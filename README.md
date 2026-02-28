# Laravel Fluent Product Builder

A clean implementation of the **Builder Design Pattern** in Laravel for constructing complex Product objects with variants, access control, and shipping method mappings.

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4+-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

---

## Table of Contents

- [Overview](#overview)
- [The Problem](#the-problem)
- [The Solution](#the-solution)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [API Reference](#api-reference)
- [Database Schema](#database-schema)
- [Testing](#testing)
- [Architecture](#architecture)
- [Design Patterns Used](#design-patterns-used)

---

## Overview

This project demonstrates how to eliminate "Fat Controllers" and messy `store()` methods by implementing a **Fluent Builder Pattern**. Complex Product objects requiring variants, permissions, and shipping configurations are validated and constructed in an atomic state before being persisted to the database.

### Key Features

- **Fluent Interface** - Chainable method calls for readable code
- **Validation Guards** - Build fails fast if mandatory data is missing
- **Atomic Persistence** - Database transactions ensure no partial saves
- **Flexible Construction** - Optional steps for roles and shipping
- **TDD Approach** - 15 comprehensive tests with 31 assertions

---

## The Problem

Traditional controller methods become bloated when handling complex object creation:

```php
// ❌ Fat Controller - Hard to test, hard to read
public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string',
        'sku' => 'required|unique:products',
        'variants' => 'required|array',
        'roles' => 'nullable|array',
        'shipping_methods' => 'nullable|array',
    ]);

    $product = Product::create([
        'name' => $validated['name'],
        'sku' => $validated['sku'],
        'variants' => json_encode($validated['variants']),
        // ... more fields
    ]);

    if (isset($validated['shipping_methods'])) {
        $product->shippingMethods()->sync($validated['shipping_methods']);
    }

    // What if something fails here? Partial data in DB!
    return $product;
}
```

---

## The Solution

The Builder Pattern separates the construction logic from the model:

```php
// ✅ Clean, readable, atomic
public function store(Request $request)
{
    return Product::builder()
        ->addDetails(
            $request->name,
            $request->sku,
            $request->description
        )
        ->addVariants($request->variants)
        ->setAllowed($request->roles ?? [])
        ->addShipping($request->shipping_methods ?? [])
        ->build();
}
```

---

## Installation

### Requirements

- PHP 8.4+
- Laravel 12.x
- SQLite/MySQL/PostgreSQL

### Setup

```bash
# Clone the repository
git clone https://github.com/AhmedHeshamC/Builder-Pattern-Coding-Example.git
cd Builder-Pattern-Coding-Example

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Run tests
php artisan test
```

---

## Quick Start

### Run the Demo

```bash
php artisan demo:product-builder
```

This interactive demo showcases all builder features with real examples.

### Basic Usage

```php
use App\Models\Product;

// Create a complete product
$product = Product::builder()
    ->addDetails('Developer Mug', 'MUG-001', 'Premium ceramic mug')
    ->addVariants(['Color' => 'Matte Black', 'Size' => '11oz'])
    ->setAllowed(['developers', 'admins'])
    ->addShipping([1, 2, 3])
    ->build();

// Create a minimal product (required fields only)
$product = Product::builder()
    ->addDetails('Basic T-Shirt', 'TSHIRT-001', '')
    ->addVariants(['Size' => 'M'])
    ->build();
```

---

## API Reference

### ProductBuilder Methods

| Method | Parameters | Required | Description |
|--------|------------|----------|-------------|
| `addDetails()` | `name`, `sku`, `description` | **Yes** | Sets core product information |
| `addVariants()` | `array` | **Yes** | Sets flexible product attributes as JSON |
| `setAllowed()` | `array` | No | Sets role-based access control |
| `addShipping()` | `array` | No | Links shipping method IDs |
| `build()` | - | - | Validates and persists the product |

### Method Chaining

All builder methods return `$this`, enabling fluent chaining:

```php
$builder = Product::builder();

// Each call returns the builder instance
$builder->addDetails('Name', 'SKU', 'Desc');  // returns ProductBuilder
$builder->addVariants(['key' => 'value']);    // returns ProductBuilder
$builder->setAllowed(['role']);               // returns ProductBuilder
$builder->addShipping([1]);                   // returns ProductBuilder
$product = $builder->build();                 // returns Product
```

### Validation Rules

The `build()` method enforces these rules:

| Field | Rule | Exception Message |
|-------|------|-------------------|
| `name` | Required, non-empty | `Missing name` |
| `sku` | Required, non-empty | `Missing sku` |
| `variants` | Required, non-empty | `Missing variants` |

---

## Database Schema

### Products Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `name` | string | Product name |
| `sku` | string | Unique SKU code |
| `description` | text | Product description (nullable) |
| `variants` | json | Flexible attributes (size, color, etc.) |
| `allowed_roles` | json | Role-based access control (nullable) |
| `timestamps` | timestamp | Created/Updated at |

### Shipping Methods Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `provider_name` | string | Shipping provider name |
| `base_cost` | integer | Base cost in cents |
| `timestamps` | timestamp | Created/Updated at |

### Pivot Table: product_shipping_method

| Column | Type | Description |
|--------|------|-------------|
| `product_id` | foreign key | References products.id |
| `shipping_method_id` | foreign key | References shipping_methods.id |

---

## Testing

### Run All Tests

```bash
php artisan test --filter=ProductBuilderTest
```

### Test Coverage

| User Story | Tests | Status |
|------------|-------|--------|
| US.1: Core Details | `it_sets_core_details_*`, `it_prevents_empty_*` | ✅ |
| US.2: Inventory Variants | `it_stores_variants_as_json_array`, `it_requires_variants` | ✅ |
| US.3: Access Control | `it_sets_allowed_roles_*`, `it_allows_product_without_roles` | ✅ |
| US.4: Logistics Mapping | `it_links_multiple_shipping_methods`, `it_creates_product_without_shipping_methods` | ✅ |
| US.5: State Validation | `it_throws_exception_when_*` | ✅ |
| US.6: Atomic Persistence | `it_successfully_builds_*`, `it_uses_database_transaction_*` | ✅ |

### Test Results

```
   PASS  Tests\Feature\ProductBuilderTest
  ✓ it sets core details name sku and description
  ✓ it prevents empty name
  ✓ it prevents empty sku
  ✓ it stores variants as json array
  ✓ it requires variants
  ✓ it sets allowed roles as json array
  ✓ it allows product without roles
  ✓ it links multiple shipping methods
  ✓ it creates product without shipping methods
  ✓ it throws exception when details step is skipped
  ✓ it throws exception when variants step is skipped
  ✓ it successfully builds a complete product
  ✓ it uses database transaction for atomic persistence
  ✓ it returns product builder instance from each method
  ✓ it supports method chaining

  Tests:    15 passed (31 assertions)
  Duration: 0.19s
```

---

## Architecture

### Directory Structure

```
app/
├── Builders/
│   └── ProductBuilder.php      # Fluent builder class
├── Models/
│   ├── Product.php             # Product Eloquent model
│   └── ShippingMethod.php      # ShippingMethod Eloquent model
└── Console/Commands/
    └── DemoProductBuilder.php  # Interactive demo command

database/
├── migrations/
│   ├── *_create_products_table.php
│   └── *_create_shipping_methods_table.php

tests/
└── Feature/
    └── ProductBuilderTest.php  # Comprehensive test suite
```

### Class Diagram

```
┌─────────────────────┐       ┌─────────────────────┐
│     ProductBuilder  │       │       Product       │
├─────────────────────┤       ├─────────────────────┤
│ - data: array       │       │ - fillable: array   │
│ - shippingIds: array│       │ - casts: array      │
├─────────────────────┤       ├─────────────────────┤
│ + addDetails()      │       │ + builder()         │
│ + addVariants()     │──────▶│ + shippingMethods() │
│ + setAllowed()      │       └─────────────────────┘
│ + addShipping()     │               │
│ + build()           │               │ M:N
└─────────────────────┘               ▼
                          ┌─────────────────────┐
                          │   ShippingMethod    │
                          ├─────────────────────┤
                          │ - provider_name     │
                          │ - base_cost         │
                          └─────────────────────┘
```

---

## Design Patterns Used

### 1. Builder Pattern (Creational)

Separates the construction of a complex object from its representation, allowing the same construction process to create different representations.

**Benefits:**
- Step-by-step object construction
- Validation before persistence
- Clean, readable client code

### 2. Fluent Interface

Methods return `$this` to enable method chaining for more readable code.

```php
Product::builder()
    ->addDetails(...)
    ->addVariants(...)
    ->build();
```

### 3. Active Record (Laravel Eloquent)

Each model represents a database row and provides methods for CRUD operations.

---

## Definition of Done

- [x] Fluent Interface: `Product::builder()->...->build()` returns a Product instance
- [x] Validation: Exception thrown if mandatory data is omitted
- [x] Data Integrity: `DB::transaction` wraps the creation and relationship sync
- [x] Eloquent Configuration: `$fillable` and `$casts` properly set
- [x] Test Coverage: All steps and failure states covered by tests

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request
