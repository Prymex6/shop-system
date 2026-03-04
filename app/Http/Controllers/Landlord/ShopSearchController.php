<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\ShopLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ShopSearchController extends Controller
{
    public function index()
    {
        return Inertia::render('Landlord/ShopSearch');
    }

    public function findContact(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
        ]);

        $query = trim($request->name . ' ' . $request->city . ' sklep');

        $body = null;

        // 1. Try Startpage (returns real Google results, no bot blocking)
        try {
            $sp = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml',
                'Accept-Language' => 'pl-PL,pl;q=0.9,en;q=0.8',
            ])->timeout(12)->get('https://www.startpage.com/search', ['q' => $query, 'cat' => 'web', 'language' => 'pl']);

            if ($sp->successful() && strlen($sp->body()) > 5000) {
                $body = $sp->body();
            }
        } catch (\Exception) {
        }

        // 2. Fallback: Brave Search
        if (!$body) {
            try {
                $brave = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'pl-PL,pl;q=0.9,en;q=0.8',
                ])->timeout(12)->get('https://search.brave.com/search', ['q' => $query]);

                if ($brave->successful()) {
                    $body = $brave->body();
                }
            } catch (\Exception) {
            }
        }

        if (!$body) {
            return response()->json(['website' => null, 'facebook' => null, 'email' => null, 'urls' => []]);
        }

        // Extract plain <a href> URLs
        $urls = [];
        preg_match_all('/<a[^>]+href="(https?:\/\/[^"]+)"/i', $body, $m);
        foreach ($m[1] ?? [] as $u) {
            // Decode HTML entities (e.g. &amp; in Play Store links)
            $u = html_entity_decode($u, ENT_QUOTES);
            if (strlen($u) > 15 && strlen($u) < 300) {
                $urls[] = $u;
            }
        }

        $skipDomains = ['startpage.com', 'ixquick.com', 'brave.com', 'duckduckgo.com',
            'w3.org', 'schema.org', 'apple.com', 'mozilla.org', 'microsoft.com',
            'google.com/search', 'google.com/maps/place'];
        $urls = array_unique(array_values(array_filter($urls, function ($u) use ($skipDomains) {
            foreach ($skipDomains as $s) {
                if (str_contains($u, $s)) {
                    return false;
                }
            }

            return true;
        })));

        $website = null;
        $facebook = null;
        $email = null;

        foreach ($urls as $u) {
            // Facebook
            if (!$facebook && preg_match('/facebook\.com\/[a-zA-Z0-9\.\-\_\/]{3,}/i', $u)) {
                $facebook = preg_replace('/[?&](fbclid|__cft__|__tn__)=[^&"]*/', '', $u);
                $facebook = rtrim($facebook, '?&');
            }
        }

        // Website: first non-directory, non-social URL
        $dirPattern = '/allegro|olx|ceneo|skąpiec|nokaut|merlin|empik|tripadvisor|yelp|google|facebook|instagram|twitter|youtube|duckduckgo|brave/i';
        foreach ($urls as $u) {
            if (!$website && !preg_match($dirPattern, $u)) {
                $website = $u;
                break;
            }
        }

        // Extract emails
        preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,6}/', $body, $em);
        $emailSkip = ['@duckduckgo', '@brave', '@w3', '@schema', '@example'];
        foreach ($em[0] ?? [] as $e) {
            $ok = true;
            foreach ($emailSkip as $s) {
                if (str_contains($e, $s)) {
                    $ok = false;
                }
            }
            if ($ok) {
                $email = $e;
                break;
            }
        }

        return response()->json([
            'website' => $website,
            'facebook' => $facebook,
            'email' => $email,
            'urls' => array_slice($urls, 0, 8),
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'city' => ['required', 'string', 'max:100'],
            'radius' => ['required', 'integer', 'in:1000,2000,5000,10000,20000,50000'],
        ]);

        $city = $request->city;
        $radius = (int) $request->radius;

        // 1. Geocode city → lat/lng via Nominatim
        $geo = Http::withHeaders([
            'User-Agent' => config('app.name', 'App') . '/1.0 (' . config('platform.contact_email') . ')',
            'Accept' => 'application/json',
        ])
            ->timeout(10)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $city,
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 1,
            ]);

        if ($geo->failed() || empty($geo->json())) {
            return response()->json(['error' => 'Nie znaleziono miasta „' . $city . '". Sprawdź pisownię.'], 422);
        }

        $place = $geo->json()[0];
        $lat = (float) $place['lat'];
        $lon = (float) $place['lon'];

        // 2. Overpass API — shops in radius (nodes only for speed)
        $overpassQuery = "[out:json][timeout:20];node[\"shop\"](around:{$radius},{$lat},{$lon});out body;";

        $endpoints = [
            'https://overpass-api.de/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
            'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
            'https://overpass.openstreetmap.ru/api/interpreter',
        ];

        $overpass = null;
        foreach ($endpoints as $url) {
            try {
                $resp = Http::withHeaders(['User-Agent' => 'Storelo/1.0'])
                    ->timeout(25)
                    ->asForm()
                    ->post($url, ['data' => $overpassQuery]);
                if ($resp->successful()) {
                    $overpass = $resp;
                    break;
                }
            } catch (\Exception) {
                continue;
            }
        }

        if (!$overpass || $overpass->failed()) {
            return response()->json(['error' => 'OpenStreetMap chwilowo niedostępny. Spróbuj za kilka minut.'], 503);
        }

        $elements = $overpass->json('elements') ?? [];

        $osmIds = collect($elements)->pluck('id')->toArray();

        // Upsert all found shops, attach contacted_at from DB
        $leads = ShopLead::whereIn('osm_id', $osmIds)
            ->get()
            ->keyBy('osm_id');

        $results = collect($elements)
            ->filter(fn ($el) => !empty($el['tags']['name']))
            ->map(function ($el) use ($leads) {
                $tags = $el['tags'];

                $website = $tags['website'] ?? $tags['contact:website'] ?? $tags['url'] ?? null;
                $facebook = $tags['contact:facebook'] ?? $tags['facebook'] ?? null;
                $email = $tags['email'] ?? $tags['contact:email'] ?? null;
                $phone = $tags['phone'] ?? $tags['contact:phone'] ?? null;

                // Normalize Facebook URL
                if ($facebook && !str_starts_with($facebook, 'http')) {
                    $facebook = 'https://facebook.com/' . ltrim($facebook, '/');
                }

                $lat = $el['lat'] ?? null;
                $lon = $el['lon'] ?? null;

                $shopType = $tags['shop'] ?? '';
                $typeLabel = match ($shopType) {
                    'clothes', 'fashion' => 'Odzież',
                    'electronics' => 'Elektronika',
                    'books' => 'Księgarnia',
                    'bakery' => 'Piekarnia',
                    'florist' => 'Kwiaciarnia',
                    'jewelry' => 'Jubiler',
                    'sports' => 'Sport',
                    'toys' => 'Zabawki',
                    'supermarket' => 'Supermarket',
                    'convenience' => 'Sklep spożywczy',
                    'beauty' => 'Kosmetyki',
                    'hairdresser' => 'Fryzjer',
                    'furniture' => 'Meble',
                    'hardware' => 'Narzędzia',
                    'optician' => 'Optyk',
                    'pharmacy' => 'Apteka',
                    default => 'Sklep',
                };

                return [
                    'id' => $el['id'],
                    'name' => $tags['name'],
                    'type' => $typeLabel,
                    'address' => trim(implode(', ', array_filter([
                        $tags['addr:street'] ?? null,
                        $tags['addr:housenumber'] ?? null,
                        $tags['addr:city'] ?? null,
                    ]))),
                    'website' => $website,
                    'facebook' => $facebook,
                    'email' => $email,
                    'phone' => $phone,
                    'osm_url' => "https://www.openstreetmap.org/{$el['type']}/{$el['id']}",
                    'maps_url' => $lat ? "https://www.google.com/maps?q={$lat},{$lon}" : null,
                    'has_contact' => $website || $facebook || $email || $phone,
                    'contacted_at' => $leads->get($el['id'])?->contacted_at?->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'city' => $place['display_name'],
            'lat' => $lat,
            'lon' => $lon,
            'count' => $results->count(),
            'results' => $results,
        ]);
    }

    public function toggleContacted(Request $request)
    {
        $request->validate([
            'osm_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $lead = ShopLead::firstOrCreate(
            ['osm_id' => $request->osm_id],
            [
                'name' => $request->name,
                'type' => $request->type,
                'address' => $request->address,
                'city' => $request->city,
                'website' => $request->website,
                'facebook' => $request->facebook,
                'email' => $request->email,
                'phone' => $request->phone,
                'maps_url' => $request->maps_url,
            ]
        );

        $lead->contacted_at = $lead->contacted_at ? null : now();
        $lead->save();

        return response()->json(['contacted_at' => $lead->contacted_at?->toIso8601String()]);
    }
}
