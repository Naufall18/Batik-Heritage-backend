<?php

namespace App\Http\Resources\Vendor;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $productIds = $this->products()->pluck('id');

        $totalOrders = OrderItem::whereIn('product_id', $productIds)
            ->distinct()
            ->count('order_id');

        $totalRevenue = OrderItem::whereIn('product_id', $productIds)
            ->whereHas('order', fn ($q) => $q->where('payment_status', 'paid'))
            ->sum('subtotal');

        $pendingOrders = OrderItem::whereIn('product_id', $productIds)
            ->whereHas('order', fn ($q) => $q->where('status', 'pending'))
            ->distinct()
            ->count('order_id');

        return [
            'total_products' => $this->products()->count(),
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'pending_orders' => $pendingOrders,
        ];
    }
}
