# BRD: Laravel Fluent Product Builder

**Framework:** Laravel (PHP 8.4+)
**Pattern:** Builder Design Pattern
**Methodology:** Scrum / TDD

---

## 1. Project Vision & Objective

To eliminate "Fat Controllers" and messy store() methods by implementing a Fluent Builder Pattern. This ensures that complex Product objects (requiring variants, permissions, and shipping) are validated and constructed in an atomic state before being persisted to the database.

---

## 2. The Scrum Backlog (User Stories)

| ID | User Story | Priority | Acceptance Criteria |
|----|------------|----------|---------------------|
| US.1 | Core Details | High | Set name, sku, and description. Must prevent empty values. |
| US.2 | Inventory Variants | High | Store flexible attributes (size, color) as a JSON array. |
| US.3 | Access Control | Med | Define visibility/roles (e.g., admin, guest) as a JSON array. |
| US.4 | Logistics Mapping | High | Link multiple ShippingMethod IDs via a Many-to-Many pivot table. |
| US.5 | State Validation | Critical | build() must throw an exception if mandatory steps (Details/Variants) are skipped. |
| US.6 | Atomic Persistence | Critical | Use DB::transaction to ensure no partial data is saved on failure. |

---

## 3. Technical Implementation

### A. Database Schema (Migrations)

```php
// Products Table
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('sku')->unique();
    $table->text('description')->nullable();
    $table->json('variants');      // Step 2
    $table->json('allowed_roles'); // Step 3
    $table->timestamps();
});

// Pivot Table
Schema::create('product_shipping_method', function (Blueprint $table) {
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();
});
```

### B. The Builder Class (app/Builders/ProductBuilder.php)

This class handles the fluent state and enforces validation before the database is ever touched.

```php
<?php

namespace App\Builders;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductBuilder
{
    protected array $data = [];
    protected array $shippingIds = [];

    public function addDetails(string $name, string $sku, string $desc): self
    {
        $this->data['name'] = $name;
        $this->data['sku'] = $sku;
        $this->data['description'] = $desc;
        return $this;
    }

    public function addVariants(array $variants): self
    {
        $this->data['variants'] = $variants;
        return $this;
    }

    public function setAllowed(array $roles): self
    {
        $this->data['allowed_roles'] = $roles;
        return $this;
    }

    public function addShipping(array $ids): self
    {
        $this->shippingIds = $ids;
        return $this;
    }

    public function build(): Product
    {
        // Validation Guard: Ensure required steps are completed
        $required = ['name', 'sku', 'variants'];
        foreach ($required as $field) {
            if (empty($this->data[$field])) {
                throw new Exception("Product construction failed: Missing {$field}.");
            }
        }

        return DB::transaction(function () {
            // Persist the Product
            $product = Product::create($this->data);

            // Sync Many-to-Many Shipping Relationships
            if (!empty($this->shippingIds)) {
                $product->shippingMethods()->sync($this->shippingIds);
            }

            return $product->load('shippingMethods');
        });
    }
}
```

---

## 4. Full Quality Assurance (Feature Tests)

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductBuilderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_successfully_builds_a_complete_product()
    {
        $shipping = ShippingMethod::create(['provider_name' => 'DHL', 'base_cost' => 500]);

        $product = Product::builder()
            ->addDetails('Developer Mug', 'MUG-001', 'Ceramic 11oz')
            ->addVariants(['Color' => 'Matte Black'])
            ->setAllowed(['developers'])
            ->addShipping([$shipping->id])
            ->build();

        $this->assertDatabaseHas('products', ['sku' => 'MUG-001']);
        $this->assertCount(1, $product->shippingMethods);
    }

    /** @test */
    public function it_fails_if_mandatory_details_are_missing()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Missing name");

        // Skipping addDetails()
        Product::builder()
            ->addVariants(['Size' => 'XL'])
            ->build();
    }
}
```

---

## 5. Definition of Done (DoD) Checklist

- [ ] Fluent Interface: `$product->builder()->...->build()` returns a Product instance.
- [ ] Validation: Exception thrown if mandatory data is omitted.
- [ ] Data Integrity: `DB::transaction` wraps the creation and relationship sync.
- [ ] Eloquent Configuration: Product model has `$fillable` and `$casts` (JSON to Array) correctly set.
- [ ] Test Coverage: All 4 steps and the failure state are covered by Feature tests.
