<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AddToCartRequest;
use App\Http\Requests\Api\UpdateCartRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = Cart::where('user_id', $request->user()->id)
            ->with('product')
            ->latest()
            ->get();

        return CartResource::collection($cartItems);
    }

    public function store(AddToCartRequest $request): JsonResponse
    {
        $user = $request->user();
        $productId = $request->product_id;
        $quantity = $request->quantity;

        $cart = Cart::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($cart) {
            $cart->increment('quantity', $quantity);
        } else {
            $cart = Cart::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        return response()->json([
            'message' => 'Produk berhasil ditambahkan ke keranjang.',
            'data' => new CartResource($cart->load('product')),
        ], 201);
    }

    public function update(UpdateCartRequest $request, Cart $cart): JsonResponse
    {
        if ($cart->user_id !== $request->user()->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $cart->update(['quantity' => $request->quantity]);

        return response()->json([
            'message' => 'Jumlah produk di keranjang berhasil diperbarui.',
            'data' => new CartResource($cart->load('product')),
        ]);
    }

    public function destroy(Request $request, Cart $cart): JsonResponse
    {
        if ($cart->user_id !== $request->user()->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $cart->delete();

        return response()->json(['message' => 'Produk berhasil dihapus dari keranjang.']);
    }
}
