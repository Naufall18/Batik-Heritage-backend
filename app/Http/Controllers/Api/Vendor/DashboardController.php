<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\StoreProductRequest;
use App\Http\Requests\Api\Vendor\UpdateProductRequest;
use App\Http\Requests\Api\Vendor\UpdateOrderStatusRequest;
use App\Http\Requests\Api\Vendor\UpdateVendorProfileRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\Vendor\DashboardStatsResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    private function getVendor(): Vendor
    {
        return request()->get('vendor');
    }

    public function index()
    {
        return new DashboardStatsResource($this->getVendor());
    }

    public function products()
    {
        $vendor = $this->getVendor();

        $products = $vendor->products()
            ->with('images', 'category')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return ProductResource::collection($products);
    }

    public function storeProduct(StoreProductRequest $request)
    {
        $vendor = $this->getVendor();

        $data = $request->validated();

        $slug = Str::slug($data['name']);
        $original = $slug;
        $counter = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }
        $data['slug'] = $slug;

        $product = $vendor->products()->create($data);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $i => $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'path' => $path,
                    'is_primary' => $i === 0,
                    'sort' => $i,
                ]);
            }
        }

        $product->load('images', 'category');

        return response()->json(new ProductResource($product), 201);
    }

    public function updateProduct(UpdateProductRequest $request, Product $product)
    {
        $vendor = $this->getVendor();

        abort_if($product->vendor_id !== $vendor->id, 403, 'Product does not belong to your vendor');

        $data = $request->validated();

        if ($request->filled('name') && $request->name !== $product->name) {
            $slug = Str::slug($request->name);
            $original = $slug;
            $counter = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $original . '-' . $counter++;
            }
            $data['slug'] = $slug;
        }

        $product->update($data);

        if ($request->hasFile('images')) {
            foreach ($product->images as $img) {
                if (Storage::disk('public')->exists($img->path)) {
                    Storage::disk('public')->delete($img->path);
                }
            }
            $product->images()->delete();

            foreach ($request->file('images') as $i => $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'path' => $path,
                    'is_primary' => $i === 0,
                    'sort' => $i,
                ]);
            }
        }

        $product->load('images', 'category');

        return response()->json(new ProductResource($product));
    }

    public function destroyProduct(Product $product)
    {
        $vendor = $this->getVendor();

        abort_if($product->vendor_id !== $vendor->id, 403, 'Product does not belong to your vendor');

        foreach ($product->images as $img) {
            if (Storage::disk('public')->exists($img->path)) {
                Storage::disk('public')->delete($img->path);
            }
        }
        $product->images()->delete();
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }

    public function orders()
    {
        $vendor = $this->getVendor();

        $productIds = $vendor->products()->pluck('id');

        $orderIds = OrderItem::whereIn('product_id', $productIds)
            ->distinct()
            ->pluck('order_id');

        $orders = Order::whereIn('id', $orderIds)
            ->with('items', 'user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function updateOrderStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $vendor = $this->getVendor();

        $productIds = $vendor->products()->pluck('id');

        $belongsToVendor = OrderItem::where('order_id', $order->id)
            ->whereIn('product_id', $productIds)
            ->exists();

        abort_if(!$belongsToVendor, 403, 'Order tidak mengandung produk Anda');

        $order->update($request->validated());
        $order->load('items', 'user');

        return response()->json(new OrderResource($order));
    }

    public function profile()
    {
        $vendor = $this->getVendor();
        $vendor->load('user');

        return response()->json([
            'vendor' => $vendor,
            'user' => $vendor->user->only(['name', 'email', 'phone', 'address']),
        ]);
    }

    public function updateProfile(UpdateVendorProfileRequest $request)
    {
        $vendor = $this->getVendor();

        $vendor->update($request->only([
            'name', 'description', 'whatsapp', 'address',
            'kecamatan', 'kelurahan', 'latitude', 'longitude',
        ]));

        $user = $vendor->user;
        $userUpdate = $request->only(['name', 'phone']);
        if ($request->filled('address')) {
            $userUpdate['address'] = $request->address;
        }
        $user->update($userUpdate);

        if ($request->hasFile('logo')) {
            if ($vendor->logo_path && Storage::disk('public')->exists($vendor->logo_path)) {
                Storage::disk('public')->delete($vendor->logo_path);
            }
            $vendor->update(['logo_path' => $request->file('logo')->store('vendors', 'public')]);
        }

        if ($request->hasFile('cover')) {
            if ($vendor->cover_path && Storage::disk('public')->exists($vendor->cover_path)) {
                Storage::disk('public')->delete($vendor->cover_path);
            }
            $vendor->update(['cover_path' => $request->file('cover')->store('vendors', 'public')]);
        }

        $vendor->load('user');

        return response()->json([
            'vendor' => $vendor,
            'user' => $vendor->user->only(['name', 'email', 'phone', 'address']),
        ]);
    }
}
