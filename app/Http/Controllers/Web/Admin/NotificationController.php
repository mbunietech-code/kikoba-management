<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Support\Audit;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('organization_id', app('kikoba.org')->id)
            ->latest('created_at')->paginate(30);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function readAll()
    {
        Notification::where('organization_id', app('kikoba.org')->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('toast', t('notifications.markAllRead').' ✓');
    }

    public function announce(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
            'channel' => ['required', 'in:in_app,sms,email,push,whatsapp'],
        ]);
        $n = Notification::create([...$data, 'organization_id' => app('kikoba.org')->id, 'type' => 'announcement']);
        Audit::log($request, 'SEND_ANNOUNCEMENT', 'Notification', $n->id);

        return back()->with('toast', t('common.submit').' ✓');
    }

    public function destroy(Request $request, Notification $notification)
    {
        $notification->delete();

        return back()->with('toast', t('common.deleted'));
    }
}
