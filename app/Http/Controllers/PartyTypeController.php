<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PartyType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PartyTypeController extends Controller
{
    // Return the current party type list so settings and quick-add popups stay in sync.
    public function index()
    {
        return response()->json([
            'type' => 'success',
            'data' => PartyType::query()->orderBy('name')->get()->map(function (PartyType $partyType) {
                return [
                    'id' => $partyType->id,
                    'name' => $partyType->name,
                    'code' => $partyType->code,
                    'text' => $partyType->name,
                    'is_active' => (bool) $partyType->is_active,
                ];
            })->values(),
        ]);
    }

    // Save one party type from the settings master popup.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:party_types,name'],
        ]);

        $name = trim($validated['name']);
        $code = $this->buildUniqueCode($name);

        $partyType = PartyType::query()->create([
            'name' => $name,
            'code' => $code,
            'is_active' => true,
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'Party type saved successfully.',
            'data' => [
                'id' => $partyType->id,
                'name' => $partyType->name,
                'code' => $partyType->code,
                'text' => $partyType->name,
                'is_active' => $partyType->is_active,
            ],
        ]);
    }

    // Update one party type or quickly toggle its active state.
    public function update(Request $request, PartyType $partyType)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255', Rule::unique('party_types', 'name')->ignore($partyType->id)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $partyType->update([
            'name' => array_key_exists('name', $validated) ? trim((string) $validated['name']) : $partyType->name,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $partyType->is_active,
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'Party type updated successfully.',
            'data' => [
                'id' => $partyType->id,
                'name' => $partyType->name,
                'code' => $partyType->code,
                'text' => $partyType->name,
                'is_active' => $partyType->is_active,
            ],
        ]);
    }

    // Remove a party type only when it is not used by existing parties.
    public function destroy(PartyType $partyType)
    {
        if (Customer::query()->where('party_type', $partyType->code)->exists()) {
            return response()->json([
                'type' => 'error',
                'message' => 'This party type is already used by one or more parties.',
            ], 422);
        }

        $partyType->delete();

        return response()->json([
            'type' => 'success',
            'message' => 'Party type deleted successfully.',
        ]);
    }

    // Turn a label into a stable code and keep it unique in the table.
    private function buildUniqueCode(string $name): string
    {
        $baseCode = Str::slug($name) ?: 'party-type';
        $code = $baseCode;
        $suffix = 1;

        while (PartyType::query()->where('code', $code)->exists()) {
            $code = $baseCode . '-' . $suffix;
            $suffix++;
        }

        return $code;
    }
}
