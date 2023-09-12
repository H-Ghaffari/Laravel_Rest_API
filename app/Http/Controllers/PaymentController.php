<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Validator;

class PaymentController extends ApiController
{
    //https://docs.pay.ir/gateway/#%D9%86%D9%85%D9%88%D9%86%D9%87-%DA%A9%D8%AF-php
    public function send(Request $request){

        $validator = Validator::make($request->all(),[
            "user_id" => 'required',
            "order_items" => 'required',
            "order_items.*.product_id" => 'required|integer',
            "order_items.*.quantity" => 'required|integer',
            "request_from" => 'required'
        ]);

        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        $totalAmount=0;
        $deliveryAmount=0;
        foreach($request->order_items as $orderItem){
            $product = Product::FindOrFail($orderItem['product_id']);
            if($product->quantity < $orderItem['quantity']){
                 return $this->errorResponse('the product quantity is incorrect', 422);
            }
            $totalAmount += $orderItem['quantity'] * $product->price;
            $deliveryAmount += $product->delivery_amount;
        }

        $payingAmount =  $totalAmount +  $deliveryAmount;

        // dd( $totalAmount, $deliveryAmount, $payingAmount);
        $amounts = [
            'totalAmount' => $totalAmount,
            'deliveryAmount' => $deliveryAmount,
            'payingAmount' => $payingAmount
        ];

        $api = env('PAY_IR_API_KEY');
        $amount = $payingAmount.'0';
        $mobile = "شماره موبایل";
        $factorNumber = "شماره فاکتور";
        $description = "توضیحات";
        //ارسال کن به یک صفحه وب به ای پی آی من درخواست را برنگردان
        //وقتی کاربر پرداختش موفق یا ناموفق هست ری دایرکت بشه به یک صفحه وب و توی آن صفحه وب
        //درخواست ارسال بشه به ای پی آی من
        $redirect = env('PAY_IR_CALLBACK_URL');
        $result = $this->sendRequest($api, $amount, $redirect, $mobile, $factorNumber, $description);
        $result = json_decode($result);
        // dd($result);
        if($result->status) { //یک توکن برای شناسایی کاربر می ده
            OrderController::create($request, $amounts, $result->token);
            $go = "https://pay.ir/pg/$result->token";
            // header("Location: $go");
            return $this->successResponse([
                'url' => $go
            ],200);
        } else {
            // echo $result->errorMessage;
             return $this->errorResponse($result->errorMessage,422);
        }
    }

    public function verify(Request $request){

        $validator = Validator::make($request->all(),[
            "token" => 'required',
            "status" => 'required'
        ]);

        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }
        
        $api = env('PAY_IR_API_KEY');
        // $token = $_GET['token'];
        $token = $request->token;
        $result = json_decode($this->verifyRequest($api,$token)); //جواب نهایی درگاه
        // return response()->json($result);
        if(isset($result->status)){
            if($result->status == 1){
                // echo "<h1>تراکنش با موفقیت انجام شد</h1>";
                if(Transaction::where('trans_id', $result->transId)->exists()){
                     return $this->errorResponse('این تراکنش قبلا توی سیستم ثبت شده است', 422);
                }
                OrderController::update($token, $result->transId);
                return $this->successResponse('تراکنش با موفقیت انجام شد', 200);
            } else {
                // echo "<h1>تراکنش با خطا مواجه شد</h1>";
                return $this->errorResponse('تراکنش با خطا مواجه شد', 422);
            }
        } else {
            // if($_GET['status'] == 0){
            if($request->status == 0){
                // echo "<h1>تراکنش با خطا مواجه شد</h1>";
                return $this->errorResponse('تراکنش با خطا مواجه شد', 422);
            }
        }
    }
    
    protected function sendRequest($api, $amount, $redirect, $mobile = null, $factorNumber = null, $description = null) {
        return $this->curl_post('https://pay.ir/pg/send', [
            'api'          => $api,
            'amount'       => $amount,
            'redirect'     => $redirect,
            'mobile'       => $mobile,
            'factorNumber' => $factorNumber,
            'description'  => $description,
        ]);
    }

    protected function curl_post($url, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

    protected function verifyRequest($api, $token) {
        return $this->curl_post('https://pay.ir/pg/verify', [
            'api' 	=> $api,
            'token' => $token,
        ]);
}
}