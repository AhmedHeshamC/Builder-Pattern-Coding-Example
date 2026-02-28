<?php

namespace App\Builders;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductBuilder
{
    protected array $data = [];
    protected array $shippingIds = [];

    public function addDetails(string $name, string $sku, string $description = ''): self
    {
        $this->data['name'] = $name;
        $this->data['sku'] = $sku;
        $this->data['description'] = $description;
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
