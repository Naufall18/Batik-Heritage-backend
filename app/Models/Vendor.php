<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = [
        'user_id', 'region_id', 'name', 'slug', 'description', 'whatsapp',
        'address', 'city', 'kecamatan', 'kelurahan', 'latitude', 'longitude',
        'logo_path', 'cover_path', 'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Tambahkan kolom `distance_km` (Haversine) & urutkan dari titik (lat,lng).
     * MySQL 8 mendukung fungsi trigonometri yang dipakai di sini.
     */
    public function scopeSelectDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query->select('*')->selectRaw(
            '(6371 * acos(least(1, cos(radians(?)) * cos(radians(latitude)) '
            . '* cos(radians(longitude) - radians(?)) '
            . '+ sin(radians(?)) * sin(radians(latitude))))) AS distance_km',
            [$lat, $lng, $lat]
        );
    }
}
