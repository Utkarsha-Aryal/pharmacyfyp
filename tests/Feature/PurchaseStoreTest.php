<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Company;
use App\Models\DropdownOption;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReference;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_unpaid_purchase_without_payment_mode(): void
    {
        $user = User::factory()->create();
        $supplier = $this->createSupplier();
        $reference = PurchaseReference::query()->create([
            'reference_no' => 'PUR-260516-0001-AA',
            'used' => 'N',
        ]);
        $product = $this->createProduct();

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->postJson(route('admin.purchase.save'), [
                'supplier_id' => $supplier->id,
                'reference_id' => $reference->id,
                'invoice_no' => 'SUP-101',
                'purchase_date' => '2026-05-16',
                'paid_amount' => 0,
                'remarks' => 'Simple unpaid purchase test.',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'batch_no' => 'PUR-BATCH-01',
                        'expiry_date' => '2027-02-01',
                        'quantity' => 3,
                        'free_qty' => 1,
                        'mrp' => 150,
                        'purchase_price' => 100,
                        'cc_rate' => 5,
                        'discount_percent' => 10,
                    ],
                ],
            ]);

        $response->assertOk()->assertJson([
            'type' => 'success',
        ]);

        $purchase = Purchase::query()->first();
        $purchaseItem = PurchaseItem::query()->first();
        $batch = Batch::query()->first();

        $this->assertNotNull($purchase);
        $this->assertSame('unpaid', $purchase->payment_status);
        $this->assertNull($purchase->payment_mode_id);
        $this->assertSame(270.0, (float) $purchase->grand_total);
        $this->assertSame('Y', $reference->fresh()->used);
        $this->assertNotNull($purchaseItem);
        $this->assertSame(270.0, (float) $purchaseItem->amount);
        $this->assertNotNull($batch);
        $this->assertSame(4, (int) $batch->quantity_available);
        $this->assertDatabaseCount('product_batches', 1);
    }

    public function test_it_rejects_purchase_when_paid_amount_is_more_than_bill_total(): void
    {
        $user = User::factory()->create();
        $supplier = $this->createSupplier();
        $reference = PurchaseReference::query()->create([
            'reference_no' => 'PUR-260516-0002-BB',
            'used' => 'N',
        ]);
        $paymentMode = $this->createDropdown('payment_mode', 'Cash', 'cash');
        $product = $this->createProduct();

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->postJson(route('admin.purchase.save'), [
                'supplier_id' => $supplier->id,
                'reference_id' => $reference->id,
                'invoice_no' => 'SUP-102',
                'purchase_date' => '2026-05-16',
                'paid_amount' => 999,
                'payment_mode_id' => $paymentMode->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'batch_no' => 'PUR-BATCH-02',
                        'expiry_date' => '2027-02-01',
                        'quantity' => 2,
                        'free_qty' => 0,
                        'mrp' => 150,
                        'purchase_price' => 100,
                        'cc_rate' => 5,
                        'discount_percent' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(422)->assertJson([
            'type' => 'error',
            'message' => 'Paid amount cannot be greater than purchase bill total.',
        ]);

        $this->assertDatabaseCount('purchases', 0);
        $this->assertSame('N', $reference->fresh()->used);
    }

    private function createDropdown(string $alias, string $name, ?string $data = null): DropdownOption
    {
        return DropdownOption::query()->create([
            'alias' => $alias,
            'name' => $name,
            'data' => $data,
            'status' => 1,
        ]);
    }

    private function createSupplier(): Supplier
    {
        return Supplier::query()->create([
            'supplier_name' => 'Supplier One',
            'contact_person' => 'Tester',
            'phone_number' => '9800000000',
            'type' => 'credit',
            'status' => 'Y',
        ]);
    }

    private function createProduct(): Product
    {
        $company = Company::query()->create([
            'name' => 'Test Company',
            'company_type' => 'domestic',
            'default_cc_rate' => 5,
            'slug' => 'test-company',
        ]);

        $unit = Unit::query()->create([
            'unit_name' => 'Box',
            'type' => 'both',
            'description' => 'Test unit',
            'status' => 'Y',
        ]);

        return Product::query()->create([
            'name' => 'Paracetamol',
            'product_code' => 'P-200',
            'product_name' => 'Paracetamol 500',
            'company_id' => $company->id,
            'sale_unit_id' => $unit->id,
            'purchase_unit_id' => $unit->id,
            'product_status' => 'instock',
            'slug' => 'paracetamol-500',
            'reorder_level' => 10,
            'alert_quantity' => 10,
            'is_active' => true,
            'status' => 'Y',
            'mrp' => 150,
            'purchase_price' => 100,
            'cc_rate' => 5,
            'conversion rate' => 1,
        ]);
    }
}
