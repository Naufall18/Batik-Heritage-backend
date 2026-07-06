<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Region;

class RegionController extends Controller
{
    /** Daftar kecamatan + jumlah UMKM aktif (untuk filter wilayah). */
    public function index()
    {
        $kecamatan = Region::where('type', 'kecamatan')
            ->withCount(['vendors' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $kecamatan->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'latitude' => $r->latitude,
                'longitude' => $r->longitude,
                'vendors_count' => $r->vendors_count,
            ]),
        ]);
    }
}
