<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Region;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Wilayah: Kota Pasuruan + beberapa kecamatan (koordinat perkiraan)
        $kota = Region::firstOrCreate(
            ['name' => 'Pasuruan', 'type' => 'kota'],
            ['latitude' => -7.6453, 'longitude' => 112.9075]
        );

        $kecamatanData = [
            ['name' => 'Bangil',       'lat' => -7.5966, 'lng' => 112.8149],
            ['name' => 'Rembang',      'lat' => -7.6236, 'lng' => 112.7539],
            ['name' => 'Pandaan',      'lat' => -7.6547, 'lng' => 112.6913],
            ['name' => 'Gadingrejo',   'lat' => -7.6412, 'lng' => 112.8817],
            ['name' => 'Bugul Kidul',  'lat' => -7.6602, 'lng' => 112.9201],
            ['name' => 'Purwosari',    'lat' => -7.7566, 'lng' => 112.7143],
        ];

        $kecamatan = [];
        foreach ($kecamatanData as $k) {
            $kecamatan[$k['name']] = Region::firstOrCreate(
                ['name' => $k['name'], 'type' => 'kecamatan', 'parent_id' => $kota->id],
                ['latitude' => $k['lat'], 'longitude' => $k['lng']]
            );
        }

        // 2) Kategori batik
        $categories = [];
        foreach ([
            ['name' => 'Kain', 'icon' => '🧵'],
            ['name' => 'Pakaian', 'icon' => '👕'],
            ['name' => 'Selendang & Shawl', 'icon' => '🧣'],
            ['name' => 'Aksesoris', 'icon' => '👜'],
        ] as $i => $c) {
            $categories[$c['name']] = Category::firstOrCreate(
                ['slug' => Str::slug($c['name'])],
                ['name' => $c['name'], 'icon' => $c['icon'], 'sort' => $i]
            );
        }

        // 3) UMKM (koordinat = pusat kecamatan + sedikit offset)
        $vendorsData = [
            ['name' => 'Batik Sedap Malam',   'kec' => 'Bangil',      'wa' => '6281200000001', 'desc' => 'Batik tulis khas Pasuruan dengan motif sedap malam.'],
            ['name' => 'Wastra Suropati',      'kec' => 'Rembang',     'wa' => '6281200000002', 'desc' => 'Mengangkat motif Pasedahan Suropati dan ragam pesisir.'],
            ['name' => 'Batik Bromo Asri',     'kec' => 'Pandaan',     'wa' => '6281200000003', 'desc' => 'Motif terinspirasi Gunung Bromo & Tengger.'],
            ['name' => 'Griya Batik Gadingrejo','kec' => 'Gadingrejo', 'wa' => '6281200000004', 'desc' => 'Batik cap & kombinasi untuk sehari-hari.'],
            ['name' => 'Batik Mangga Kencana', 'kec' => 'Bugul Kidul', 'wa' => '6281200000005', 'desc' => 'Ciri khas motif mangga, produk UMKM lokal.'],
            ['name' => 'Sanggar Batik Purwosari','kec' => 'Purwosari', 'wa' => '6281200000006', 'desc' => 'Sanggar batik tulis dengan pewarna alam.'],
        ];

        $motifs = ['Sedap Malam', 'Pasedahan Suropati', 'Bromo Tengger', 'Mangga', 'Sekar Jagad'];
        $techniques = ['tulis', 'cap', 'printing', 'kombinasi'];
        $productTypes = [
            'Kain' => ['Kain Batik', 'Kain Panjang', 'Sarung Batik'],
            'Pakaian' => ['Kemeja Batik Pria', 'Blus Batik Wanita', 'Outer Batik'],
            'Selendang & Shawl' => ['Selendang Batik', 'Pashmina Batik', 'Shawl Batik'],
            'Aksesoris' => ['Tas Batik', 'Dompet Batik', 'Ikat Kepala Batik'],
        ];
        $prices = ['tulis' => 450000, 'kombinasi' => 250000, 'cap' => 150000, 'printing' => 85000];

        // Foto batik asli (Unsplash, lisensi bebas). Dipilih deterministik per produk.
        // ponytail: foto representatif kain batik; foto produk sebenarnya diunggah UMKM via dashboard.
        $unsplash = fn (string $id) => "https://images.unsplash.com/photo-{$id}?w=600&q=80&fit=crop&auto=format";
        $batikImages = array_map($unsplash, [
            '1672716912554-c23ba8fac4ce', '1761515315375-1315503bb3ce', '1481325545291-94394fe1cf95',
            '1507434745378-235a6297156b', '1722957533029-6b62a3826d05', '1761516659497-8478e39d2b26',
            '1672716912467-fd99b71cf780', '1762111908858-201b9da429dc', '1761517099171-de5772a56956',
            '1761517099330-13b34b141d74',
        ]);

        foreach ($vendorsData as $vi => $vd) {
            $reg = $kecamatan[$vd['kec']];
            $vendor = Vendor::firstOrCreate(
                ['slug' => Str::slug($vd['name'])],
                [
                    'region_id' => $reg->id,
                    'name' => $vd['name'],
                    'description' => $vd['desc'],
                    'whatsapp' => $vd['wa'],
                    'address' => 'Jl. Batik No. ' . ($vi + 1) . ', ' . $vd['kec'],
                    'city' => 'Pasuruan',
                    'kecamatan' => $vd['kec'],
                    'latitude' => $reg->latitude + ($vi * 0.0009),
                    'longitude' => $reg->longitude + ($vi * 0.0009),
                    'is_active' => true,
                ]
            );

            // 4) 3 produk per UMKM, lintas kategori & teknik
            $catNames = array_keys($productTypes);
            for ($p = 0; $p < 3; $p++) {
                $catName = $catNames[($vi + $p) % count($catNames)];
                $type = $productTypes[$catName][$p % 3];
                $tech = $techniques[($vi + $p) % count($techniques)];
                $motif = $motifs[($vi + $p) % count($motifs)];
                $name = "{$type} Motif {$motif}";
                $slug = Str::slug($name . '-' . $vendor->id . '-' . $p);

                $product = Product::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'vendor_id' => $vendor->id,
                        'category_id' => $categories[$catName]->id,
                        'name' => $name,
                        'description' => "{$type} batik {$tech} dengan motif {$motif} dari {$vendor->name}, {$vd['kec']}, Pasuruan.",
                        'price' => $prices[$tech] + ($p * 15000),
                        'stock' => 5 + $p * 3,
                        'technique' => $tech,
                        'motif' => $motif,
                        'material' => 'Katun primisima',
                        'color' => 'Nila / Soga',
                        'is_active' => true,
                        'is_featured' => $p === 0,
                    ]
                );

                $product->images()->firstOrCreate(
                    ['is_primary' => true],
                    ['path' => $batikImages[crc32($slug) % count($batikImages)], 'sort' => 0]
                );
            }
        }
    }
}
