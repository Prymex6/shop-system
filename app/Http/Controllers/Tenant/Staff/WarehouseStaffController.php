<?php

namespace App\Http\Controllers\Tenant\Staff;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Models\Tenant\Warehouse;
use Inertia\Inertia;

class WarehouseStaffController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::with(['stock.product'])->get();

        $lowStock = Product::whereHas('variants', function ($q) {
            $q->where('stock_quantity', '<=', 5)->where('stock_quantity', '>', 0);
        })->orWhere(function ($q) {
            $q->doesntHave('variants')
                ->where('stock_quantity', '<=', 5)
                ->where('stock_quantity', '>', 0);
        })->get(['id', 'name', 'sku', 'stock_quantity']);

        return Inertia::render('Tenant/Staff/Warehouse', [
            'warehouses' => $warehouses,
            'lowStock' => $lowStock,
        ]);
    }
}
