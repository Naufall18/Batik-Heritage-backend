<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    /** Daftar UMKM (opsional filter kecamatan). */
    public function index(Request $request)
    {
        $vendors = Vendor::active()
            ->withCount('products')
            ->when($request->filled('kecamatan'), fn ($q) => $q->where('kecamatan', $request->kecamatan))
            ->orderBy('name')
            ->get();

        return VendorResource::collection($vendors);
    }

    /** Detail UMKM + produknya. */
    public function show(string $slug)
    {
        $vendor = Vendor::active()
            ->with(['products' => fn ($q) => $q->active()->with('images', 'category')])
            ->withCount('products')
            ->where('slug', $slug)
            ->firstOrFail();

        return new VendorResource($vendor);
    }

    /**
     * UMKM terdekat dari titik (lat,lng) dalam radius km — inti fitur "batik di sekitarku".
     */
    public function nearby(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'between:0.1,100'],
            'limit' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $radius = $data['radius'] ?? 10;
        $limit = $data['limit'] ?? 20;

        $vendors = Vendor::active()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectDistance((float) $data['lat'], (float) $data['lng'])
            ->withCount('products')
            ->having('distance_km', '<=', $radius)
            ->orderBy('distance_km')
            ->limit($limit)
            ->get();

        return VendorResource::collection($vendors);
    }
}
