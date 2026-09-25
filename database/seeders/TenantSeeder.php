<?php

namespace Database\Seeders;

use App\Models\Tenant\Category;
use App\Models\Tenant\Customer;
use App\Models\Tenant\DiscountCode;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\ShippingMethod;
use App\Models\Tenant\ShippingZone;
use App\Models\Tenant\User;
use App\Models\Tenant\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('TenantSeeder holds demo data and must NOT be run in production.');
            if (!$this->command->confirm('Really seed demo data in production?', false)) {
                $this->command->info('Anulowano.');

                return;
            }
        }

        $this->call(TenantSettingsSeeder::class);

        $this->seedStaff();
        $this->seedCustomers();
        $this->seedCategories();
        $this->seedProducts();
        $this->seedDiscountCodes();
        $this->seedShipping();
        $this->seedWarehouse();

        $this->command->info('  ✔ Dane demonstracyjne tenanta gotowe.');
    }

    // ── Staff ─────────────────────────────────────────────────────────────────

    private function seedStaff(): void
    {
        $staff = [
            [
                'name' => 'Jan Kowalski',
                'email' => 'manager@example.com',
                'password' => Hash::make('password'),
                'role' => 'manager',
                'phone' => '+48 500 100 200',
                'is_active' => true,
            ],
            [
                'name' => 'Piotr Nowak',
                'email' => 'staff@example.com',
                'password' => Hash::make('password'),
                'role' => 'fulfillment',
                'phone' => '+48 500 100 201',
                'is_active' => true,
            ],
        ];

        foreach ($staff as $member) {
            User::updateOrCreate(['email' => $member['email']], $member);
        }

        $this->command->line('  👥 Staff: ' . count($staff) . ' kont.');
    }

    // ── Customers ────────────────────────────────────────────────────────────

    private function seedCustomers(): void
    {
        $customers = [
            [
                'name' => 'Tomasz Testowy',
                'email' => 'klient@example.pl',
                'password' => Hash::make('password'),
                'phone' => '+48 600 123 456',
                'delivery_city' => 'Krakow',
                'delivery_address' => '5 Long Street',
                'delivery_postal_code' => '31-147',
                'loyalty_points' => 150,
                'loyalty_tier' => 'silver',
            ],
            [
                'name' => 'Maria Example',
                'email' => 'maria@example.pl',
                'password' => Hash::make('password'),
                'phone' => '+48 601 234 567',
                'delivery_city' => 'Krakow',
                'delivery_address' => 'ul. Grodzka 10',
                'delivery_postal_code' => '31-006',
                'loyalty_points' => 45,
                'loyalty_tier' => 'bronze',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(['email' => $customer['email']], $customer);
        }

        $this->command->line('  👤 Klienci: ' . count($customers) . ' kont.');
    }

    // ── Categories ────────────────────────────────────────────────────────────

    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Elektronika', 'slug' => 'elektronika', 'sort_order' => 1],
            ['name' => 'Clothing',      'slug' => 'odziez',      'sort_order' => 2],
            ['name' => 'Akcesoria',   'slug' => 'akcesoria',   'sort_order' => 3],
            ['name' => 'E-booki',     'slug' => 'e-booki',     'sort_order' => 4],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], array_merge($cat, ['is_active' => true]));
        }

        $this->command->line('  📂 Kategorie: ' . count($categories) . ' szt.');
    }

    // ── Products ──────────────────────────────────────────────────────────────

    private function seedProducts(): void
    {
        $electronics = Category::where('slug', 'elektronika')->first();
        $clothing = Category::where('slug', 'odziez')->first();
        $accessories = Category::where('slug', 'akcesoria')->first();
        $ebooks = Category::where('slug', 'e-booki')->first();

        $products = [
            [
                'category_id' => $electronics?->id,
                'name' => 'BT Pro X headphones',
                'slug' => 'sluchawki-bt-pro-x',
                'short_description' => 'Wireless headphones with ANC',
                'description' => 'Bluetooth headphones with active noise cancelling, 30h battery, folding design.',
                'sku' => 'SLUBX001',
                'type' => 'physical',
                'price' => 299.99,
                'compare_price' => 399.99,
                'cost_price' => 120.00,
                'track_stock' => true,
                'stock_quantity' => 50,
                'low_stock_threshold' => 5,
                'weight' => 0.25,
                'is_published' => true,
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'category_id' => $electronics?->id,
                'name' => 'Smartwatch X200',
                'slug' => 'smartwatch-x200',
                'short_description' => 'Smartwatch z monitorem zdrowia',
                'description' => 'Waterproof smartwatch with GPS, sleep tracking, a pulse oximeter and a 7-day battery.',
                'sku' => 'SMWX200',
                'type' => 'physical',
                'price' => 549.00,
                'compare_price' => 699.00,
                'cost_price' => 220.00,
                'track_stock' => true,
                'stock_quantity' => 30,
                'low_stock_threshold' => 3,
                'weight' => 0.08,
                'is_published' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'category_id' => $clothing?->id,
                'name' => 'Bluza Premium Hoodie',
                'slug' => 'bluza-premium-hoodie',
                'short_description' => 'Cotton hoodie',
                'description' => '100% combed cotton, 320 gsm, kangaroo pocket, ribbed hem.',
                'sku' => 'BLU001',
                'type' => 'physical',
                'price' => 149.00,
                'cost_price' => 55.00,
                'track_stock' => true,
                'stock_quantity' => 120,
                'low_stock_threshold' => 10,
                'weight' => 0.45,
                'is_published' => true,
                'is_featured' => false,
                'sort_order' => 3,
            ],
            [
                'category_id' => $accessories?->id,
                'name' => 'Slim leather wallet',
                'slug' => 'portfel-skorzany-slim',
                'short_description' => 'Slim leather wallet',
                'description' => 'Full-grain leather, six card slots, a note compartment and RFID blocking.',
                'sku' => 'POR001',
                'type' => 'physical',
                'price' => 89.00,
                'cost_price' => 28.00,
                'track_stock' => true,
                'stock_quantity' => 75,
                'low_stock_threshold' => 8,
                'weight' => 0.1,
                'is_published' => true,
                'is_featured' => false,
                'sort_order' => 4,
            ],
            [
                'category_id' => $ebooks?->id,
                'name' => 'Kurs PHP 8 — kompletny przewodnik',
                'slug' => 'kurs-php-8-kompletny-przewodnik',
                'short_description' => 'E-book: PHP 8 od podstaw do eksperta',
                'description' => 'A complete ebook on PHP 8: OOP, types, Fibers, JIT and Laravel 11. PDF, 450 pages.',
                'sku' => 'EBOOK001',
                'type' => 'digital',
                'price' => 79.00,
                'cost_price' => 0.00,
                'track_stock' => false,
                'stock_quantity' => 0,
                'download_limit' => 3,
                'download_expires_hours' => 72,
                'is_published' => true,
                'is_featured' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(['slug' => $data['slug']], $data);
        }

        // Warianty do bluzy (attributes = kolor+rozmiar jako JSON)
        $bluza = Product::where('slug', 'bluza-premium-hoodie')->first();
        if ($bluza) {
            $variants = [
                ['sku' => 'BLU001-BK-S', 'attributes' => ['Kolor' => 'Czarny', 'Rozmiar' => 'S'], 'price' => 149.00, 'stock_quantity' => 30],
                ['sku' => 'BLU001-BK-M', 'attributes' => ['Kolor' => 'Czarny', 'Rozmiar' => 'M'], 'price' => 149.00, 'stock_quantity' => 40],
                ['sku' => 'BLU001-BK-L', 'attributes' => ['Kolor' => 'Czarny', 'Rozmiar' => 'L'], 'price' => 149.00, 'stock_quantity' => 30],
                ['sku' => 'BLU001-GR-M', 'attributes' => ['Kolor' => 'Szary',  'Rozmiar' => 'M'], 'price' => 149.00, 'stock_quantity' => 20],
                ['sku' => 'BLU001-WH-M', 'attributes' => ['Kolor' => 'White',  'Rozmiar' => 'M'], 'price' => 149.00, 'stock_quantity' => 0],
            ];
            foreach ($variants as $vi => $v) {
                ProductVariant::updateOrCreate(
                    ['product_id' => $bluza->id, 'sku' => $v['sku']],
                    array_merge($v, ['sort_order' => $vi + 1, 'is_active' => true])
                );
            }
        }

        $this->command->line('  🛍️  Produkty: ' . count($products) . ' szt. (+ warianty bluzy)');
    }

    // ── Discount codes ────────────────────────────────────────────────────────

    private function seedDiscountCodes(): void
    {
        $codes = [
            [
                'code' => 'WELCOME10',
                'type' => 'percentage',
                'value' => 10.00,
                'min_order_value' => 50.00,
                'max_uses' => null,
                'is_active' => true,
            ],
            [
                'code' => 'LATO20',
                'type' => 'percentage',
                'value' => 20.00,
                'min_order_value' => 100.00,
                'max_uses' => 200,
                'valid_until' => now()->addDays(60),
                'is_active' => true,
            ],
            [
                'code' => 'REGULAR5',
                'type' => 'fixed',
                'value' => 5.00,
                'min_order_value' => 30.00,
                'max_uses' => null,
                'is_active' => true,
            ],
            [
                'code' => 'WYGASLY',
                'type' => 'percentage',
                'value' => 15.00,
                'is_active' => false,
            ],
        ];

        foreach ($codes as $code) {
            DiscountCode::updateOrCreate(['code' => $code['code']], $code);
        }

        $this->command->line('  🏷️  Kody rabatowe: ' . count($codes) . ' szt.');
    }

    // ── Shipping ──────────────────────────────────────────────────────────────

    private function seedShipping(): void
    {
        ShippingZone::updateOrCreate(
            ['name' => 'Polska'],
            ['countries' => ['PL'], 'is_default' => true]
        );

        $methods = [
            ['name' => 'Kurier DPD',       'price' => 14.99, 'free_from' => 199.00, 'delivery_days_min' => 2, 'delivery_days_max' => 3],
            ['name' => 'InPost paczkomat', 'price' => 12.99, 'free_from' => 199.00, 'delivery_days_min' => 1, 'delivery_days_max' => 2],
            ['name' => 'Collection in person',  'price' => 0.00,  'free_from' => null,   'delivery_days_min' => 0, 'delivery_days_max' => 0],
        ];

        foreach ($methods as $m) {
            ShippingMethod::updateOrCreate(
                ['name' => $m['name']],
                array_merge($m, ['is_active' => true])
            );
        }

        $this->command->line('  🚚 Metody dostawy: ' . count($methods) . ' szt.');
    }

    // ── Warehouse ─────────────────────────────────────────────────────────────

    private function seedWarehouse(): void
    {
        Warehouse::updateOrCreate(
            ['name' => 'Main warehouse'],
            [
                'city' => 'Krakow',
                'address' => '1 Warehouse Road, Krakow',
                'is_active' => true,
            ]
        );

        $this->command->line('  🏭 Magazyn: 1 szt.');
    }
}
