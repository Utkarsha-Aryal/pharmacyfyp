<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupplierTypeController extends Controller
{
    // Return the current supplier type list so settings and quick-add popups stay in sync.
    public function index()
    {
        return response()->json([
            'type' => 'success',
            'data' => SupplierType::query()->orderBy('name')->get()->map(function (SupplierType $supplierType) {
                return [
                    'id' => $supplierType->id,
                    'name' => $supplierType->name,
                    'code' => $supplierType->code,
                    'text' => $supplierType->name,
                    'is_active' => (bool) $supplierType->is_active,
                ];
            })->values(),
        ]);
    }

    // Save one supplier type from the settings master popup.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:supplier_types,name'],
        ]);

        $name = trim($validated['name']);
        $code = $this->buildUniqueCode($name);

        $supplierType = SupplierType::query()->create([
            'name' => $name,
            'code' => $code,
            'is_active' => true,
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'Supplier type saved successfully.',
            'data' => [
                'id' => $supplierType->id,
                'name' => $supplierType->name,
                'code' => $supplierType->code,
                'text' => $supplierType->name,
                'is_active' => $supplierType->is_active,
            ],
        ]);
    }

    // Update one supplier type or quickly toggle its active state.
    public function update(Request $request, SupplierType $supplierType)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255', Rule::unique('supplier_types', 'name')->ignore($supplierType->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $supplierType->update([
            'name' => array_key_exists('name', $validated) ? trim((string) $validated['name']) : $supplierType->name,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $supplierType->is_active,
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'Supplier type updated successfully.',
            'data' => [
                'id' => $supplierType->id,
                'name' => $supplierType->name,
                'code' => $supplierType->code,
                'text' => $supplierType->name,
                'is_active' => $supplierType->is_active,
            ],
        ]);
    }

    // Remove a supplier type only when it is not used by existing suppliers.
    public function destroy(SupplierType $supplierType)
    {
        if (Supplier::query()->where('type', $supplierType->code)->exists()) {
            return response()->json([
                'type' => 'error',
                'message' => 'This supplier type is already used by one or more suppliers.',
            ], 422);
        }

        $supplierType->delete();

        return response()->json([
            'type' => 'success',
            'message' => 'Supplier type deleted successfully.',
        ]);
    }

    // Turn a label into a stable code and keep it unique in the table.
    private function buildUniqueCode(string $name): string
    {
        $baseCode = Str::slug($name) ?: 'supplier-type';
        $code = $baseCode;
        $suffix = 1;

        while (SupplierType::query()->where('code', $code)->exists()) {
            $code = $baseCode . '-' . $suffix;
            $suffix++;
        }

        return $code;
    }
}
