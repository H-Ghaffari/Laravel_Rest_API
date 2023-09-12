<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public static function create($request, $amounts, $token){
        DB::beginTransaction();

        $order = Order::create([
            // "user_id" => $request['user_id'],
            "user_id" => $request->user_id,
            'total_amount' => $amounts['totalAmount'],
            'delivery_amount' => $amounts['deliveryAmount'],
            'paying_amount' => $amounts['payingAmount']
        ]);

        foreach($request->order_items as $orderItem){
            $product = Product::FindOrFail($orderItem['product_id']);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' =>  $product->id,
                'price' => $product->price,
                'quantity' => $orderItem['quantity'],
                'subtotal' => ($orderItem['quantity'] * $product->price)
            ]);
        }

        Transaction::create([
            'user_id' => $request->user_id,
            'order_id' => $order->id,
            'amount' => $amounts['payingAmount'],
            'token' =>  $token,
            'request_from' => $request->request_from
        ]);

        DB::commit();
    }

    public static function update($token, $transId){
         DB::beginTransaction();
         $transaction = Transaction::where('token', $token)->firstOrFail();
         $transaction->update([
            'status' => 1,
            'trans_id' => $transId
         ]);

         $order = Order::FindOrFail($transaction->order_id);
         $order->update([
            'status' => 1,
            'payment_status' => 1
         ]);

         foreach(OrderItem::where('order_id', $order->id)->get() as $item){
            $product = Product::FindOrFail($item->product_id);
            $product->update([
                'quantity' => ($product->quantity - $item->quantity)
            ]);
         }
         
         DB::commit();
    }
}