<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::paginate(6);

        foreach ($orders->items() as $order) {
            $order->username = $order->user ? $order->user->username : null;
            unset($order->user); // Loại bỏ category_id nếu không cần
        }

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'address_ship' => 'required|string',
            'order_details' => 'required|array',
            'order_details.*.product_id' => 'required|exists:products,id',
            'order_details.*.quantity' => 'required|integer|min:1',
            'order_details.*.price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Tính tổng tiền
        $totalPrice = collect($request->order_details)->sum(function ($detail)
        {
            return $detail['quantity'] * $detail['price'];
        });

        // Tạo đơn hàng
        $order = Order::create([
            'user_id' => $request->user_id,
            'address_ship' => $request->address_ship,
            'total_price' => $totalPrice,
        ]);

        // Tạo chi tiết đơn hàng
        foreach ($request->order_details as $detail) {
            $order->orderDetails()->create($detail);
        }

        return response()->json($order->load('orderDetails'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Tìm đơn hàng theo ID, kèm theo chi tiết sản phẩm
        $order = Order::with('orderDetails')->find($id);

        // Kiểm tra nếu đơn hàng không tồn tại
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Trả về thông tin đơn hàng và chi tiết sản phẩm
        return response()->json($order, 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
