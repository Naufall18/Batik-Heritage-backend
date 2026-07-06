<?php

namespace App\Http\Middleware;

use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasVendor
{
    public function handle(Request $request, Closure $next): Response
    {
        $vendor = Vendor::where('user_id', $request->user()->id)->first();

        if (!$vendor) {
            return response()->json(['message' => 'Anda belum memiliki toko UMKM.'], 403);
        }

        $request->merge(['vendor' => $vendor]);

        return $next($request);
    }
}
