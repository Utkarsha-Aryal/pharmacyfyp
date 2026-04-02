<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
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
        $defaultCategory = Category::query()->orderBy('id')->first();
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

                $category = !empty($data['category_name'])
                    ? Category::query()->where('name', $data['category_name'])->first()
                    : $defaultCategory;
                $product = Product::query()->where('product_code', $data['product_code'])->first();
                $payload = [
                    'product_code' => $data['product_code'],
                    'name' => $data['product_name'],
                    'product_name' => $data['product_name'],
                    'generic_name' => $data['generic_name'] ?? null,
                    'mrp' => $data['mrp'] ?? 0,
                    'cc_rate' => $data['cc_rate'] ?? 0,
                    'reorder_level' => $data['reorder_level'] ?? 10,
                    'alert_quantity' => $data['reorder_level'] ?? 10,
                    'category_id' => $category?->id,
                    'sale_unit_id' => $defaultUnit?->id,
                    'purchase_unit_id' => $defaultUnit?->id,
                    'unit' => $data['unit'] ?? ($defaultUnit?->unit_name ?: 'Unit'),
                    'description' => $data['description'] ?? null,
                    'manufacturer' => $data['manufacturer'] ?? null,
                    'purchase_price' => $data['purchase_price'] ?? 0,
                    'status' => 'Y',
                    'is_active' => true,
                    'slug' => $product?->slug ?: (Str::slug($data['product_name']) . '-' . Str::lower(Str::random(6))),
                    'product_status' => 'instock',
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

        foreach ($sheetRows as $rowIndex => $row) {
            try {
                $data = $this->normaliseRow($row);

                $validator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'phone' => ['required', 'string', 'max:100'],
                    'party_type' => ['nullable', 'in:customer,institution'],
                ]);
                $validator->validate();

                $customer = Customer::query()->where('phone', $data['phone'])->first();
                $payload = [
                    'name' => $data['name'],
                    'party_type' => $data['party_type'] ?? 'customer',
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
}
