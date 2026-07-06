<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'technique' => $this->technique,
            'motif' => $this->motif,
            'material' => $this->material,
            'color' => $this->color,
            'is_featured' => $this->is_featured,
            'images' => $this->whenLoaded('images', fn () => $this->images->pluck('path')),
            'primary_image' => $this->whenLoaded('images', fn () => optional($this->images->firstWhere('is_primary', true) ?? $this->images->first())->path),
            'category' => $this->whenLoaded('category', fn () => [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'vendor' => $this->whenLoaded('vendor', fn () => [
                'name' => $this->vendor->name,
                'slug' => $this->vendor->slug,
                'kecamatan' => $this->vendor->kecamatan,
                'latitude' => $this->vendor->latitude,
                'longitude' => $this->vendor->longitude,
            ]),
        ];
    }
}
