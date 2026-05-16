<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductModelTest extends TestCase
{
    public function test_it_uses_product_cc_rate_when_it_is_set(): void
    {
        $product = new Product([
            'cc_rate' => 8.75,
        ]);

        $product->setRelation('company', new Company([
            'default_cc_rate' => 3.50,
        ]));

        $this->assertSame(8.75, $product->effective_cc_rate);
    }

    public function test_it_falls_back_to_company_cc_rate_when_product_rate_is_empty(): void
    {
        $product = new Product([
            'cc_rate' => 0,
        ]);

        $product->setRelation('company', new Company([
            'default_cc_rate' => 4.25,
        ]));

        $this->assertSame(4.25, $product->effective_cc_rate);
    }

    public function test_it_uses_the_new_name_field_for_display_name(): void
    {
        $product = new Product([
            'name' => 'Azithromycin 500',
        ]);

        $this->assertSame('Azithromycin 500', $product->display_name);
    }

    public function test_it_returns_default_reorder_level_when_value_is_missing(): void
    {
        $product = new Product([
            'alert_quantity' => 12,
        ]);

        $this->assertSame(12, $product->effective_reorder_level);
    }
}
