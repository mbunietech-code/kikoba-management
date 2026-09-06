<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoanProduct;
use App\Support\Audit;
use Illuminate\Http\Request;

class LoanProductController extends Controller
{
    public function index()
    {
        $products = LoanProduct::where('organization_id', app('kikoba.org')->id)->orderBy('name')->get();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new LoanProduct([
            'interest_method' => 'reducing', 'repayment_frequency' => 'monthly', 'status' => 'active',
            'repayment_period' => 6, 'required_guarantors' => 2,
        ])]);
    }

    public function edit(LoanProduct $product)
    {
        return view('admin.products.form', compact('product'));
    }

    public function store(Request $request)
    {
        $p = LoanProduct::create([...$this->validated($request), 'organization_id' => app('kikoba.org')->id]);
        Audit::log($request, 'CREATE_PRODUCT', 'LoanProduct', $p->id);

        return redirect()->route('admin.products.index')->with('toast', t('common.save').' ✓');
    }

    public function update(Request $request, LoanProduct $product)
    {
        $product->update($this->validated($request));
        Audit::log($request, 'UPDATE_PRODUCT', 'LoanProduct', $product->id);

        return redirect()->route('admin.products.index')->with('toast', t('common.saveChanges').' ✓');
    }

    public function destroy(Request $request, LoanProduct $product)
    {
        if ($product->loans()->exists()) {
            $product->update(['status' => 'inactive']);

            return back()->with('toast', 'Has loans — deactivated');
        }
        $product->delete();
        Audit::log($request, 'DELETE_PRODUCT', 'LoanProduct', $product->id);

        return back()->with('toast', t('common.deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'minimum_amount' => ['required', 'integer', 'min:0'],
            'maximum_amount' => ['required', 'integer', 'min:0'],
            'interest_rate' => ['required', 'numeric', 'min:0'],
            'interest_method' => ['required', 'in:flat,reducing'],
            'repayment_period' => ['required', 'integer', 'min:1'],
            'repayment_frequency' => ['required', 'in:weekly,biweekly,monthly,quarterly'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'insurance_fee' => ['nullable', 'numeric', 'min:0'],
            'penalty_rate' => ['nullable', 'numeric', 'min:0'],
            'minimum_savings' => ['nullable', 'integer', 'min:0'],
            'minimum_shares' => ['nullable', 'integer', 'min:0'],
            'required_guarantors' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }
}
