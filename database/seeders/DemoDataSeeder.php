<?php

namespace Database\Seeders;

use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseReference;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('categories')->count() > 0) {
            return;
        }

        $now = now();

        $categoryIds = [];
        foreach ([
            ['name' => 'Antibiotic', 'order_number' => 1],
            ['name' => 'Pain Relief', 'order_number' => 2],
            ['name' => 'Vitamin', 'order_number' => 3],
            ['name' => 'Diabetes Care', 'order_number' => 4],
        ] as $category) {
            $categoryIds[] = DB::table('categories')->insertGetId([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']) . '-' . Str::random(8),
                'order_number' => $category['order_number'],
                'status' => 'Y',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $unitIds = [];
        foreach ([
            ['unit_name' => 'Box', 'description' => 'Packed by box'],
            ['unit_name' => 'Strip', 'description' => 'Packed by strip'],
            ['unit_name' => 'Bottle', 'description' => 'Packed by bottle'],
        ] as $unit) {
            $unitIds[] = DB::table('units')->insertGetId([
                'unit_name' => $unit['unit_name'],
                'description' => $unit['description'],
                'status' => 'Y',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $supplierIds = [];
        foreach ([
            ['supplier_name' => 'Everest Medico', 'contact_person' => 'Ramesh Shrestha', 'phone_number' => '9801000001', 'email' => 'everest@example.com', 'pan_number' => 'PAN001', 'opening_balance' => '12000', 'address' => 'Kathmandu', 'type' => 'credit'],
            ['supplier_name' => 'Himal Pharma Link', 'contact_person' => 'Sita Adhikari', 'phone_number' => '9801000002', 'email' => 'himal@example.com', 'pan_number' => 'PAN002', 'opening_balance' => '8500', 'address' => 'Lalitpur', 'type' => 'credit'],
            ['supplier_name' => 'Care Nepal Distributors', 'contact_person' => 'Bikash Karki', 'phone_number' => '9801000003', 'email' => 'care@example.com', 'pan_number' => 'PAN003', 'opening_balance' => '6200', 'address' => 'Bhaktapur', 'type' => 'debit'],
        ] as $supplier) {
            $supplierIds[] = DB::table('suppliers')->insertGetId(array_merge($supplier, [
                'status' => 'Y',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $products = [
            ['name' => 'Amoxicillin 500mg', 'category' => $categoryIds[0], 'generic' => 'Amoxicillin', 'manufacturer' => 'ABC Pharma', 'mrp' => 145, 'purchase_price' => 102, 'alert_quantity' => 18],
            ['name' => 'Azithromycin 250mg', 'category' => $categoryIds[0], 'generic' => 'Azithromycin', 'manufacturer' => 'Nepal Remedies', 'mrp' => 220, 'purchase_price' => 170, 'alert_quantity' => 15],
            ['name' => 'Paracetamol 650mg', 'category' => $categoryIds[1], 'generic' => 'Paracetamol', 'manufacturer' => 'Health First', 'mrp' => 95, 'purchase_price' => 60, 'alert_quantity' => 20],
            ['name' => 'Ibuprofen 400mg', 'category' => $categoryIds[1], 'generic' => 'Ibuprofen', 'manufacturer' => 'Health First', 'mrp' => 110, 'purchase_price' => 72, 'alert_quantity' => 12],
            ['name' => 'Vitamin C 1000mg', 'category' => $categoryIds[2], 'generic' => 'Ascorbic Acid', 'manufacturer' => 'Nutri Life', 'mrp' => 310, 'purchase_price' => 250, 'alert_quantity' => 10],
            ['name' => 'Metformin 500mg', 'category' => $categoryIds[3], 'generic' => 'Metformin', 'manufacturer' => 'Life Care', 'mrp' => 180, 'purchase_price' => 132, 'alert_quantity' => 14],
        ];

        $productIds = [];
        foreach ($products as $index => $product) {
            $productIds[] = DB::table('products')->insertGetId([
                'product_name' => $product['name'],
                'composition' => $product['generic'],
                'group_name' => 'Medicine',
                'manufacturer' => $product['manufacturer'],
                'description' => $product['name'] . ' sample product for dashboard demo.',
                'previous_price' => $product['purchase_price'],
                'mrp' => $product['mrp'],
                'generic_name' => $product['generic'],
                'product_status' => 'instock',
                'slug' => Str::slug($product['name']) . '-' . Str::random(8),
                'keywords' => strtolower($product['name']) . ', medicine, sample',
                'order_number' => $index + 1,
                'alert_quantity' => $product['alert_quantity'],
                'category_id' => $product['category'],
                'sale_unit_id' => $unitIds[1],
                'purchase_unit_id' => $unitIds[0],
                'conversion rate' => 1,
                'discount' => 5,
                'display_price' => $product['mrp'] - (($product['mrp'] * 5) / 100),
                'status' => 'Y',
                'purchase_price' => $product['purchase_price'],
                'profit_margin' => 18,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $purchaseSeedRows = [
            [
                'supplier_id' => $supplierIds[0],
                'invoice_no' => 'INV-1001',
                'purchase_date' => now()->subDays(40)->toDateString(),
                'order_status' => 'received',
                'paid_amount' => 1476,
                'items' => [
                    ['product_id' => $productIds[0], 'batch_no' => 'AMX-001', 'expiry_date' => now()->addDays(18)->format('Y-m'), 'quantity' => 8, 'purchase_price' => 102],
                    ['product_id' => $productIds[2], 'batch_no' => 'PAR-001', 'expiry_date' => now()->addMonths(10)->format('Y-m'), 'quantity' => 16, 'purchase_price' => 60],
                ],
            ],
            [
                'supplier_id' => $supplierIds[1],
                'invoice_no' => 'INV-1002',
                'purchase_date' => now()->subDays(25)->toDateString(),
                'order_status' => 'approved',
                'paid_amount' => 0,
                'items' => [
                    ['product_id' => $productIds[1], 'batch_no' => 'AZI-001', 'expiry_date' => now()->format('Y-m'), 'quantity' => 10, 'purchase_price' => 170],
                    ['product_id' => $productIds[4], 'batch_no' => 'VIT-001', 'expiry_date' => now()->format('Y-m'), 'quantity' => 7, 'purchase_price' => 250],
                ],
            ],
            [
                'supplier_id' => $supplierIds[2],
                'invoice_no' => 'INV-1003',
                'purchase_date' => now()->subDays(12)->toDateString(),
                'order_status' => 'received',
                'paid_amount' => 390,
                'items' => [
                    ['product_id' => $productIds[3], 'batch_no' => 'IBU-001', 'expiry_date' => now()->subDays(3)->format('Y-m'), 'quantity' => 5, 'purchase_price' => 72],
                    ['product_id' => $productIds[5], 'batch_no' => 'MET-001', 'expiry_date' => now()->addMonths(4)->format('Y-m'), 'quantity' => 9, 'purchase_price' => 132],
                ],
            ],
            [
                'supplier_id' => $supplierIds[0],
                'invoice_no' => 'INV-1004',
                'purchase_date' => now()->subMonths(2)->subDays(7)->toDateString(),
                'order_status' => 'pending',
                'paid_amount' => 0,
                'items' => [
                    ['product_id' => $productIds[0], 'batch_no' => 'AMX-002', 'expiry_date' => now()->addMonths(7)->format('Y-m'), 'quantity' => 4, 'purchase_price' => 100],
                    ['product_id' => $productIds[4], 'batch_no' => 'VIT-002', 'expiry_date' => now()->addMonths(11)->format('Y-m'), 'quantity' => 1, 'purchase_price' => 248],
                ],
            ],
            [
                'supplier_id' => $supplierIds[1],
                'invoice_no' => 'INV-1005',
                'purchase_date' => now()->subMonths(4)->toDateString(),
                'order_status' => 'received',
                'paid_amount' => 1996,
                'items' => [
                    ['product_id' => $productIds[2], 'batch_no' => 'PAR-002', 'expiry_date' => now()->addMonths(5)->format('Y-m'), 'quantity' => 30, 'purchase_price' => 58],
                    ['product_id' => $productIds[5], 'batch_no' => 'MET-002', 'expiry_date' => now()->addMonths(9)->format('Y-m'), 'quantity' => 2, 'purchase_price' => 128],
                ],
            ],
            [
                'supplier_id' => $supplierIds[2],
                'invoice_no' => 'INV-1006',
                'purchase_date' => now()->subDays(4)->toDateString(),
                'order_status' => 'approved',
                'paid_amount' => 250,
                'items' => [
                    ['product_id' => $productIds[1], 'batch_no' => 'AZI-002', 'expiry_date' => now()->addDays(11)->format('Y-m'), 'quantity' => 4, 'purchase_price' => 168],
                    ['product_id' => $productIds[3], 'batch_no' => 'IBU-002', 'expiry_date' => now()->addDays(25)->format('Y-m'), 'quantity' => 6, 'purchase_price' => 74],
                ],
            ],
        ];

        foreach ($purchaseSeedRows as $index => $purchaseRow) {
            $referenceId = PurchaseReference::query()->insertGetId([
                'reference_no' => 'PUR-DEMO-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'used' => 'Y',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $grandTotal = collect($purchaseRow['items'])->sum(function ($item) {
                return $item['quantity'] * $item['purchase_price'];
            });

            Purchase::query()->create([
                'supplier_id' => $purchaseRow['supplier_id'],
                'reference_id' => $referenceId,
                'invoice_no' => $purchaseRow['invoice_no'],
                'purchase_date' => $purchaseRow['purchase_date'],
                'order_status' => $purchaseRow['order_status'],
                'grand_total' => $grandTotal,
                'paid_amount' => $purchaseRow['paid_amount'],
                'payment_status' => Purchase::resolvePaymentStatus($grandTotal, (float) $purchaseRow['paid_amount']),
                'remarks' => 'Demo purchase entry',
                'status' => 'Y',
            ]);

            foreach ($purchaseRow['items'] as $item) {
                ProductBatch::query()->create([
                    'product_id' => $item['product_id'],
                    'batch_no' => $item['batch_no'],
                    'expiry_date' => $item['expiry_date'],
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'subtotal' => $item['quantity'] * $item['purchase_price'],
                    'status' => 'Y',
                    'supplier_id' => $purchaseRow['supplier_id'],
                    'reference_id' => $referenceId,
                ]);
            }
        }

        $staff = User::updateOrCreate(
            ['email' => 'staff@pharmacy.com'],
            [
                'name' => 'Staff User',
                'password' => 'staff12345',
            ]
        );

        $staff->syncRoles(['staff']);
    }
}
