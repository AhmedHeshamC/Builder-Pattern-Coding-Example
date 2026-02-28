<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Console\Command;
use Exception;

class DemoProductBuilder extends Command
{
    protected $signature = 'demo:product-builder';

    protected $description = 'Test-drive the Fluent Product Builder Pattern';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('       FLUENT PRODUCT BUILDER - TEST DRIVE DEMO');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->newLine();

        // Step 1: Create Shipping Methods
        $this->info('📦 STEP 1: Creating Shipping Methods...');
        $dhl = ShippingMethod::create(['provider_name' => 'DHL Express', 'base_cost' => 1500]);
        $fedex = ShippingMethod::create(['provider_name' => 'FedEx International', 'base_cost' => 2000]);
        $this->line("   ✓ Created: {$dhl->provider_name} (ID: {$dhl->id})");
        $this->line("   ✓ Created: {$fedex->provider_name} (ID: {$fedex->id})");
        $this->newLine();

        // Demo 1: Full Product with all features
        $this->info('🛠️  DEMO 1: Building a Complete Product (Full Features)');
        $this->line('   Code:');
        $this->line('   ┌─────────────────────────────────────────────────────────────┐');
        $this->line('   │ $product = Product::builder()                               │');
        $this->line('   │     ->addDetails(                                           │');
        $this->line('   │         "Developer Mug",                                    │');
        $this->line('   │         "MUG-DEV-001",                                      │');
        $this->line('   │         "Premium ceramic mug for developers"                │');
        $this->line('   │     )                                                       │');
        $this->line('   │     ->addVariants([                                         │');
        $this->line('   │         "Color" => "Matte Black",                           │');
        $this->line('   │         "Size" => "11oz",                                   │');
        $this->line('   │         "Material" => "Ceramic"                             │');
        $this->line('   │     ])                                                      │');
        $this->line('   │     ->setAllowed(["developers", "admins"])                  │');
        $this->line('   │     ->addShipping([1, 2])                                   │');
        $this->line('   │     ->build();                                              │');
        $this->line('   └─────────────────────────────────────────────────────────────┘');
        $this->newLine();

        $product1 = Product::builder()
            ->addDetails(
                'Developer Mug',
                'MUG-DEV-001',
                'Premium ceramic mug for developers'
            )
            ->addVariants([
                'Color' => 'Matte Black',
                'Size' => '11oz',
                'Material' => 'Ceramic',
            ])
            ->setAllowed(['developers', 'admins'])
            ->addShipping([$dhl->id, $fedex->id])
            ->build();

        $this->info('   ✅ Result:');
        $this->line("   • ID: {$product1->id}");
        $this->line("   • Name: {$product1->name}");
        $this->line("   • SKU: {$product1->sku}");
        $this->line("   • Description: {$product1->description}");
        $this->line('   • Variants: ' . json_encode($product1->variants));
        $this->line('   • Allowed Roles: ' . json_encode($product1->allowed_roles));
        $this->line('   • Shipping Methods: ' . $product1->shippingMethods->pluck('provider_name')->join(', '));
        $this->newLine();

        // Demo 2: Minimal Product
        $this->info('🛠️  DEMO 2: Building a Minimal Product (Required fields only)');
        $this->line('   Code:');
        $this->line('   ┌─────────────────────────────────────────────────────────────┐');
        $this->line('   │ $product = Product::builder()                               │');
        $this->line('   │     ->addDetails("Basic T-Shirt", "TSHIRT-001", "")        │');
        $this->line('   │     ->addVariants(["Size" => "M", "Color" => "White"])     │');
        $this->line('   │     ->build();                                              │');
        $this->line('   └─────────────────────────────────────────────────────────────┘');
        $this->newLine();

        $product2 = Product::builder()
            ->addDetails('Basic T-Shirt', 'TSHIRT-001', '')
            ->addVariants(['Size' => 'M', 'Color' => 'White'])
            ->build();

        $this->info('   ✅ Result:');
        $this->line("   • Name: {$product2->name}");
        $this->line("   • SKU: {$product2->sku}");
        $this->line('   • Variants: ' . json_encode($product2->variants));
        $this->line('   • Allowed Roles: ' . ($product2->allowed_roles ?? 'null (public)'));
        $this->line('   • Shipping Methods: ' . ($product2->shippingMethods->count() > 0 ? $product2->shippingMethods->pluck('provider_name')->join(', ') : 'None'));
        $this->newLine();

        // Demo 3: Validation Error
        $this->info('🛠️  DEMO 3: Validation Guard (Missing required field)');
        $this->line('   Code:');
        $this->line('   ┌─────────────────────────────────────────────────────────────┐');
        $this->line('   │ $product = Product::builder()                               │');
        $this->line('   │     ->addDetails("Incomplete", "", "Missing SKU")           │');
        $this->line('   │     ->addVariants(["Type" => "Test"])                       │');
        $this->line('   │     ->build();  // Should throw exception                   │');
        $this->line('   └─────────────────────────────────────────────────────────────┘');
        $this->newLine();

        try {
            Product::builder()
                ->addDetails('Incomplete Product', '', 'Missing SKU')
                ->addVariants(['Type' => 'Test'])
                ->build();
        } catch (Exception $e) {
            $this->error('   ❌ Exception Thrown: ' . $e->getMessage());
            $this->line('   ✓ Validation guard prevented invalid product creation');
        }
        $this->newLine();

        // Demo 4: Missing variants
        $this->info('🛠️  DEMO 4: Validation Guard (Missing variants)');
        $this->line('   Attempting to build without addVariants()...');
        $this->newLine();

        try {
            Product::builder()
                ->addDetails('No Variants', 'NO-VAR-001', 'Missing variants step')
                ->build();
        } catch (Exception $e) {
            $this->error('   ❌ Exception Thrown: ' . $e->getMessage());
            $this->line('   ✓ Validation guard enforced mandatory variants step');
        }
        $this->newLine();

        // Summary
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('                        SUMMARY');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->table(
            ['Product', 'SKU', 'Variants', 'Roles', 'Shipping'],
            [
                [
                    $product1->name,
                    $product1->sku,
                    json_encode($product1->variants),
                    json_encode($product1->allowed_roles),
                    $product1->shippingMethods->count() . ' methods'
                ],
                [
                    $product2->name,
                    $product2->sku,
                    json_encode($product2->variants),
                    'null',
                    '0 methods'
                ],
            ]
        );
        $this->newLine();

        $this->info('✅ Demo completed successfully!');
        $this->line('   Total products in database: ' . Product::count());
        $this->line('   Total shipping methods: ' . ShippingMethod::count());

        return self::SUCCESS;
    }
}
