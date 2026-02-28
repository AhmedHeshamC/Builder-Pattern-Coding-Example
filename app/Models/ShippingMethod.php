<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShippingMethod extends Model
{
    protected $fillable = [
        'provider_name',
        'base_cost',
    ];

    protected function casts(): array
    {
        return [
            'base_cost' => 'integer',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_shipping_method');
    }
}
