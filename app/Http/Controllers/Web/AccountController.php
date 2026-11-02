<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * The signed-in user's own account: name, phone and password.
 */
class AccountController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.account', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);
        $request->user()->update($data);
        Audit::log($request, 'UPDATE_OWN_ACCOUNT', 'User', $request->user()->id);

        return back()->with('toast', t('common.saveChanges').' ✓');
    }

    /**
     * Shown right after signing in with a temporary password.
     */
    public function newPassword(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect('/');
        }

        return view('auth.new-password');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ]);
        $wasTemporary = $request->user()->must_change_password;
        $request->user()->update(['password' => $data['password'], 'must_change_password' => false]);
        Audit::log($request, 'CHANGE_OWN_PASSWORD', 'User', $request->user()->id);

        return ($wasTemporary ? redirect('/') : back())->with('toast', t('account.passwordChanged'));
    }
}
