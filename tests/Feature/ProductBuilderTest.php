<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Builders\ProductBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Exception;

class ProductBuilderTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // US.1: Core Details Tests
    // ==========================================

    #[Test]
    public function it_sets_core_details_name_sku_and_description()
    {
        $product = Product::builder()
            ->addDetails('Developer Mug', 'MUG-001', 'Ceramic 11oz mug')
            ->addVariants(['Color' => 'Black'])
            ->build();

        $this->assertEquals('Developer Mug', $product->name);
        $this->assertEquals('MUG-001', $product->sku);
        $this->assertEquals('Ceramic 11oz mug', $product->description);
    }

    #[Test]
    public function it_prevents_empty_name()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing name');

        Product::builder()
            ->addVariants(['Size' => 'XL'])
            ->build();
    }

    #[Test]
    public function it_prevents_empty_sku()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing sku');

        Product::builder()
            ->addDetails('Test Product', '', 'Description')
            ->addVariants(['Size' => 'XL'])
            ->build();
    }

    // ==========================================
    // US.2: Inventory Variants Tests
    // ==========================================

    #[Test]
    public function it_stores_variants_as_json_array()
    {
        $variants = [
            'Color' => 'Matte Black',
            'Size' => 'Large',
            'Material' => 'Ceramic',
        ];

        $product = Product::builder()
            ->addDetails('Developer Mug', 'MUG-002', 'Premium mug')
            ->addVariants($variants)
            ->build();

        $this->assertEquals($variants, $product->variants);
        $this->assertDatabaseHas('products', [
            'sku' => 'MUG-002',
        ]);
    }

    #[Test]
    public function it_requires_variants()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing variants');

        Product::builder()
            ->addDetails('Test Product', 'TEST-001', 'Description')
            ->build();
    }

    // ==========================================
    // US.3: Access Control Tests
    // ==========================================

    #[Test]
    public function it_sets_allowed_roles_as_json_array()
    {
        $roles = ['admin', 'developers', 'managers'];

        $product = Product::builder()
            ->addDetails('Admin Tool', 'TOOL-001', 'Internal tool')
            ->addVariants(['Version' => 'Pro'])
            ->setAllowed($roles)
            ->build();

        $this->assertEquals($roles, $product->allowed_roles);
    }

    #[Test]
    public function it_allows_product_without_roles()
    {
        $product = Product::builder()
            ->addDetails('Public Product', 'PUB-001', 'For everyone')
            ->addVariants(['Type' => 'Standard'])
            ->build();

        $this->assertNull($product->allowed_roles);
    }

    // ==========================================
    // US.4: Logistics Mapping Tests
    // ==========================================

    #[Test]
    public function it_links_multiple_shipping_methods()
    {
        $dhl = ShippingMethod::create(['provider_name' => 'DHL', 'base_cost' => 500]);
        $fedex = ShippingMethod::create(['provider_name' => 'FedEx', 'base_cost' => 750]);
        $ups = ShippingMethod::create(['provider_name' => 'UPS', 'base_cost' => 600]);

        $product = Product::builder()
            ->addDetails('Shippable Item', 'SHIP-001', 'Ships worldwide')
            ->addVariants(['Weight' => '1kg'])
            ->addShipping([$dhl->id, $fedex->id, $ups->id])
            ->build();

        $this->assertCount(3, $product->shippingMethods);
        $this->assertTrue($product->shippingMethods->contains('provider_name', 'DHL'));
        $this->assertTrue($product->shippingMethods->contains('provider_name', 'FedEx'));
        $this->assertTrue($product->shippingMethods->contains('provider_name', 'UPS'));
    }

    #[Test]
    public function it_creates_product_without_shipping_methods()
    {
        $product = Product::builder()
            ->addDetails('Digital Product', 'DIGI-001', 'No shipping needed')
            ->addVariants(['Format' => 'PDF'])
            ->build();

        $this->assertCount(0, $product->shippingMethods);
    }

    // ==========================================
    // US.5: State Validation Tests
    // ==========================================

    #[Test]
    public function it_throws_exception_when_details_step_is_skipped()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing name');

        Product::builder()
            ->addVariants(['Size' => 'M'])
            ->setAllowed(['guest'])
            ->addShipping([1])
            ->build();
    }

    #[Test]
    public function it_throws_exception_when_variants_step_is_skipped()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing variants');

        Product::builder()
            ->addDetails('Incomplete Product', 'INC-001', 'Missing variants')
            ->setAllowed(['admin'])
            ->build();
    }

    // ==========================================
    // US.6: Atomic Persistence Tests
    // ==========================================

    #[Test]
    public function it_successfully_builds_a_complete_product()
    {
        $shipping = ShippingMethod::create(['provider_name' => 'DHL', 'base_cost' => 500]);

        $product = Product::builder()
            ->addDetails('Developer Mug', 'MUG-001', 'Ceramic 11oz')
            ->addVariants(['Color' => 'Matte Black'])
            ->setAllowed(['developers'])
            ->addShipping([$shipping->id])
            ->build();

        $this->assertInstanceOf(Product::class, $product);
        $this->assertDatabaseHas('products', ['sku' => 'MUG-001']);
        $this->assertCount(1, $product->shippingMethods);
    }

    #[Test]
    public function it_uses_database_transaction_for_atomic_persistence()
    {
        $initialCount = Product::count();

        // Attempt to create with invalid shipping ID should not create partial product
        try {
            Product::builder()
                ->addDetails('Test Product', 'TEST-ATOMIC', 'Testing transaction')
                ->addVariants(['Type' => 'Test'])
                ->addShipping([99999]) // Non-existent shipping method
                ->build();

            $this->fail('Should have thrown an exception');
        } catch (\Exception $e) {
            // Product should not have been created due to transaction rollback
            $this->assertEquals($initialCount, Product::count());
        }
    }

    // ==========================================
    // Fluent Interface Tests
    // ==========================================

    #[Test]
    public function it_returns_product_builder_instance_from_each_method()
    {
        $builder = Product::builder();

        $this->assertInstanceOf(ProductBuilder::class, $builder->addDetails('Test', 'TEST', 'Desc'));
        $this->assertInstanceOf(ProductBuilder::class, $builder->addVariants(['Size' => 'L']));
        $this->assertInstanceOf(ProductBuilder::class, $builder->setAllowed(['admin']));
        $this->assertInstanceOf(ProductBuilder::class, $builder->addShipping([]));
    }

    #[Test]
    public function it_supports_method_chaining()
    {
        $product = Product::builder()
            ->addDetails('Chained Product', 'CHAIN-001', 'Built via chaining')
            ->addVariants(['Style' => 'Modern'])
            ->setAllowed(['users'])
            ->build();

        $this->assertEquals('Chained Product', $product->name);
    }
}
