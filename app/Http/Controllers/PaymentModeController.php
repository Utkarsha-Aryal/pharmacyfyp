<?php

namespace App\Http\Controllers;

use App\Models\PaymentMode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentModeController extends Controller
{
    // Return the current payment mode list so settings can refresh the table after ajax save.
    public function index()
    {
        return response()->json([
            'type' => 'success',
            'data' => PaymentMode::query()->orderBy('name')->get(),
        ]);
    }

    // Save one payment mode from the settings modal.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:payment_modes,name'],
            'type' => ['required', Rule::in(['cash', 'bank', 'digital'])],
        ]);

        $paymentMode = PaymentMode::query()->create([
            'name' => trim($validated['name']),
            'type' => $validated['type'],
            'is_active' => true,
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'Payment mode saved successfully.',
            'data' => $paymentMode,
        ]);
    }

    // Update one payment mode or quickly toggle active state from the settings table.
    public function update(Request $request, PaymentMode $paymentMode)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255', Rule::unique('payment_modes', 'name')->ignore($paymentMode->id)],
            'type' => ['nullable', Rule::in(['cash', 'bank', 'digital'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $paymentMode->update([
            'name' => array_key_exists('name', $validated) ? trim((string) $validated['name']) : $paymentMode->name,
            'type' => $validated['type'] ?? $paymentMode->type,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $paymentMode->is_active,
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'Payment mode updated successfully.',
            'data' => $paymentMode->fresh(),
        ]);
    }

    // Cash and Bank are system defaults, so only custom modes can be removed.
    public function destroy(PaymentMode $paymentMode)
    {
        abort_if(in_array(strtolower((string) $paymentMode->name), ['cash', 'bank'], true), 403, 'Cash and Bank cannot be deleted.');

        $paymentMode->delete();

        return response()->json([
            'type' => 'success',
            'message' => 'Payment mode deleted successfully.',
        ]);
    }
}
