<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Katalog produk + filter: kategori, kecamatan, teknik, motif, cari, unggulan, urut.
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->active()
            ->with(['images', 'category', 'vendor'])
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $request->category)))
            ->when($request->filled('technique'), fn ($q) => $q->where('technique', $request->technique))
            ->when($request->filled('motif'), fn ($q) => $q->where('motif', 'like', '%' . $request->motif . '%'))
            ->when($request->filled('kecamatan'), fn ($q) => $q->whereHas('vendor', fn ($v) => $v->where('kecamatan', $request->kecamatan)))
            ->when($request->filled('vendor'), fn ($q) => $q->whereHas('vendor', fn ($v) => $v->where('slug', $request->vendor)))
            ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('motif', 'like', "%{$term}%"));
            });

        match ($request->get('sort')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            default => $query->latest(),
        };

        return ProductResource::collection($query->paginate(12)->withQueryString());
    }

    /** Detail produk by slug. */
    public function show(string $slug)
    {
        $product = Product::active()
            ->with(['images', 'category', 'vendor'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProductResource($product);
    }
}
