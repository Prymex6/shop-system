<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use App\Services\SeoCheckerService;

class SeoCheckController extends Controller
{
    public function __construct(protected SeoCheckerService $seoChecker) {}

    public function check(Product $product)
    {
        $result = $this->seoChecker->analyze($product);

        return response()->json($result);
    }
}
