<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
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

        // keep a small unit list because the old product page still depends on it
        $unitIds = [];
        foreach ([
            ['unit_name' => 'Strip', 'description' => 'Tablet strip'],
            ['unit_name' => 'Bottle', 'description' => 'Liquid bottle'],
            ['unit_name' => 'Vial', 'description' => 'Injection vial'],
            ['unit_name' => 'Tube', 'description' => 'Cream tube'],
        ] as $unit) {
            $unitIds[] = DB::table('units')->insertGetId([
                'unit_name' => $unit['unit_name'],
                'description' => $unit['description'],
                'status' => 'Y',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $categoryIds = [];
        foreach ([
            ['name' => 'Pain Relief', 'order_number' => 1],
            ['name' => 'Antibiotic', 'order_number' => 2],
            ['name' => 'Vitamin', 'order_number' => 3],
            ['name' => 'Diabetes Care', 'order_number' => 4],
            ['name' => 'Cardiovascular', 'order_number' => 5],
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

        $productRows = [
            ['name' => 'Ibuprofen 400mg', 'generic' => 'Ibuprofen', 'category' => 1, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 12, 'mrp' => 110, 'purchase_price' => 78, 'alert' => 12, 'manufacturer' => 'Himalaya Labs'],
            ['name' => 'Paracetamol 500mg', 'generic' => 'Paracetamol', 'category' => 1, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 20, 'mrp' => 95, 'purchase_price' => 60, 'alert' => 20, 'manufacturer' => 'Health First'],
            ['name' => 'Amoxicillin 500mg', 'generic' => 'Amoxicillin', 'category' => 2, 'formulation' => 'capsule', 'unit' => 'Strip', 'reorder' => 18, 'mrp' => 145, 'purchase_price' => 102, 'alert' => 18, 'manufacturer' => 'ABC Pharma'],
            ['name' => 'Azithromycin 250mg', 'generic' => 'Azithromycin', 'category' => 2, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 15, 'mrp' => 220, 'purchase_price' => 170, 'alert' => 15, 'manufacturer' => 'Nepal Remedies'],
            ['name' => 'Vitamin C 1000mg', 'generic' => 'Ascorbic Acid', 'category' => 3, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 10, 'mrp' => 310, 'purchase_price' => 250, 'alert' => 10, 'manufacturer' => 'Nutri Life'],
            ['name' => 'Metformin 500mg', 'generic' => 'Metformin', 'category' => 4, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 14, 'mrp' => 180, 'purchase_price' => 132, 'alert' => 14, 'manufacturer' => 'Life Care'],
            ['name' => 'Amlodipine 5mg', 'generic' => 'Amlodipine', 'category' => 5, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 10, 'mrp' => 165, 'purchase_price' => 120, 'alert' => 10, 'manufacturer' => 'Heart Care Pharma'],
            ['name' => 'Atorvastatin 10mg', 'generic' => 'Atorvastatin', 'category' => 5, 'formulation' => 'tablet', 'unit' => 'Strip', 'reorder' => 8, 'mrp' => 265, 'purchase_price' => 190, 'alert' => 8, 'manufacturer' => 'Cardio Care'],
        ];

        $productIds = [];
        foreach ($productRows as $index => $product) {
            $productIds[] = DB::table('products')->insertGetId([
                'name' => $product['name'],
                'product_name' => $product['name'],
                'composition' => $product['generic'],
                'group_name' => 'Medicine',
                'manufacturer' => $product['manufacturer'],
                'description' => $product['name'] . ' sample record for pharmacy demo.',
                'previous_price' => $product['purchase_price'],
                'mrp' => $product['mrp'],
                'generic_name' => $product['generic'],
                'product_status' => 'instock',
                'slug' => Str::slug($product['name']) . '-' . Str::random(8),
                'keywords' => strtolower($product['name']) . ', medicine, sample',
                'order_number' => $index + 1,
                'alert_quantity' => $product['alert'],
                'reorder_level' => $product['reorder'],
                'category_id' => $categoryIds[$product['category'] - 1],
                'formulation' => $product['formulation'],
                'unit' => $product['unit'],
                'sale_unit_id' => $unitIds[0],
                'purchase_unit_id' => $unitIds[0],
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

        // old purchase flow still exists, so we keep it alive with legacy tables too
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

            Purchase::query()->create([
                'supplier_id' => $purchaseRow['supplier_id'],
                'reference_id' => $reference->id,
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
                    'reference_id' => $reference->id,
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
    }
}
