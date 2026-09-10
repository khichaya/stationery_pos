<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderSyncController extends Controller
{
    public function sync(Request $request)
    {
        if ($request->bearerToken() !== env('POS_API_TOKEN')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'order_number' => 'required|string',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'customer_address' => 'nullable|string',
            'total_amount' => 'required|numeric',
            'items' => 'required|array',
            'items.*.code' => 'required|string',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|integer',
            'items.*.unit_price' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $existingService = Service::where('service_type', 'like', '%' . $data['order_number'] . '%')->first();
            if ($existingService) {
                return response()->json(['message' => 'Order already synced'], 200);
            }

            $itemNames = collect($data['items'])->map(function($item) {
                return $item['quantity'] . 'x ' . $item['name'];
            })->implode(' | ');

            // ✅ تجميع معلومات الزبون في نص واحد
            $shippingInfo = "Nom: {$data['customer_name']}\nTél: {$data['customer_phone']}\nAdresse: {$data['customer_address']}";

            $service = Service::create([
                'service_type' => 'Commande Web ' . $data['order_number'] . ' (' . $itemNames . ')',
                'shipping_info' => $shippingInfo, // ✅ حفظ المعلومات هنا
                'price' => $data['total_amount'],
                'user_id' => 1, 
                'customer_id' => null,
                'payment_method' => 'debt',
                'paid_amount' => 0,
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::where('code', $item['code'])->first();
                if ($product) {
                    $product->decrement('current_stock', $item['quantity']);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Order synced successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("POS Order Sync Error: " . $e->getMessage());
            return response()->json(['message' => 'Server Error'], 500);
        }
    }
}