<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Company;
use App\Models\DropdownOption;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoiceStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_unpaid_sales_invoice_without_payment_mode(): void
    {
        $user = User::factory()->create();
        $saleType = $this->createDropdown('sales_type', 'Retail');
        $product = $this->createProduct();
        $batch = $this->createBatch($product);

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->postJson(route('admin.sales.store'), [
                'invoice_date' => '2026-05-16',
                'sale_type_id' => $saleType->id,
                'paid_amount' => 0,
                'notes' => 'Simple unpaid invoice test.',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'batch_id' => $batch->id,
                        'quantity' => 2,
                        'free_qty' => 0,
                        'unit_price' => 100,
                        'mrp' => 120,
                        'cc_rate' => 5,
                        'discount_percent' => 10,
                    ],
                ],
            ]);

        $response->assertOk()->assertJson([
            'type' => 'success',
        ]);

        $invoice = SalesInvoice::query()->first();

        $this->assertNotNull($invoice);
        $this->assertSame('unpaid', $invoice->payment_status);
        $this->assertSame('none', $invoice->payment_method);
        $this->assertNull($invoice->payment_mode_id);
        $this->assertSame(200.0, (float) $invoice->subtotal);
        $this->assertSame(20.0, (float) $invoice->total_discount);
        $this->assertSame(180.0, (float) $invoice->total_amount);
        $this->assertSame(8, (int) $batch->fresh()->quantity_available);
        $this->assertDatabaseCount('sales_invoice_items', 1);
    }

    public function test_it_rejects_sales_invoice_when_paid_amount_is_more_than_total(): void
    {
        $user = User::factory()->create();
        $saleType = $this->createDropdown('sales_type', 'Retail');
        $paymentMode = $this->createDropdown('payment_mode', 'Cash', 'cash');
        $product = $this->createProduct();
        $batch = $this->createBatch($product);

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->postJson(route('admin.sales.store'), [
                'invoice_date' => '2026-05-16',
                'sale_type_id' => $saleType->id,
                'payment_mode_id' => $paymentMode->id,
                'paid_amount' => 500,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'batch_id' => $batch->id,
                        'quantity' => 1,
                        'free_qty' => 0,
                        'unit_price' => 100,
                        'mrp' => 120,
                        'cc_rate' => 5,
                        'discount_percent' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(422)->assertJson([
            'type' => 'error',
            'message' => 'Paid amount cannot be greater than invoice total.',
        ]);

        $this->assertDatabaseCount('sales_invoices', 0);
        $this->assertSame(10, (int) $batch->fresh()->quantity_available);
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
            'product_code' => 'P-100',
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
            'mrp' => 120,
            'purchase_price' => 70,
            'cc_rate' => 5,
            'conversion rate' => 1,
        ]);
    }

    private function createBatch(Product $product): Batch
    {
        return Batch::query()->create([
            'product_id' => $product->id,
            'supplier_id' => $this->createSupplierId(),
            'batch_number' => 'BATCH-S-100',
            'expiry_date' => '2027-01-01',
            'quantity_received' => 10,
            'quantity_available' => 10,
            'purchase_price' => 70,
            'is_active' => true,
        ]);
    }

    private function createSupplierId(): int
    {
        return \App\Models\Supplier::query()->create([
            'supplier_name' => 'Supplier One',
            'contact_person' => 'Tester',
            'phone_number' => '9800000000',
            'type' => 'credit',
            'status' => 'Y',
        ])->id;
    }
}
