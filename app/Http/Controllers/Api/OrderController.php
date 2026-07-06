<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Services\MidtransService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        protected MidtransService $midtrans
    ) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();

        $cartItems = Cart::where('user_id', $user->id)
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Keranjang belanja kosong.'], 422);
        }

        $paymentMethod = $request->payment_method ?? 'cod';

        if ($paymentMethod === 'midtrans' && !$this->midtrans->isAvailable()) {
            return response()->json([
                'message' => 'Pembayaran Midtrans belum tersedia. Silakan pilih COD atau hubungi admin.',
            ], 422);
        }

        // Cek stok sebelum checkout
        foreach ($cartItems as $cartItem) {
            if ($cartItem->product->stock < $cartItem->quantity) {
                return response()->json([
                    'message' => "Stok {$cartItem->product->name} tidak mencukupi. Tersedia: {$cartItem->product->stock}.",
                ], 422);
            }
        }

        $totalAmount = $cartItems->sum(fn ($item) => $item->product->price * $item->quantity);

        $orderNumber = 'INV/' . now()->format('Ymd') . '/' . str_pad(Order::whereDate('created_at', now())->count() + 1, 5, '0', STR_PAD_LEFT);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'shipping_address' => $request->shipping_address,
            'phone' => $request->phone,
            'notes' => $request->notes,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentMethod === 'cod' ? 'cod' : 'unpaid',
            'status' => $paymentMethod === 'cod' ? 'processing' : 'pending',
        ]);

        foreach ($cartItems as $cartItem) {
            $order->items()->create([
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product->name,
                'product_price' => $cartItem->product->price,
                'quantity' => $cartItem->quantity,
                'subtotal' => $cartItem->product->price * $cartItem->quantity,
            ]);

            // Kurangi stok
            $cartItem->product->decrement('stock', $cartItem->quantity);
        }

        Cart::where('user_id', $user->id)->delete();

        if ($paymentMethod === 'midtrans' && $this->midtrans->isAvailable()) {
            $itemDetails = $cartItems->map(fn ($item) => [
                'id' => (string) $item->product_id,
                'name' => $item->product->name,
                'price' => (int) $item->product->price,
                'quantity' => $item->quantity,
            ])->toArray();

            $customerDetails = [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $request->phone,
            ];

            $snapToken = $this->midtrans->createSnapToken([
                'transaction_details' => [
                    'order_id' => $orderNumber,
                    'gross_amount' => (int) $totalAmount,
                ],
                'item_details' => $itemDetails,
                'customer_details' => $customerDetails,
            ]);

            $order->update(['snap_token' => $snapToken]);
        }

        $response = new OrderResource($order->load('items'));

        return response()->json([
            'message' => 'Pesanan berhasil dibuat.',
            'data' => $response,
        ], 201);
    }

    public function history(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate(10);

        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403, 'Aksi tidak diizinkan.');
        }

        return response()->json(new OrderResource($order->load('items')));
    }

    public function notification(Request $request): JsonResponse
    {
        try {
            $notification = $this->midtrans->handleNotification();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid notification'], 400);
        }

        $orderNumber = $notification['order_id'];
        $transactionStatus = $notification['transaction_status'];
        $paymentType = $notification['payment_type'];
        $transactionId = $notification['transaction_id'];

        $order = Order::where('order_number', $orderNumber)->first();

        if (! $order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Verifikasi signature_key jika server key tersedia
        $serverKey = config('midtrans.server_key');
        if (!empty($serverKey) && isset($notification['signature_key'])) {
            $expected = hash(
                'sha512',
                $notification['order_id']
                    . $notification['status_code']
                    . $notification['gross_amount']
                    . $serverKey
            );
            if ($notification['signature_key'] !== $expected) {
                return response()->json(['message' => 'Invalid signature'], 400);
            }
        }

        $order->update([
            'payment_type' => $paymentType,
            'transaction_id' => $transactionId,
        ]);

        match ($transactionStatus) {
            'settlement', 'capture' => $order->update([
                'payment_status' => 'paid',
                'status' => 'processing',
                'paid_at' => now(),
            ]),
            'deny', 'cancel', 'expire' => $order->update([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ]),
            default => $order->update(['payment_status' => 'unpaid']),
        };

        return response()->json(['message' => 'OK']);
    }
}
