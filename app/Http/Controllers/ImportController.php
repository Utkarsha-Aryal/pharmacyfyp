<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\DropdownOption;
use App\Models\PartyType;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    // Download the company sample file.
    public function sampleCompanies()
    {
        return response()->download(storage_path('app/samples/companies-sample.csv'));
    }

    // Download the unit sample file.
    public function sampleUnits()
    {
        return response()->download(storage_path('app/samples/units-sample.csv'));
    }

    // Download the product sample file with one small note row and two example rows.
    public function sampleProducts()
    {
        return response()->download(storage_path('app/samples/products-sample.csv'));
    }

    // Download the customer sample file.
    public function sampleCustomers()
    {
        return response()->download(storage_path('app/samples/customers-sample.csv'));
    }

    // Download the supplier sample file.
    public function sampleSuppliers()
    {
        return response()->download(storage_path('app/samples/suppliers-sample.csv'));
    }

    // Import products in bulk and collect row-wise errors instead of stopping at the first problem.
    public function importProducts(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $sheetRows = $this->sheetRows($validated['file']);
        $summary = ['imported' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];
        $defaultCompany = Company::query()->orderBy('id')->first();
        $defaultUnit = Unit::query()->orderBy('id')->first();

        foreach ($sheetRows as $rowIndex => $row) {
            try {
                $data = $this->normaliseRow($row);

                $validator = Validator::make($data, [
                    'product_code' => ['required', 'string', 'max:100'],
                    'product_name' => ['required', 'string', 'max:255'],
                    'mrp' => ['nullable', 'numeric', 'min:0'],
                    'cc_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                    'reorder_level' => ['nullable', 'integer', 'min:0'],
                ]);
                $validator->validate();

                $companyName = $data['company_name'] ?? null;
                $company = !empty($companyName)
                    ? Company::query()->where('name', $companyName)->first()
                    : $defaultCompany;
                $inputCcRate = (float) ($data['cc_rate'] ?? 0);
                $product = Product::query()->where('product_code', $data['product_code'])->first();
                $payload = [
                    'product_code' => $data['product_code'],
                    'name' => $data['product_name'],
                    'product_name' => $data['product_name'],
                    'generic_name' => $data['generic_name'] ?? null,
                    'mrp' => $data['mrp'] ?? 0,
                    'cc_rate' => $inputCcRate > 0 ? $inputCcRate : (float) ($company?->default_cc_rate ?? 0),
                    'reorder_level' => $data['reorder_level'] ?? 10,
                    'alert_quantity' => $data['reorder_level'] ?? 10,
                    'company_id' => $company?->id,
                    'sale_unit_id' => $defaultUnit?->id,
                    'purchase_unit_id' => $defaultUnit?->id,
                    'unit' => $data['unit'] ?? ($defaultUnit?->unit_name ?: 'Unit'),
                    'description' => $data['description'] ?? null,
                    'manufacturer' => $data['manufacturer'] ?? null,
                    'purchase_price' => $data['purchase_price'] ?? 0,
                    'status' => 'Y',
                    'is_active' => true,
                    'slug' => $product?->slug ?: (Str::slug($data['product_name']) . '-' . Str::lower(Str::random(6))),
                    'product_status_id' => DropdownOption::findIdByAliasAndName('product_status', 'In Stock'),
                    'product_status' => Product::legacyStatusCode('In Stock'),
                    'formulation_id' => !empty($data['formulation']) ? DropdownOption::findIdByAliasAndName('formulation', ucwords(strtolower($data['formulation']))) : null,
                    'formulation' => !empty($data['formulation']) ? ucwords(strtolower($data['formulation'])) : null,
                ];

                if ($product) {
                    $product->update($payload);
                    $summary['updated']++;
                } else {
                    Product::query()->create($payload);
                    $summary['imported']++;
                }
            } catch (\Throwable $throwable) {
                $summary['failed']++;
                $summary['errors'][] = 'Row ' . ($rowIndex + 1) . ': ' . $throwable->getMessage();
            }
        }

        return back()->with('import_summary', $summary);
    }

    // Import customer and institution rows in bulk.
    public function importCustomers(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $sheetRows = $this->sheetRows($validated['file']);
        $summary = ['imported' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];
        $partyTypeCodes = PartyType::query()->where('is_active', true)->pluck('code')->all();

        foreach ($sheetRows as $rowIndex => $row) {
            try {
                $data = $this->normaliseRow($row);

                $validator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'phone' => ['required', 'string', 'max:100'],
                    'party_type' => ['nullable', 'string'],
                ]);
                $validator->validate();

                $partyType = $this->resolvePartyTypeCode($data['party_type'] ?? null, $partyTypeCodes);
                $customer = Customer::query()->where('phone', $data['phone'])->first();
                $payload = [
                    'name' => $data['name'],
                    'party_type' => $partyType,
                    'contact_person' => $data['contact_person'] ?? null,
                    'phone' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'address' => $data['address'] ?? null,
                    'credit_limit' => $data['credit_limit'] ?? 0,
                    'opening_balance' => $data['opening_balance'] ?? 0,
                    'current_balance' => $customer?->current_balance ?? ($data['opening_balance'] ?? 0),
                    'is_active' => true,
                ];

                if ($customer) {
                    $customer->update($payload);
                    $summary['updated']++;
                } else {
                    Customer::query()->create($payload);
                    $summary['imported']++;
                }
            } catch (\Throwable $throwable) {
                $summary['failed']++;
                $summary['errors'][] = 'Row ' . ($rowIndex + 1) . ': ' . $throwable->getMessage();
            }
        }

        return back()->with('import_summary', $summary);
    }

    // Import supplier rows in bulk.
    public function importSuppliers(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $sheetRows = $this->sheetRows($validated['file']);
        $summary = ['imported' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];

        foreach ($sheetRows as $rowIndex => $row) {
            try {
                $data = $this->normaliseRow($row);

                $validator = Validator::make($data, [
                    'supplier_name' => ['required', 'string', 'max:255'],
                    'phone' => ['required', 'string', 'max:100'],
                ]);
                $validator->validate();

                $supplier = Supplier::query()->where('phone_number', $data['phone'])->first();
                $payload = [
                    'supplier_name' => $data['supplier_name'],
                    'contact_person' => $data['contact_person'] ?? null,
                    'phone_number' => $data['phone'],
                    'email' => $data['email'] ?? null,
                    'pan_number' => $data['pan_number'] ?? null,
                    'opening_balance' => $data['opening_balance'] ?? 0,
                    'address' => $data['address'] ?? null,
                    'type' => $data['type'] ?? 'credit',
                    'status' => 'Y',
                ];

                if ($supplier) {
                    $supplier->update($payload);
                    $summary['updated']++;
                } else {
                    Supplier::query()->create($payload);
                    $summary['imported']++;
                }
            } catch (\Throwable $throwable) {
                $summary['failed']++;
                $summary['errors'][] = 'Row ' . ($rowIndex + 1) . ': ' . $throwable->getMessage();
            }
        }

        return back()->with('import_summary', $summary);
    }

    // Import company rows in bulk and update by company name.
    public function importCompanies(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $sheetRows = $this->sheetRows($validated['file']);
        $summary = ['imported' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];

        foreach ($sheetRows as $rowIndex => $row) {
            try {
                $data = $this->normaliseRow($row);

                $validator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'company_type' => ['nullable', 'in:domestic,foreign'],
                    'default_cc_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                ]);
                $validator->validate();

                $company = Company::query()->where('name', $data['name'])->first();
                $payload = [
                    'name' => $data['name'],
                    'company_type' => $data['company_type'] ?? ($company?->company_type ?? 'domestic'),
                    'default_cc_rate' => round((float) ($data['default_cc_rate'] ?? ($company?->default_cc_rate ?? 0)), 2),
                    'status' => 'Y',
                ];

                if ($company) {
                    $company->update($payload);
                    $summary['updated']++;
                } else {
                    Company::query()->create($payload);
                    $summary['imported']++;
                }
            } catch (\Throwable $throwable) {
                $summary['failed']++;
                $summary['errors'][] = 'Row ' . ($rowIndex + 1) . ': ' . $throwable->getMessage();
            }
        }

        return back()->with('import_summary', $summary);
    }

    // Import unit rows in bulk and update by unit name.
    public function importUnits(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $sheetRows = $this->sheetRows($validated['file']);
        $summary = ['imported' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];

        foreach ($sheetRows as $rowIndex => $row) {
            try {
                $data = $this->normaliseRow($row);

                $validator = Validator::make($data, [
                    'unit_name' => ['required', 'string', 'max:255'],
                    'description' => ['nullable', 'string'],
                ]);
                $validator->validate();

                $unit = Unit::query()->where('unit_name', $data['unit_name'])->first();
                $payload = [
                    'unit_name' => $data['unit_name'],
                    'description' => $data['description'] ?? null,
                    'status' => 'Y',
                ];

                if ($unit) {
                    $unit->update($payload);
                    $summary['updated']++;
                } else {
                    Unit::query()->create($payload);
                    $summary['imported']++;
                }
            } catch (\Throwable $throwable) {
                $summary['failed']++;
                $summary['errors'][] = 'Row ' . ($rowIndex + 1) . ': ' . $throwable->getMessage();
            }
        }

        return back()->with('import_summary', $summary);
    }

    // Read the first sheet and skip the note row before real headings.
    private function sheetRows($file): array
    {
        $sheet = Excel::toArray([], $file)[0] ?? [];

        if (empty($sheet)) {
            return [];
        }

        $headerRowIndex = 0;
        if (isset($sheet[0][0]) && str_contains(strtolower((string) $sheet[0][0]), 'notes')) {
            $headerRowIndex = 1;
        }

        $headers = array_map([$this, 'normaliseHeading'], $sheet[$headerRowIndex] ?? []);
        $rows = [];

        foreach (array_slice($sheet, $headerRowIndex + 1) as $row) {
            if (count(array_filter($row, fn ($value) => $value !== null && $value !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($headers, array_pad($row, count($headers), null));
        }

        return $rows;
    }

    // Convert headings to clean snake keys for easier row mapping.
    private function normaliseHeading($heading): string
    {
        return Str::of((string) $heading)->trim()->lower()->replace([' ', '-', '/', '.'], '_')->toString();
    }

    // Keep row keys small and consistent before validation.
    private function normaliseRow(array $row): array
    {
        $normalised = [];

        foreach ($row as $key => $value) {
            $normalised[$this->normaliseHeading((string) $key)] = is_string($value) ? trim($value) : $value;
        }

        return $normalised;
    }

    // Convert a party type from the file into an active code, falling back to customer when blank.
    private function resolvePartyTypeCode(?string $value, array $activeCodes): string
    {
        $normalized = Str::of((string) $value)->trim()->lower()->replace([' ', '-'], '_')->toString();
        $activeCodes = array_values(array_filter($activeCodes));

        if ($normalized !== '' && in_array($normalized, $activeCodes, true)) {
            return $normalized;
        }

        return in_array('customer', $activeCodes, true) ? 'customer' : ($activeCodes[0] ?? 'customer');
    }
}
