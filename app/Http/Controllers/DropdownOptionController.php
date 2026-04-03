<?php

namespace App\Http\Controllers;

use App\Models\DropdownOption;
use App\Models\PartyType;
use App\Models\SupplierType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DropdownOptionController extends Controller
{
    // Show grouped option rows for the settings area, or return one alias list for ajax refresh.
    public function index(Request $request)
    {
        $aliases = DropdownOption::managedAliases();

        $query = DropdownOption::query()
            ->whereIn('alias', array_keys($aliases))
            ->orderBy('alias')
            ->orderBy('name');

        if ($request->filled('alias')) {
            $query->where('alias', $request->input('alias'));
        }

        $options = $query->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'type' => 'success',
                'data' => $options->map(fn (DropdownOption $option) => $this->rowPayload($option))->values(),
            ]);
        }

        return view('settings.dropdown-options', [
            'dropdownOptionAliases' => $aliases,
            'dropdownOptionGroups' => $options->groupBy('alias'),
            'partyTypes' => PartyType::query()->orderBy('name')->get(),
            'supplierTypes' => SupplierType::query()->orderBy('name')->get(),
        ]);
    }

    // Save one shared dropdown option row for any supported alias.
    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);

        $option = DropdownOption::query()->create([
            'alias' => $validated['alias'],
            'name' => trim($validated['name']),
            'data' => $this->cleanDataValue($validated['data'] ?? null),
            'status' => (int) ($validated['status'] ?? 1),
        ]);

        return response()->json([
            'type' => 'success',
            'message' => $option->alias_label . ' saved successfully.',
            'data' => $this->rowPayload($option),
        ]);
    }

    // Update one shared dropdown option row using the same alias-safe validation rules.
    public function update(Request $request, DropdownOption $dropdownOption)
    {
        $validated = $this->validatedPayload($request, $dropdownOption->id);

        $dropdownOption->update([
            'alias' => $validated['alias'],
            'name' => trim($validated['name']),
            'data' => $this->cleanDataValue($validated['data'] ?? null),
            'status' => (int) ($validated['status'] ?? $dropdownOption->status),
        ]);

        return response()->json([
            'type' => 'success',
            'message' => $dropdownOption->alias_label . ' updated successfully.',
            'data' => $this->rowPayload($dropdownOption->fresh()),
        ]);
    }

    // Delete only when the option is not already linked anywhere important in the system.
    public function destroy(DropdownOption $dropdownOption)
    {
        $linkedUsage = $this->linkedUsageCount($dropdownOption);

        if ($linkedUsage > 0) {
            return response()->json([
                'type' => 'error',
                'message' => 'This option is already in use and cannot be deleted.',
            ], 422);
        }

        $dropdownOption->delete();

        return response()->json([
            'type' => 'success',
            'message' => 'Option deleted successfully.',
        ]);
    }

    // One reusable validation block keeps every alias under the same safe rules.
    private function validatedPayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'alias' => ['required', Rule::in(array_keys(DropdownOption::managedAliases()))],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dropdown_options', 'name')
                    ->where(fn ($query) => $query->where('alias', $request->input('alias')))
                    ->ignore($ignoreId),
            ],
            'data' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ]);
    }

    // Settings tables and quick-add modals both use the same response shape.
    private function rowPayload(DropdownOption $option): array
    {
        return [
            'id' => $option->id,
            'alias' => $option->alias,
            'alias_label' => $option->alias_label,
            'name' => $option->name,
            'text' => $option->name,
            'data' => $option->data,
            'status' => (int) $option->status,
            'is_active' => (bool) $option->status,
        ];
    }

    // A small cleanup keeps optional metadata fields tidy in the database.
    private function cleanDataValue(?string $value): ?string
    {
        return filled($value) ? trim($value) : null;
    }

    // Before delete we check the key places that depend on these shared options.
    private function linkedUsageCount(DropdownOption $option): int
    {
        return match ($option->alias) {
            'product_status' => \App\Models\Product::query()->where('product_status_id', $option->id)->count(),
            'formulation' => \App\Models\Product::query()->where('formulation_id', $option->id)->count(),
            'sales_type' => \App\Models\SalesInvoice::query()->where('sale_type_id', $option->id)->count(),
            'payment_mode' => \App\Models\SalesInvoice::query()->where('payment_mode_id', $option->id)->count()
                + \App\Models\Purchase::query()->where('payment_mode_id', $option->id)->count()
                + \App\Models\Payment::query()->where('payment_mode_id', $option->id)->count()
                + \App\Models\Expense::query()->where('payment_mode_id', $option->id)->count(),
            'expense_category' => \App\Models\Expense::query()->where('expense_category_id', $option->id)->count(),
            default => 0,
        };
    }
}
