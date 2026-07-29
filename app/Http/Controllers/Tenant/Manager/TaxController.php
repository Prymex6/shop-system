<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\TaxRate;
use App\Services\EuVatService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxController extends Controller
{
    public function index()
    {
        return Inertia::render('Tenant/Manager/Tax/Index', [
            'taxRates' => TaxRate::orderByDesc('is_default')->orderBy('rate')->get(),
            'euRates' => EuVatService::EU_VAT_RATES,
        ]);
    }

    /**
     * Store an EU OSS tax rate
     */
    public function storeOss(Request $request)
    {
        $data = $request->validate([
            'country_code' => 'required|string|size:2',
            'eu_vat_rate' => 'required|numeric|min:0|max:50',
            'name' => 'nullable|string|max:100',
        ]);

        TaxRate::updateOrCreate(
            ['country_code' => $data['country_code'], 'is_eu_oss' => true],
            [
                'name' => $data['name'] ?? ('EU OSS ' . $data['country_code']),
                'rate' => $data['eu_vat_rate'],
                'eu_vat_rate' => $data['eu_vat_rate'],
                'is_eu_oss' => true,
                'is_active' => true,
            ]
        );

        return back()->with('success', "Stawka EU OSS dla {$data['country_code']} zapisana.");
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'country' => 'nullable|string|size:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            TaxRate::where('is_default', true)->update(['is_default' => false]);
        }

        TaxRate::create($data);

        return back()->with('success', 'Stawka VAT dodana.');
    }

    public function update(Request $request, TaxRate $taxRate)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'country' => 'nullable|string|size:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            TaxRate::where('is_default', true)->where('id', '!=', $taxRate->id)->update(['is_default' => false]);
        }

        $taxRate->update($data);

        return back()->with('success', 'Stawka VAT zaktualizowana.');
    }

    public function destroy(TaxRate $taxRate)
    {
        if ($taxRate->products()->exists()) {
            return back()->with('error', __('messages.tax_rate_in_use'));
        }
        $taxRate->delete();

        return back()->with('success', __('messages.tax_rate_deleted'));
    }
}
