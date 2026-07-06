<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'total_amount' => (float) $this->total_amount,
            'shipping_address' => $this->shipping_address,
            'phone' => $this->phone,
            'notes' => $this->notes,
            'payment_type' => $this->payment_type,
            'payment_method' => $this->payment_method ?? 'cod',
            'payment_status' => $this->payment_status,
            'transaction_id' => $this->transaction_id,
            'snap_token' => $this->snap_token,
            'paid_at' => $this->paid_at,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_price' => (float) $item->product_price,
                'quantity' => $item->quantity,
                'subtotal' => (float) $item->subtotal,
            ])),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
