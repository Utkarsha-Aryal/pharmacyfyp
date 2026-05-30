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
                    'product_code' => ['nullable', 'string', 'max:100'],
                    'product_name' => ['required', 'string', 'max:255'],
                    'mrp' => ['nullable', 'numeric', 'min:0'],
                    'cc_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                    'reorder_level' => ['nullable', 'integer', 'min:0'],
                ]);
                $validator->validate();

                $company = $this->resolveImportCompany($data, $defaultCompany);
                $unit = $this->resolveImportUnit($data, $defaultUnit);
                $inputCcRate = (float) ($data['cc_rate'] ?? 0);
                $product = $this->findImportProduct($data);
                $productCode = $data['product_code'] ?? $product?->product_code ?? $this->generateProductCode();
                $payload = [
                    'product_code' => $productCode,
                    'name' => $data['product_name'],
                    'product_name' => $data['product_name'],
                    'generic_name' => $data['generic_name'] ?? null,
                    'mrp' => $data['mrp'] ?? 0,
                    'cc_rate' => $inputCcRate > 0 ? $inputCcRate : (float) ($company?->default_cc_rate ?? 0),
                    'reorder_level' => $data['reorder_level'] ?? 10,
                    'alert_quantity' => $data['reorder_level'] ?? 10,
                    'company_id' => $company->id,
                    'sale_unit_id' => $unit->id,
                    'purchase_unit_id' => $unit->id,
                    'unit' => $unit->unit_name,
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
        $partyTypes = PartyType::query()->where('is_active', true)->get(['code', 'name']);

        foreach ($sheetRows as $rowIndex => $row) {
            try {
                $data = $this->normaliseRow($row);

                $validator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'phone' => ['nullable', 'string', 'max:100'],
                    'party_type' => ['nullable', 'string'],
                ]);
                $validator->validate();

                $partyType = $this->resolvePartyTypeCode($data['party_type'] ?? null, $partyTypes);
                $customer = $this->findImportCustomer($data);
                $payload = [
                    'name' => $data['name'],
                    'party_type' => $partyType,
                    'contact_person' => $data['contact_person'] ?? null,
                    'phone' => $data['phone'] ?? null,
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
                    'phone' => ['nullable', 'string', 'max:100'],
                ]);
                $validator->validate();

                $supplier = $this->findImportSupplier($data);
                $openingBalance = round((float) ($data['opening_balance'] ?? 0), 2);
                $payload = [
                    'supplier_name' => $data['supplier_name'],
                    'contact_person' => $data['contact_person'] ?? null,
                    'phone_number' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'pan_number' => $data['pan_number'] ?? null,
                    'opening_balance' => $openingBalance,
                    'address' => $data['address'] ?? null,
                    'type' => $this->resolveSupplierType($data['type'] ?? null),
                    'status' => 'Y',
                ];

                if ($supplier) {
                    $payload['current_balance'] = round((float) $supplier->current_balance + ($openingBalance - (float) $supplier->opening_balance), 2);
                    $supplier->update($payload);
                    $summary['updated']++;
                } else {
                    $payload['current_balance'] = $openingBalance;
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
                    'company_type' => ['nullable', 'string'],
                    'default_cc_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                ]);
                $validator->validate();

                $company = Company::query()
                    ->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
                    ->first();
                $payload = [
                    'name' => $data['name'],
                    'company_type' => $this->resolveCompanyType($data['company_type'] ?? null, $company?->company_type ?? 'domestic'),
                    'default_cc_rate' => round((float) ($data['default_cc_rate'] ?? ($company?->default_cc_rate ?? 0)), 2),
                    'status' => 'Y',
                    'slug' => $company?->slug ?: (Str::slug($data['name']) . '-' . Str::lower(Str::random(6))),
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

                $unit = Unit::query()
                    ->whereRaw('LOWER(unit_name) = ?', [strtolower($data['unit_name'])])
                    ->first();
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
        $headers = array_map(
            fn ($header, $index) => $header !== '' ? $header : 'column_' . $index,
            $headers,
            array_keys($headers)
        );
        $rows = [];

        foreach (array_slice($sheet, $headerRowIndex + 1) as $row) {
            if (count(array_filter($row, fn ($value) => $value !== null && $value !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($headers, array_slice(array_pad($row, count($headers), null), 0, count($headers)));
        }

        return $rows;
    }

    // Convert headings to clean snake keys for easier row mapping.
    private function normaliseHeading($heading): string
    {
        return Str::of((string) $heading)
            ->trim()
            ->lower()
            ->replace(['#', '(', ')', '%'], '')
            ->replace([' ', '-', '/', '.'], '_')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->toString();
    }

    // Keep row keys small and consistent before validation.
    private function normaliseRow(array $row): array
    {
        $normalised = [];

        foreach ($row as $key => $value) {
            $normalised[$this->normaliseHeading((string) $key)] = $this->normaliseCellValue($value);
        }

        return $this->applyImportAliases($normalised);
    }

    // Accept common spreadsheet labels so users do not need to know database column names.
    private function applyImportAliases(array $data): array
    {
        $original = $data;
        $aliases = [
            'product_name' => ['product', 'medicine', 'medicine_name', 'item', 'item_name', 'name'],
            'product_code' => ['code', 'sku', 'item_code', 'medicine_code'],
            'name' => ['customer_name', 'client_name', 'party_name', 'company_name', 'supplier_name'],
            'company_name' => ['company', 'brand', 'brand_name', 'manufacturer_name'],
            'company_type' => ['type', 'company_category'],
            'default_cc_rate' => ['cc_rate', 'company_cc_rate', 'company_cc', 'cc'],
            'unit_name' => ['unit', 'sale_unit', 'purchase_unit'],
            'unit' => ['unit_name', 'sale_unit', 'purchase_unit'],
            'unit_id' => ['sale_unit_id', 'purchase_unit_id'],
            'party_type' => ['type', 'customer_type', 'client_type', 'party'],
            'supplier_name' => ['supplier', 'vendor', 'vendor_name', 'company_name', 'name'],
            'phone' => ['phone_number', 'phone_no', 'mobile', 'mobile_number', 'contact_number', 'contact_no'],
            'pan_number' => ['pan', 'pan_no', 'vat', 'vat_number'],
        ];

        foreach ($aliases as $target => $sources) {
            if ($this->hasImportValue($data, $target)) {
                continue;
            }

            foreach ($sources as $source) {
                if ($this->hasImportValue($original, $source)) {
                    $data[$target] = $original[$source];
                    break;
                }
            }
        }

        return $data;
    }

    private function hasImportValue(array $data, string $key): bool
    {
        return array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '';
    }

    // Excel can read phone numbers as numbers, so convert simple scalar cells before validation.
    private function normaliseCellValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? null : $value;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return fmod($value, 1.0) === 0.0
                ? number_format($value, 0, '.', '')
                : rtrim(rtrim(number_format($value, 8, '.', ''), '0'), '.');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $value;
    }

    // Product import should be usable directly: create missing companies by name instead of failing FK.
    private function resolveImportCompany(array $data, ?Company $defaultCompany): Company
    {
        if ($this->hasImportValue($data, 'company_id')) {
            $company = Company::query()->find($data['company_id']);

            if ($company) {
                return $company;
            }
        }

        $companyName = trim((string) ($data['company_name'] ?? $data['company'] ?? ''));

        if ($companyName === '') {
            if ($defaultCompany) {
                return $defaultCompany;
            }

            $companyName = 'Imported Company';
        }

        $company = Company::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($companyName)])
            ->first();

        if ($company) {
            return $company;
        }

        return Company::query()->create([
            'name' => $companyName,
            'company_type' => $this->resolveCompanyType($data['company_type'] ?? null),
            'default_cc_rate' => round((float) ($data['company_cc_rate'] ?? $data['default_cc_rate'] ?? 0), 2),
            'status' => 'Y',
            'slug' => Str::slug($companyName) . '-' . Str::lower(Str::random(6)),
        ]);
    }

    // Products need sale/purchase unit IDs, so create missing units from the imported unit label.
    private function resolveImportUnit(array $data, ?Unit $defaultUnit): Unit
    {
        if ($this->hasImportValue($data, 'unit_id')) {
            $unit = Unit::query()->find($data['unit_id']);

            if ($unit) {
                return $unit;
            }
        }

        $unitName = trim((string) ($data['unit'] ?? $data['unit_name'] ?? ''));

        if ($unitName === '') {
            if ($defaultUnit) {
                return $defaultUnit;
            }

            $unitName = 'Unit';
        }

        $unit = Unit::query()
            ->whereRaw('LOWER(unit_name) = ?', [strtolower($unitName)])
            ->first();

        if ($unit) {
            return $unit;
        }

        return Unit::query()->create([
            'unit_name' => $unitName,
            'description' => 'Created from product import.',
            'status' => 'Y',
        ]);
    }

    private function findImportProduct(array $data): ?Product
    {
        if ($this->hasImportValue($data, 'product_code')) {
            return Product::query()->where('product_code', $data['product_code'])->first();
        }

        return Product::query()
            ->whereRaw('LOWER(product_name) = ?', [strtolower($data['product_name'])])
            ->first();
    }

    private function generateProductCode(): string
    {
        do {
            $code = 'PRD-' . Str::upper(Str::random(8));
        } while (Product::query()->where('product_code', $code)->exists());

        return $code;
    }

    private function findImportCustomer(array $data): ?Customer
    {
        if ($this->hasImportValue($data, 'phone')) {
            return Customer::query()->where('phone', $data['phone'])->first();
        }

        if ($this->hasImportValue($data, 'email')) {
            return Customer::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($data['email'])])
                ->first();
        }

        return Customer::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->first();
    }

    private function findImportSupplier(array $data): ?Supplier
    {
        if ($this->hasImportValue($data, 'phone')) {
            return Supplier::query()->where('phone_number', $data['phone'])->first();
        }

        if ($this->hasImportValue($data, 'email')) {
            return Supplier::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($data['email'])])
                ->first();
        }

        return Supplier::query()
            ->whereRaw('LOWER(supplier_name) = ?', [strtolower($data['supplier_name'])])
            ->first();
    }

    // Convert a party type from the file into an active code, falling back to customer when blank.
    private function resolvePartyTypeCode(?string $value, $partyTypes): string
    {
        $normalized = $this->normaliseImportCode($value);

        foreach ($partyTypes as $partyType) {
            if (
                $normalized !== ''
                && in_array($normalized, [
                    $this->normaliseImportCode($partyType->code),
                    $this->normaliseImportCode($partyType->name),
                ], true)
            ) {
                return $partyType->code;
            }
        }

        if (in_array($normalized, ['customer', 'institution'], true)) {
            return $normalized;
        }

        if ($normalized !== '') {
            $name = Str::headline((string) $value);
            $partyType = PartyType::query()
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->orWhereRaw('LOWER(code) = ?', [strtolower(Str::slug($name))])
                ->first();

            if ($partyType) {
                $partyType->update(['is_active' => true]);

                return $partyType->code;
            }

            $partyType = PartyType::query()->create([
                'name' => $name,
                'code' => $this->buildUniquePartyTypeCode($name),
                'is_active' => true,
            ]);

            return $partyType->code;
        }

        $fallback = $partyTypes->first(fn ($partyType) => $this->normaliseImportCode($partyType->code) === 'customer');

        return $fallback?->code ?? $partyTypes->first()?->code ?? 'customer';
    }

    private function resolveCompanyType(?string $value, string $fallback = 'domestic'): string
    {
        $normalized = $this->normaliseImportCode($value);

        if (in_array($normalized, ['foreign', 'foregin', 'imported', 'international', 'overseas'], true)) {
            return 'foreign';
        }

        if (in_array($normalized, ['domestic', 'local', 'nepal', 'nepali', 'national'], true)) {
            return 'domestic';
        }

        return in_array($fallback, ['domestic', 'foreign'], true) ? $fallback : 'domestic';
    }

    private function resolveSupplierType(?string $value): string
    {
        $normalized = $this->normaliseImportCode($value);

        if (in_array($normalized, ['debit', 'dr', 'payable', 'advance'], true)) {
            return 'debit';
        }

        return 'credit';
    }

    private function normaliseImportCode(?string $value): string
    {
        return Str::of((string) $value)
            ->trim()
            ->lower()
            ->replace([' ', '-', '/', '.'], '_')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->toString();
    }

    private function buildUniquePartyTypeCode(string $name): string
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
