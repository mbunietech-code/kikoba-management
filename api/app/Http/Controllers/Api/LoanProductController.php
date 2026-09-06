<?php

namespace App\Http\Controllers\Api;

use App\Models\LoanProduct;
use App\Support\ApiResponse;
use App\Support\Audit;
use Illuminate\Http\Request;

class LoanProductController extends ApiController
{
    public function index(Request $request)
    {
        return $this->items(
            LoanProduct::where('organization_id', $this->orgId($request))->orderBy('name')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $product = LoanProduct::create([...$data, 'organization_id' => $this->orgId($request)]);
        Audit::log($request, 'CREATE_PRODUCT', 'LoanProduct', $product->id);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($product))->resolve(), 'Product created');
    }

    public function update(Request $request, string $id)
    {
        $product = LoanProduct::where('organization_id', $this->orgId($request))->findOrFail($id);
        $product->update($this->validated($request, true));
        Audit::log($request, 'UPDATE_PRODUCT', 'LoanProduct', $product->id);

        return $this->item($product->fresh());
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rule = fn ($r) => $partial ? array_merge(['sometimes'], $r) : $r;

        return $request->validate([
            'name' => $rule(['required', 'string', 'max:120']),
            'description' => ['nullable', 'string'],
            'minimum_amount' => $rule(['required', 'integer', 'min:0']),
            'maximum_amount' => $rule(['required', 'integer', 'min:0']),
            'interest_rate' => $rule(['required', 'numeric', 'min:0']),
            'interest_method' => $rule(['required', 'in:flat,reducing']),
            'repayment_period' => $rule(['required', 'integer', 'min:1']),
            'repayment_frequency' => ['nullable', 'in:weekly,biweekly,monthly,quarterly'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'insurance_fee' => ['nullable', 'numeric', 'min:0'],
            'penalty_rate' => ['nullable', 'numeric', 'min:0'],
            'minimum_savings' => ['nullable', 'integer', 'min:0'],
            'minimum_shares' => ['nullable', 'integer', 'min:0'],
            'required_guarantors' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
    }
}
