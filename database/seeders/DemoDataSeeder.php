<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\DropdownOption;
use App\Models\Expense;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesReturn;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReference;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    // This demo seeder gives the project both fresh activity and older history,
    // so reports do not look empty when someone tests finance and analytics pages.
    public function run(): void
    {
        if (DB::table('companies')->count() > 0) {
            return;
        }

        $now = now();

        $unitIds = [];
        foreach ([
            ['unit_name' => 'Piece', 'type' => 'both', 'description' => 'Single medicine piece'],
            ['unit_name' => 'Strip', 'type' => 'sales', 'description' => 'Tablet strip'],
            ['unit_name' => 'Box', 'type' => 'purchase', 'description' => 'Purchase box'],
            ['unit_name' => 'Carton', 'type' => 'purchase', 'description' => 'Large purchase carton'],
            ['unit_name' => 'Tablet', 'type' => 'sales', 'description' => 'Single tablet unit'],
            ['unit_name' => 'Bottle', 'type' => 'both', 'description' => 'Liquid bottle'],
            ['unit_name' => 'Sachet', 'type' => 'both', 'description' => 'Single sachet packet'],
        ] as $unit) {
            $unitIds[$unit['unit_name']] = DB::table('units')->insertGetId([
                'unit_name' => $unit['unit_name'],
                'type' => $unit['type'],
                'description' => $unit['description'],
                'status' => 'Y',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $companyIds = [];
        foreach ([
            ['name' => 'Himalaya Labs', 'company_type' => 'domestic', 'default_cc_rate' => 5.50],
            ['name' => 'ABC Pharma Imports', 'company_type' => 'foreign', 'default_cc_rate' => 8.25],
            ['name' => 'Nutri Life Pharma', 'company_type' => 'domestic', 'default_cc_rate' => 6.00],
            ['name' => 'Life Care International', 'company_type' => 'foreign', 'default_cc_rate' => 7.25],
            ['name' => 'Cardio Care Nepal', 'company_type' => 'domestic', 'default_cc_rate' => 5.75],
        ] as $company) {
            $companyIds[] = DB::table('companies')->insertGetId([
                'name' => $company['name'],
                'slug' => Str::slug($company['name']) . '-' . Str::random(8),
                'company_type' => $company['company_type'],
                'default_cc_rate' => $company['default_cc_rate'],
                'status' => 'Y',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $supplierIds = [];
        foreach ([
            ['supplier_name' => 'Himal Pharma Link', 'contact_person' => 'Ryan Koirala', 'phone_number' => '9801234567', 'email' => 'himal@example.com', 'pan_number' => 'PAN-1001', 'opening_balance' => '12000.00', 'address' => 'Kathmandu', 'type' => 'credit'],
            ['supplier_name' => 'Everest Medico', 'contact_person' => 'Susmita KC', 'phone_number' => '9807654321', 'email' => 'everest@example.com', 'pan_number' => 'PAN-1002', 'opening_balance' => '8500.00', 'address' => 'Lalitpur', 'type' => 'credit'],
            ['supplier_name' => 'Care Nepal Distributors', 'contact_person' => 'Pratik Thapa', 'phone_number' => '9812345678', 'email' => 'care@example.com', 'pan_number' => 'PAN-1003', 'opening_balance' => '6200.00', 'address' => 'Bhaktapur', 'type' => 'debit'],
        ] as $supplier) {
            $supplierIds[] = DB::table('suppliers')->insertGetId(array_merge($supplier, [
                'status' => 'Y',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $findDropdownId = function (string $alias, string $name): ?int {
            return DropdownOption::findIdByAliasAndName($alias, $name);
        };

        $paymentModeIdFromLegacy = function (?string $legacyMode) use ($findDropdownId): ?int {
            return match (strtolower(trim((string) $legacyMode))) {
                'bank', 'bank transfer', 'transfer' => $findDropdownId('payment_mode', 'Bank Transfer'),
                'cheque', 'check' => $findDropdownId('payment_mode', 'Cheque'),
                'esewa' => $findDropdownId('payment_mode', 'eSewa'),
                'khalti' => $findDropdownId('payment_mode', 'Khalti'),
                default => $findDropdownId('payment_mode', 'Cash'),
            };
        };

        $expenseCategoryIdFromLegacy = function (?string $legacyName) use ($findDropdownId): ?int {
            $normalized = strtolower(trim((string) $legacyName));
            $mappedName = match ($normalized) {
                'salary' => 'Salary',
                'rent' => 'Rent',
                'utilities', 'utility' => 'Utilities',
                'supplies', 'office', 'stationery' => 'Supplies',
                'maintenance', 'fuel' => 'Maintenance',
                default => 'Miscellaneous',
            };

            return $findDropdownId('expense_category', $mappedName);
        };

        $productStatusId = $findDropdownId('product_status', 'In Stock');

        $productRows = [
            ['name' => 'Ibuprofen 400mg', 'generic' => 'Ibuprofen', 'company' => 1, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 12, 'mrp' => 110, 'purchase_price' => 78, 'alert' => 12, 'manufacturer' => 'Himalaya Labs', 'cc_rate' => 7.50],
            ['name' => 'Paracetamol 500mg', 'generic' => 'Paracetamol', 'company' => 1, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 20, 'mrp' => 95, 'purchase_price' => 60, 'alert' => 20, 'manufacturer' => 'Himalaya Labs', 'cc_rate' => 5.00],
            ['name' => 'Amoxicillin 500mg', 'generic' => 'Amoxicillin', 'company' => 2, 'formulation' => 'capsule', 'unit' => 'Strip', 'reorder' => 18, 'mrp' => 145, 'purchase_price' => 102, 'alert' => 18, 'manufacturer' => 'ABC Pharma Imports', 'cc_rate' => 8.25],
            ['name' => 'Azithromycin 250mg', 'generic' => 'Azithromycin', 'company' => 2, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 15, 'mrp' => 220, 'purchase_price' => 170, 'alert' => 15, 'manufacturer' => 'ABC Pharma Imports', 'cc_rate' => 6.00],
            ['name' => 'Vitamin C 1000mg', 'generic' => 'Ascorbic Acid', 'company' => 3, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 10, 'mrp' => 310, 'purchase_price' => 250, 'alert' => 10, 'manufacturer' => 'Nutri Life Pharma', 'cc_rate' => 10.00],
            ['name' => 'Metformin 500mg', 'generic' => 'Metformin', 'company' => 4, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 14, 'mrp' => 180, 'purchase_price' => 132, 'alert' => 14, 'manufacturer' => 'Life Care International', 'cc_rate' => 7.00],
            ['name' => 'Amlodipine 5mg', 'generic' => 'Amlodipine', 'company' => 5, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 10, 'mrp' => 165, 'purchase_price' => 120, 'alert' => 10, 'manufacturer' => 'Cardio Care Nepal', 'cc_rate' => 5.50],
            ['name' => 'Atorvastatin 10mg', 'generic' => 'Atorvastatin', 'company' => 5, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 8, 'mrp' => 265, 'purchase_price' => 190, 'alert' => 8, 'manufacturer' => 'Cardio Care Nepal', 'cc_rate' => 6.75],
        ];

        $productIds = [];
        foreach ($productRows as $product) {
            $productIds[] = DB::table('products')->insertGetId([
                'name' => $product['name'],
                'product_name' => $product['name'],
                'composition' => $product['generic'],
                'group_name' => 'Medicine',
                'manufacturer' => $product['manufacturer'],
                'description' => $product['name'] . ' sample record for pharmacy demo.',
                'previous_price' => $product['purchase_price'],
                'mrp' => $product['mrp'],
                'cc_rate' => $product['cc_rate'],
                'generic_name' => $product['generic'],
                'product_status' => Product::legacyStatusCode('In Stock'),
                'product_status_id' => $productStatusId,
                'slug' => Str::slug($product['name']) . '-' . Str::random(8),
                'keywords' => strtolower($product['name']) . ', medicine, sample',
                'alert_quantity' => $product['alert'],
                'reorder_level' => $product['reorder'],
                'company_id' => $companyIds[$product['company'] - 1],
                'formulation' => ucfirst($product['formulation']),
                'formulation_id' => $findDropdownId('formulation', ucfirst($product['formulation'])),
                'unit' => $product['unit'],
                'sale_unit_id' => $unitIds['Strip'] ?? reset($unitIds),
                'purchase_unit_id' => $unitIds['Box'] ?? reset($unitIds),
                'conversion rate' => 1,
                'discount' => 5,
                'display_price' => $product['mrp'] - (($product['mrp'] * 5) / 100),
                'is_active' => 1,
                'status' => 'Y',
                'purchase_price' => $product['purchase_price'],
                'profit_margin' => 18,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        
        $legacyPurchaseRows = [
            [
                'supplier_id' => $supplierIds[0],
                'invoice_no' => 'INV-1001',
                'purchase_date' => now()->subDays(40)->toDateString(),
                'order_status' => 'received',
                'paid_amount' => 1476,
                'items' => [
                    ['product_id' => $productIds[0], 'batch_no' => 'LEG-AMX-001', 'expiry_date' => now()->addDays(18)->format('Y-m'), 'quantity' => 8, 'purchase_price' => 102],
                    ['product_id' => $productIds[1], 'batch_no' => 'LEG-PAR-001', 'expiry_date' => now()->addMonths(10)->format('Y-m'), 'quantity' => 16, 'purchase_price' => 60],
                ],
            ],
            [
                'supplier_id' => $supplierIds[1],
                'invoice_no' => 'INV-1002',
                'purchase_date' => now()->subDays(25)->toDateString(),
                'order_status' => 'approved',
                'paid_amount' => 0,
                'items' => [
                    ['product_id' => $productIds[2], 'batch_no' => 'LEG-AZI-001', 'expiry_date' => now()->format('Y-m'), 'quantity' => 10, 'purchase_price' => 170],
                    ['product_id' => $productIds[4], 'batch_no' => 'LEG-VIT-001', 'expiry_date' => now()->format('Y-m'), 'quantity' => 7, 'purchase_price' => 250],
                ],
            ],
            [
                'supplier_id' => $supplierIds[2],
                'invoice_no' => 'INV-1003',
                'purchase_date' => now()->subDays(12)->toDateString(),
                'order_status' => 'received',
                'paid_amount' => 390,
                'items' => [
                    ['product_id' => $productIds[3], 'batch_no' => 'LEG-IBU-001', 'expiry_date' => now()->subDays(3)->format('Y-m'), 'quantity' => 5, 'purchase_price' => 72],
                    ['product_id' => $productIds[5], 'batch_no' => 'LEG-MET-001', 'expiry_date' => now()->addMonths(4)->format('Y-m'), 'quantity' => 9, 'purchase_price' => 132],
                ],
            ],
            [
                'supplier_id' => $supplierIds[0],
                'invoice_no' => 'INV-1004',
                'purchase_date' => now()->subMonths(2)->subDays(7)->toDateString(),
                'order_status' => 'pending',
                'paid_amount' => 0,
                'items' => [
                    ['product_id' => $productIds[6], 'batch_no' => 'LEG-AML-001', 'expiry_date' => now()->addMonths(7)->format('Y-m'), 'quantity' => 4, 'purchase_price' => 118],
                    ['product_id' => $productIds[7], 'batch_no' => 'LEG-ATO-001', 'expiry_date' => now()->addMonths(11)->format('Y-m'), 'quantity' => 1, 'purchase_price' => 188],
                ],
            ],
            [
                'supplier_id' => $supplierIds[1],
                'invoice_no' => 'INV-1005',
                'purchase_date' => now()->subMonths(4)->toDateString(),
                'order_status' => 'received',
                'paid_amount' => 1996,
                'items' => [
                    ['product_id' => $productIds[1], 'batch_no' => 'LEG-PAR-002', 'expiry_date' => now()->addMonths(5)->format('Y-m'), 'quantity' => 30, 'purchase_price' => 58],
                    ['product_id' => $productIds[5], 'batch_no' => 'LEG-MET-002', 'expiry_date' => now()->addMonths(9)->format('Y-m'), 'quantity' => 2, 'purchase_price' => 128],
                ],
            ],
            [
                'supplier_id' => $supplierIds[2],
                'invoice_no' => 'INV-1006',
                'purchase_date' => now()->subDays(4)->toDateString(),
                'order_status' => 'approved',
                'paid_amount' => 250,
                'items' => [
                    ['product_id' => $productIds[2], 'batch_no' => 'LEG-AZI-002', 'expiry_date' => now()->addDays(11)->format('Y-m'), 'quantity' => 4, 'purchase_price' => 168],
                    ['product_id' => $productIds[3], 'batch_no' => 'LEG-IBU-002', 'expiry_date' => now()->addDays(25)->format('Y-m'), 'quantity' => 6, 'purchase_price' => 74],
                ],
            ],
        ];

        foreach ($legacyPurchaseRows as $index => $purchaseRow) {
            $reference = PurchaseReference::query()->create([
                'reference_no' => 'PUR-DEMO-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'used' => 'Y',
            ]);

            $grandTotal = collect($purchaseRow['items'])->sum(function ($item) {
                return $item['quantity'] * $item['purchase_price'];
            });

            $purchase = Purchase::query()->create([
                'supplier_id' => $purchaseRow['supplier_id'],
                'reference_id' => $reference->id,
                'invoice_no' => $purchaseRow['invoice_no'],
                'purchase_date' => $purchaseRow['purchase_date'],
                'order_status' => $purchaseRow['order_status'],
                'payment_mode_id' => (float) $purchaseRow['paid_amount'] > 0
                    ? $paymentModeIdFromLegacy(($index % 2 === 0) ? 'bank' : 'cash')
                    : $paymentModeIdFromLegacy('cash'),
                'grand_total' => $grandTotal,
                'paid_amount' => $purchaseRow['paid_amount'],
                'payment_status' => Purchase::resolvePaymentStatus($grandTotal, (float) $purchaseRow['paid_amount']),
                'remarks' => 'Demo purchase entry',
                'status' => 'Y',
            ]);

            foreach ($purchaseRow['items'] as $item) {
                $product = Product::query()->find($item['product_id']);
                $purchaseExpiryDate = preg_match('/^\d{4}-\d{2}$/', (string) $item['expiry_date']) === 1
                    ? Carbon::createFromFormat('Y-m', $item['expiry_date'])->endOfMonth()->toDateString()
                    : Carbon::parse($item['expiry_date'])->toDateString();
                $lineAmount = round($item['quantity'] * $item['purchase_price'], 2);

                ProductBatch::query()->create([
                    'product_id' => $item['product_id'],
                    'batch_no' => $item['batch_no'],
                    'expiry_date' => $item['expiry_date'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'subtotal' => $lineAmount,
                    'status' => 'Y',
                    'supplier_id' => $purchaseRow['supplier_id'],
                    'reference_id' => $reference->id,
                ]);

                PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'batch_id' => null,
                    'batch_no' => $item['batch_no'],
                    'expiry_date' => $purchaseExpiryDate,
                    'quantity' => $item['quantity'],
                    'free_qty' => 0,
                    'mrp' => round((float) ($product?->mrp ?? 0), 2),
                    'rate' => round((float) $item['purchase_price'], 2),
                    'cc_rate' => round((float) ($product?->cc_rate ?? 0), 2),
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'free_goods_value' => 0,
                    'amount' => $lineAmount,
                ]);
            }
        }

        $briefBatchRows = [
            ['product' => 0, 'supplier' => 0, 'batch' => 'BATCH-IBU-001', 'expiry' => now()->subDays(6)->toDateString(), 'received' => 5, 'available' => 5, 'price' => 78, 'storage' => 'Rack A-1'],
            ['product' => 1, 'supplier' => 1, 'batch' => 'BATCH-PAR-001', 'expiry' => now()->subDays(2)->toDateString(), 'received' => 8, 'available' => 8, 'price' => 60, 'storage' => 'Rack A-2'],
            ['product' => 2, 'supplier' => 1, 'batch' => 'BATCH-AMX-001', 'expiry' => now()->addDays(4)->toDateString(), 'received' => 12, 'available' => 4, 'price' => 102, 'storage' => 'Rack B-1'],
            ['product' => 3, 'supplier' => 2, 'batch' => 'BATCH-AZI-001', 'expiry' => now()->addDays(5)->toDateString(), 'received' => 10, 'available' => 3, 'price' => 170, 'storage' => 'Rack B-2'],
            ['product' => 4, 'supplier' => 0, 'batch' => 'BATCH-VIT-001', 'expiry' => now()->addDays(14)->toDateString(), 'received' => 7, 'available' => 7, 'price' => 250, 'storage' => 'Rack C-1'],
            ['product' => 5, 'supplier' => 1, 'batch' => 'BATCH-MET-001', 'expiry' => now()->addDays(21)->toDateString(), 'received' => 9, 'available' => 9, 'price' => 132, 'storage' => 'Rack C-2'],
            ['product' => 6, 'supplier' => 2, 'batch' => 'BATCH-AML-001', 'expiry' => now()->addDays(29)->toDateString(), 'received' => 10, 'available' => 10, 'price' => 120, 'storage' => 'Rack D-1'],
            ['product' => 7, 'supplier' => 0, 'batch' => 'BATCH-ATO-001', 'expiry' => now()->addMonths(5)->toDateString(), 'received' => 16, 'available' => 16, 'price' => 190, 'storage' => 'Rack D-2'],
            ['product' => 0, 'supplier' => 1, 'batch' => 'BATCH-IBU-002', 'expiry' => now()->addMonths(7)->toDateString(), 'received' => 20, 'available' => 20, 'price' => 76, 'storage' => 'Rack E-1'],
            ['product' => 2, 'supplier' => 2, 'batch' => 'BATCH-AMX-002', 'expiry' => now()->addMonths(9)->toDateString(), 'received' => 24, 'available' => 24, 'price' => 100, 'storage' => 'Rack E-2'],
            ['product' => 4, 'supplier' => 0, 'batch' => 'BATCH-VIT-002', 'expiry' => now()->addMonths(11)->toDateString(), 'received' => 6, 'available' => 6, 'price' => 248, 'storage' => 'Rack F-1'],
            ['product' => 5, 'supplier' => 1, 'batch' => 'BATCH-MET-002', 'expiry' => now()->addMonths(12)->toDateString(), 'received' => 11, 'available' => 11, 'price' => 130, 'storage' => 'Rack F-2'],
        ];

        foreach ($briefBatchRows as $row) {
            Batch::query()->create([
                'product_id' => $productIds[$row['product']],
                'supplier_id' => $supplierIds[$row['supplier']],
                'batch_number' => $row['batch'],
                'manufacturing_date' => now()->subMonths(1)->toDateString(),
                'expiry_date' => $row['expiry'],
                'quantity_received' => $row['received'],
                'quantity_available' => $row['available'],
                'purchase_price' => $row['price'],
                'storage_location' => $row['storage'],
                'is_active' => true,
            ]);
        }

        $customerIds = [];
        foreach ([
            ['name' => 'Kathmandu Clinic Pvt. Ltd.', 'party_type' => 'institution', 'contact_person' => 'Dr. Sita Sharma', 'phone' => '9841010101', 'email' => 'clinic@example.com', 'credit_limit' => 50000, 'opening_balance' => 8000, 'current_balance' => 8000, 'address' => 'Kathmandu'],
            ['name' => 'Sunlight Pharmacy', 'party_type' => 'customer', 'contact_person' => 'Ram Shrestha', 'phone' => '9852020202', 'email' => 'sunlight@example.com', 'credit_limit' => 20000, 'opening_balance' => 0, 'current_balance' => 0, 'address' => 'Lalitpur'],
            ['name' => 'Everest Hospital Store', 'party_type' => 'institution', 'contact_person' => 'Mina KC', 'phone' => '9863030303', 'email' => 'hospital@example.com', 'credit_limit' => 80000, 'opening_balance' => 12000, 'current_balance' => 12000, 'address' => 'Bhaktapur'],
            ['name' => 'Local Meds Retail', 'party_type' => 'customer', 'contact_person' => 'Bikash Lama', 'phone' => '9874040404', 'email' => 'local@example.com', 'credit_limit' => 10000, 'opening_balance' => 0, 'current_balance' => 0, 'address' => 'Pokhara'],
        ] as $customer) {
            $customerIds[] = DB::table('customers')->insertGetId([
                'name' => $customer['name'],
                'party_type' => $customer['party_type'],
                'contact_person' => $customer['contact_person'],
                'phone' => $customer['phone'],
                'email' => $customer['email'],
                'address' => $customer['address'],
                'credit_limit' => $customer['credit_limit'],
                'opening_balance' => $customer['opening_balance'],
                'current_balance' => $customer['current_balance'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // create demo users before orders so we can safely link "ordered by"
        $admin = User::updateOrCreate(
            ['email' => 'admin@pharmacy.com'],
            [
                'name' => 'Admin User',
                'password' => 'admin12345',
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['admin']);

        $staff = User::updateOrCreate(
            ['email' => 'staff@email.com'],
            [
                'name' => 'Sovit Gautam',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $staff->syncRoles(['staff']);

        $procurement = User::updateOrCreate(
            ['email' => 'procurement@email.com'],
            [
                'name' => 'Sheetal Aryal',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $procurement->syncRoles(['procurement']);

        $adminId = $admin->id;

        $findSaleBatch = function (int $productId, float $quantity) {
            $batch = Batch::query()
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->orderBy('expiry_date')
                ->get()
                ->first(function ($item) use ($quantity) {
                    return (float) $item->quantity_available >= $quantity;
                });

            if (!$batch) {
                throw new \RuntimeException('Seed stock is not enough for sales demo data.');
            }

            return $batch;
        };

        $salesInvoiceRows = [
            [
                'customer_id' => $customerIds[1],
                'invoice_date' => now()->subDays(6)->toDateString(),
                'sale_type' => 'retail',
                'payment_method' => 'cash',
                'paid_amount' => 550,
                'notes' => 'Retail billing demo',
                'items' => [
                    ['product_id' => $productIds[0], 'quantity' => 2, 'free_qty' => 1, 'unit_price' => 105, 'discount_percent' => 5],
                    ['product_id' => $productIds[4], 'quantity' => 1, 'free_qty' => 0, 'unit_price' => 300, 'discount_percent' => 0],
                ],
            ],
            [
                'customer_id' => $customerIds[0],
                'invoice_date' => now()->subDays(4)->toDateString(),
                'sale_type' => 'credit',
                'payment_method' => 'bank',
                'paid_amount' => 500,
                'notes' => 'Credit sale for clinic',
                'items' => [
                    ['product_id' => $productIds[2], 'quantity' => 2, 'free_qty' => 0, 'unit_price' => 140, 'discount_percent' => 3],
                    ['product_id' => $productIds[6], 'quantity' => 3, 'free_qty' => 1, 'unit_price' => 160, 'discount_percent' => 0],
                ],
            ],
            [
                'customer_id' => $customerIds[2],
                'invoice_date' => now()->subDays(2)->toDateString(),
                'sale_type' => 'wholesale',
                'payment_method' => 'bank',
                'paid_amount' => 0,
                'notes' => 'Wholesale invoice for hospital store',
                'items' => [
                    ['product_id' => $productIds[1], 'quantity' => 4, 'free_qty' => 0, 'unit_price' => 90, 'discount_percent' => 0],
                    ['product_id' => $productIds[3], 'quantity' => 1, 'free_qty' => 0, 'unit_price' => 210, 'discount_percent' => 0],
                ],
            ],
            [
                'customer_id' => $customerIds[3],
                'invoice_date' => now()->subDay()->toDateString(),
                'sale_type' => 'retail',
                'payment_method' => 'cash',
                'paid_amount' => 620,
                'notes' => 'Retail cash sale',
                'items' => [
                    ['product_id' => $productIds[5], 'quantity' => 2, 'free_qty' => 0, 'unit_price' => 175, 'discount_percent' => 0],
                    ['product_id' => $productIds[7], 'quantity' => 1, 'free_qty' => 1, 'unit_price' => 250, 'discount_percent' => 0],
                ],
            ],
        ];

        $createdSalesInvoices = [];

        foreach ($salesInvoiceRows as $row) {
            $subtotal = 0;
            $discountAmount = 0;
            $totalAmount = 0;
            $saleTypeName = ucfirst(strtolower((string) $row['sale_type']));
            $paymentModeId = $paymentModeIdFromLegacy($row['payment_method']);
            $paymentMode = $paymentModeId ? DropdownOption::query()->find($paymentModeId) : null;

            $invoice = SalesInvoice::create([
                'reference' => SalesInvoice::makeReference(),
                'customer_id' => $row['customer_id'],
                'sold_by' => $adminId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'invoice_date' => $row['invoice_date'],
                'sale_type' => $row['sale_type'],
                'sale_type_id' => $findDropdownId('sales_type', $saleTypeName),
                'status' => 'confirmed',
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMode?->data ?: strtolower((string) $row['payment_method']),
                'payment_mode_id' => $paymentModeId,
                'subtotal' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'paid_amount' => $row['paid_amount'],
                'notes' => $row['notes'],
                'confirmed_at' => now(),
            ]);

            foreach ($row['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $saleQuantity = $item['quantity'] + ($item['free_qty'] ?? 0);
                $batch = $findSaleBatch($item['product_id'], $saleQuantity);
                $lineBase = round($item['quantity'] * $item['unit_price'], 2);
                $lineDiscount = round(($lineBase * $item['discount_percent']) / 100, 2);
                $lineTotal = round($lineBase - $lineDiscount, 2);
                $freeGoodsValue = round((float) ($item['free_qty'] ?? 0) * (((float) ($product->mrp ?? 0) * (float) ($product->cc_rate ?? 0)) / 100), 2);

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'batch_id' => $batch->id,
                    'quantity' => $item['quantity'],
                    'free_qty' => $item['free_qty'] ?? 0,
                    'unit_price' => $item['unit_price'],
                    'mrp' => $product->mrp ?? 0,
                    'cc_rate' => $product->cc_rate ?? 0,
                    'discount_percent' => $item['discount_percent'],
                    'free_goods_value' => $freeGoodsValue,
                    'subtotal' => $lineTotal,
                ]);

                $batch->quantity_available = max(0, (float) $batch->quantity_available - (float) $saleQuantity);
                $batch->save();

                $subtotal += $lineBase;
                $discountAmount += $lineDiscount;
                $totalAmount += $lineTotal;
            }

            $invoice->update([
                'subtotal' => round($subtotal, 2),
                'discount_amount' => round($discountAmount, 2),
                'total_amount' => round($totalAmount, 2),
                'payment_status' => SalesInvoice::resolvePaymentStatus($totalAmount, (float) $row['paid_amount']),
            ]);

            $customer = Customer::query()->find($row['customer_id']);
            if ($customer) {
                $customer->current_balance = round((float) $customer->current_balance + $invoice->due_amount, 2);
                $customer->save();
            }

            if ((float) $row['paid_amount'] > 0) {
                record_account_transaction([
                    'transaction_date' => $invoice->invoice_date,
                    'reference_type' => 'SalesInvoice',
                    'reference_id' => $invoice->id,
                    'party_type' => 'customer',
                    'party_id' => $invoice->customer_id,
                    'entry_type' => 'debit',
                    'account_type' => ($paymentMode?->data === 'cash') ? 'cash' : 'bank',
                    'amount' => $row['paid_amount'],
                    'notes' => 'Demo sale payment for ' . $invoice->reference,
                    'created_by' => $adminId,
                ]);
            }

            if ($invoice->due_amount > 0) {
                record_account_transaction([
                    'transaction_date' => $invoice->invoice_date,
                    'reference_type' => 'SalesInvoice',
                    'reference_id' => $invoice->id,
                    'party_type' => 'customer',
                    'party_id' => $invoice->customer_id,
                    'entry_type' => 'debit',
                    'account_type' => 'receivable',
                    'amount' => $invoice->due_amount,
                    'notes' => 'Demo sale due for ' . $invoice->reference,
                    'created_by' => $adminId,
                ]);
            }

            record_account_transaction([
                'transaction_date' => $invoice->invoice_date,
                'reference_type' => 'SalesInvoice',
                'reference_id' => $invoice->id,
                'party_type' => 'customer',
                'party_id' => $invoice->customer_id,
                'entry_type' => 'credit',
                'account_type' => 'income',
                'amount' => $invoice->total_amount,
                'notes' => 'Demo sales income for ' . $invoice->reference,
                'created_by' => $adminId,
            ]);

            $createdSalesInvoices[] = $invoice;
        }

        $firstInvoice = $createdSalesInvoices[0] ?? null;
        if ($firstInvoice) {
            $firstItem = $firstInvoice->items()->first();

            if ($firstItem) {
                $returnQuantity = 1;
                $refundAmount = round($returnQuantity * (float) $firstItem->unit_price, 2);

                if ($firstItem->batch) {
                    $firstItem->batch->quantity_available = round((float) $firstItem->batch->quantity_available + $returnQuantity, 2);
                    $firstItem->batch->save();
                }

                SalesReturn::create([
                    'sales_invoice_id' => $firstInvoice->id,
                    'sales_invoice_item_id' => $firstItem->id,
                    'product_id' => $firstItem->product_id,
                    'batch_id' => $firstItem->batch_id,
                    'created_by' => $adminId,
                    'return_date' => now()->subDay()->toDateString(),
                    'quantity' => $returnQuantity,
                    'refund_amount' => $refundAmount,
                    'reason' => 'Demo return',
                    'notes' => 'Customer returned one pack',
                ]);

                $customer = Customer::query()->find($firstInvoice->customer_id);
                if ($customer) {
                    $customer->current_balance = max(0, round((float) $customer->current_balance - $refundAmount, 2));
                    $customer->save();
                }

                record_account_transaction([
                    'transaction_date' => now()->subDay()->toDateString(),
                    'reference_type' => 'SalesReturn',
                    'reference_id' => $firstInvoice->id,
                    'party_type' => 'customer',
                    'party_id' => $firstInvoice->customer_id,
                    'entry_type' => 'debit',
                    'account_type' => 'income',
                    'amount' => $refundAmount,
                    'notes' => 'Demo sales return for ' . $firstInvoice->reference,
                    'created_by' => $adminId,
                ]);

                record_account_transaction([
                    'transaction_date' => now()->subDay()->toDateString(),
                    'reference_type' => 'SalesReturn',
                    'reference_id' => $firstInvoice->id,
                    'party_type' => 'customer',
                    'party_id' => $firstInvoice->customer_id,
                    'entry_type' => 'credit',
                    'account_type' => 'cash',
                    'amount' => $refundAmount,
                    'notes' => 'Demo refund for ' . $firstInvoice->reference,
                    'created_by' => $adminId,
                ]);
            }
        }

        foreach ([
            ['date' => now()->subDays(8)->toDateString(), 'category' => 'Salary', 'vendor' => 'Pharmacy Team', 'payment_mode' => 'cash', 'amount' => 15000, 'notes' => 'Monthly staff payroll'],
            ['date' => now()->subDays(6)->toDateString(), 'category' => 'Fuel', 'vendor' => 'City Fuel Station', 'payment_mode' => 'cash', 'amount' => 2200, 'notes' => 'Delivery fuel expense'],
            ['date' => now()->subDays(5)->toDateString(), 'category' => 'Utilities', 'vendor' => 'NEA', 'payment_mode' => 'bank', 'amount' => 3800, 'notes' => 'Electricity bill paid online'],
            ['date' => now()->subDays(3)->toDateString(), 'category' => 'Office', 'vendor' => 'Stationery Hub', 'payment_mode' => 'bank', 'amount' => 1200, 'notes' => 'Stationery and office items'],
        ] as $expenseRow) {
            $paymentModeId = $paymentModeIdFromLegacy($expenseRow['payment_mode']);
            $paymentMode = $paymentModeId ? DropdownOption::query()->find($paymentModeId) : null;
            $expenseCategoryId = $expenseCategoryIdFromLegacy($expenseRow['category']);
            $expenseCategoryName = DropdownOption::query()->find($expenseCategoryId)?->name ?? 'Miscellaneous';

            $expense = Expense::create([
                'expense_date' => $expenseRow['date'],
                'expense_category_id' => $expenseCategoryId,
                'category' => $expenseCategoryName,
                'vendor_name' => $expenseRow['vendor'],
                'payment_mode_id' => $paymentModeId,
                'payment_mode' => $paymentMode?->data ?: 'cash',
                'amount' => $expenseRow['amount'],
                'notes' => $expenseRow['notes'],
                'created_by' => $adminId,
            ]);

            record_account_transaction([
                'transaction_date' => $expense->expense_date,
                'reference_type' => 'Expense',
                'reference_id' => $expense->id,
                'entry_type' => 'debit',
                'account_type' => 'expense',
                'amount' => $expense->amount,
                'notes' => 'Demo expense for ' . $expense->category,
                'created_by' => $adminId,
            ]);

            record_account_transaction([
                'transaction_date' => $expense->expense_date,
                'reference_type' => 'Expense',
                'reference_id' => $expense->id,
                'entry_type' => 'credit',
                'account_type' => ($paymentMode?->data === 'cash') ? 'cash' : 'bank',
                'amount' => $expense->amount,
                'notes' => 'Demo expense payment via ' . ($paymentMode?->name ?? 'Cash'),
                'created_by' => $adminId,
            ]);
        }

        // These recent purchase orders are used by dashboard cards and normal daily flow screens.
        $purchaseOrderRows = [
            [
                'supplier_id' => $supplierIds[0],
                'order_date' => now()->subDays(12)->toDateString(),
                'expected_delivery_date' => now()->addDays(5)->toDateString(),
                'status' => 'received',
                'payment_status' => 'paid',
                'paid_amount' => 3240,
                'notes' => 'Received and closed',
                'items' => [
                    ['product_id' => $productIds[0], 'quantity' => 20, 'unit_price' => 78],
                    ['product_id' => $productIds[2], 'quantity' => 10, 'unit_price' => 102],
                ],
            ],
            [
                'supplier_id' => $supplierIds[1],
                'order_date' => now()->subDays(10)->toDateString(),
                'expected_delivery_date' => now()->addDays(7)->toDateString(),
                'status' => 'received',
                'payment_status' => 'paid',
                'paid_amount' => 2780,
                'notes' => 'Paid after receiving stock',
                'items' => [
                    ['product_id' => $productIds[1], 'quantity' => 18, 'unit_price' => 60],
                    ['product_id' => $productIds[4], 'quantity' => 6, 'unit_price' => 250],
                ],
            ],
            [
                'supplier_id' => $supplierIds[2],
                'order_date' => now()->subDays(8)->toDateString(),
                'expected_delivery_date' => now()->addDays(10)->toDateString(),
                'status' => 'received',
                'payment_status' => 'unpaid',
                'paid_amount' => 0,
                'notes' => 'Pending vendor settlement',
                'items' => [
                    ['product_id' => $productIds[3], 'quantity' => 12, 'unit_price' => 170],
                    ['product_id' => $productIds[5], 'quantity' => 8, 'unit_price' => 132],
                ],
            ],
            [
                'supplier_id' => $supplierIds[0],
                'order_date' => now()->subDays(4)->toDateString(),
                'expected_delivery_date' => now()->addDays(12)->toDateString(),
                'status' => 'approved',
                'payment_status' => 'unpaid',
                'paid_amount' => 0,
                'notes' => 'Approved but not received yet',
                'items' => [
                    ['product_id' => $productIds[6], 'quantity' => 14, 'unit_price' => 120],
                    ['product_id' => $productIds[7], 'quantity' => 10, 'unit_price' => 190],
                ],
            ],
            [
                'supplier_id' => $supplierIds[1],
                'order_date' => now()->subDay()->toDateString(),
                'expected_delivery_date' => now()->addDays(15)->toDateString(),
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'paid_amount' => 0,
                'notes' => 'Waiting for approval',
                'items' => [
                    ['product_id' => $productIds[2], 'quantity' => 16, 'unit_price' => 100],
                    ['product_id' => $productIds[4], 'quantity' => 4, 'unit_price' => 248],
                ],
            ],
        ];

        foreach ($purchaseOrderRows as $index => $orderRow) {
            $order = PurchaseOrder::query()->create([
                'reference' => PurchaseOrder::makeReference(),
                'supplier_id' => $orderRow['supplier_id'],
                'ordered_by' => $adminId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'order_date' => $orderRow['order_date'],
                'expected_delivery_date' => $orderRow['expected_delivery_date'],
                'status' => $orderRow['status'],
                'payment_status' => $orderRow['payment_status'],
                'notes' => $orderRow['notes'],
                'total_amount' => 0,
                'paid_amount' => $orderRow['paid_amount'],
            ]);

            $grandTotal = 0;

            foreach ($orderRow['items'] as $item) {
                $subtotal = round($item['quantity'] * $item['unit_price'], 2);
                $grandTotal += $subtotal;

                PurchaseOrderItem::query()->create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity'],
                    'quantity_received' => $orderRow['status'] === 'received' ? $item['quantity'] : 0,
                    'unit_price' => $item['unit_price'],
                    'batch_number' => $orderRow['status'] === 'received' ? 'PO-' . Str::upper(Str::random(4)) : null,
                    'expiry_date' => $orderRow['status'] === 'received' ? now()->addMonths(6)->toDateString() : null,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update([
                'total_amount' => $grandTotal,
                'paid_amount' => $orderRow['paid_amount'],
            ]);
        }

        // Older finance and purchase history helps the reports feel real during viva/demo time.
        foreach (range(1, 5) as $yearOffset) {
            $historyBaseDate = now()->copy()->subYears($yearOffset)->setMonth(6)->setDay(12);
            $supplierIndex = ($yearOffset - 1) % count($supplierIds);
            $productIndex = ($yearOffset - 1) % count($productIds);
            $quantity = 10 + $yearOffset;
            $unitPrice = (float) ($productRows[$productIndex]['purchase_price'] ?? 100);
            $historicalTotal = round($quantity * $unitPrice, 2);

            $historicalOrder = PurchaseOrder::query()->create([
                'reference' => PurchaseOrder::makeReference(),
                'supplier_id' => $supplierIds[$supplierIndex],
                'ordered_by' => $adminId,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'order_date' => $historyBaseDate->toDateString(),
                'expected_delivery_date' => $historyBaseDate->copy()->addDays(6)->toDateString(),
                'status' => 'received',
                'payment_status' => 'paid',
                'notes' => 'Historical demo order kept for yearly reporting view.',
                'total_amount' => $historicalTotal,
                'paid_amount' => $historicalTotal,
                'received_at' => $historyBaseDate->copy()->addDays(3),
            ]);

            $historicalItem = PurchaseOrderItem::query()->create([
                'purchase_order_id' => $historicalOrder->id,
                'product_id' => $productIds[$productIndex],
                'quantity_ordered' => $quantity,
                'quantity_received' => $quantity,
                'unit_price' => $unitPrice,
                'batch_number' => 'HIST-' . $historyBaseDate->format('ym') . '-' . str_pad((string) $yearOffset, 2, '0', STR_PAD_LEFT),
                'expiry_date' => $historyBaseDate->copy()->addMonths(18)->toDateString(),
                'subtotal' => $historicalTotal,
            ]);

            Batch::query()->create([
                'product_id' => $productIds[$productIndex],
                'supplier_id' => $supplierIds[$supplierIndex],
                'purchase_order_item_id' => $historicalItem->id,
                'batch_number' => 'HIST-BATCH-' . $historyBaseDate->format('ymd') . '-' . $yearOffset,
                'manufacturing_date' => $historyBaseDate->copy()->subMonths(5)->toDateString(),
                'expiry_date' => $historyBaseDate->copy()->addMonths(18)->toDateString(),
                'quantity_received' => $quantity,
                'quantity_available' => 0,
                'purchase_price' => $unitPrice,
                'storage_location' => 'Archive Rack ' . $yearOffset,
                'is_active' => false,
            ]);

            $historicalExpense = Expense::create([
                'expense_date' => $historyBaseDate->copy()->addMonths(2)->toDateString(),
                'expense_category_id' => $expenseCategoryIdFromLegacy('Maintenance'),
                'category' => 'Maintenance',
                'vendor_name' => 'Historical Service Vendor ' . $yearOffset,
                'payment_mode_id' => $paymentModeIdFromLegacy($yearOffset % 2 === 0 ? 'bank' : 'cash'),
                'payment_mode' => $yearOffset % 2 === 0 ? 'bank' : 'cash',
                'amount' => 1400 + ($yearOffset * 250),
                'notes' => 'Older seeded expense for long term finance report demo.',
                'created_by' => $adminId,
            ]);

            record_account_transaction([
                'transaction_date' => $historicalExpense->expense_date,
                'reference_type' => 'Expense',
                'reference_id' => $historicalExpense->id,
                'entry_type' => 'debit',
                'account_type' => 'expense',
                'amount' => $historicalExpense->amount,
                'notes' => 'Historical expense record for year wise reporting.',
                'created_by' => $adminId,
            ]);

            record_account_transaction([
                'transaction_date' => $historicalExpense->expense_date,
                'reference_type' => 'Expense',
                'reference_id' => $historicalExpense->id,
                'entry_type' => 'credit',
                'account_type' => $historicalExpense->paymentModeOption?->data === 'cash' ? 'cash' : 'bank',
                'amount' => $historicalExpense->amount,
                'notes' => 'Historical expense payment entry.',
                'created_by' => $adminId,
            ]);

            record_account_transaction([
                'transaction_date' => $historyBaseDate->copy()->addMonths(3)->toDateString(),
                'reference_type' => 'HistoricalSale',
                'reference_id' => $yearOffset,
                'party_type' => 'customer',
                'party_id' => $customerIds[$yearOffset % count($customerIds)],
                'entry_type' => 'debit',
                'account_type' => $yearOffset % 2 === 0 ? 'bank' : 'cash',
                'amount' => 3800 + ($yearOffset * 420),
                'notes' => 'Historical received sale amount added for older finance trend.',
                'created_by' => $adminId,
            ]);

            record_account_transaction([
                'transaction_date' => $historyBaseDate->copy()->addMonths(3)->toDateString(),
                'reference_type' => 'HistoricalSale',
                'reference_id' => $yearOffset,
                'party_type' => 'customer',
                'party_id' => $customerIds[$yearOffset % count($customerIds)],
                'entry_type' => 'credit',
                'account_type' => 'income',
                'amount' => 3800 + ($yearOffset * 420),
                'notes' => 'Historical income entry added for yearly report view.',
                'created_by' => $adminId,
            ]);
        }
    }
}
