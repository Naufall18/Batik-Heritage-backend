<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'whatsapp' => $this->whatsapp,
            'address' => $this->address,
            'city' => $this->city,
            'kecamatan' => $this->kecamatan,
            'kelurahan' => $this->kelurahan,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'logo_path' => $this->logo_path,
            'cover_path' => $this->cover_path,
            // Ada hanya bila di-query lewat scopeSelectDistance (endpoint nearby)
            'distance_km' => $this->when(isset($this->distance_km), fn () => round((float) $this->distance_km, 2)),
            'products_count' => $this->whenCounted('products'),
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
