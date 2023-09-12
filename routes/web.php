<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//درگاه جواب را به این روت برمی گرداند
Route::get('/payment/verify', function(Request $request){ //call back url for dargah
    // dd($request->all());
    $response = Http::post('http://localhost:8000/api/payment/verify',[
        'token' => $request->token,
        'status' => $request->status
    ]); //توکن را برایم متد وریفای ای پی آی مان می فرستیم

    dd($response->json());
    // dd($response->body());
});