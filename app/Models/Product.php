<?php

namespace App\Models;

use App\Builders\ProductBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'description',
        'variants',
        'allowed_roles',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'allowed_roles' => 'array',
        ];
    }

    public function shippingMethods(): BelongsToMany
    {
        return $this->belongsToMany(ShippingMethod::class, 'product_shipping_method');
    }

    public static function builder(): ProductBuilder
    {
        return new ProductBuilder();
    }
}
