<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;

class ProductController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products= Product::paginate(3);
        return $this->successResponse([
            'products'=> ProductResource::collection($products->load('images')),
            'links'=> ProductResource::collection($products)->response()->getData()->links,
            'meta'=> ProductResource::collection($products)->response()->getData()->meta,
        ],200); 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name'=> 'required|string',
            'brand_id'=>'required|integer',
            'category_id'=>'required|integer',
            'primary_image'=>'required|image',
            'description'=>'required',
            'price'=>'integer',
            'quantity'=>'integer',
            'delivery_amount' =>'nullable|integer',
            'images.*'=>'nullable|image' //می توانیم محدودیت حجم قرار دهیم
        ]); 

        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        DB::beginTransaction();
        //ذخیره تصاویر
        $primaryImageName = Carbon::now()->microsecond . '.' .$request->primary_image->extension();
        $request->primary_image->storeAs('images/products',  $primaryImageName, 'public');
        //php artisan storage:link ممکنه این دستور در هاست اشتراکی کار نکند
        //راه حل \config\filesystems.php 42

        $otherImages=[];
        if($request->has('images')){
            foreach($request->images as $image){
                $imageName = Carbon::now()->microsecond . '.' .$image->extension();
                $image->storeAs('images/products',  $imageName, 'public');
                array_push($otherImages, $imageName);
            }
        }
         
        $product = Product::create([
            'name'=> $request->name,
            'brand_id'=> $request->brand_id,
            'category_id'=> $request->category_id,
            'primary_image'=> $primaryImageName,
            'description'=> $request->description,
            'price'=> $request->price,
            'quantity'=> $request->quantity,
            'delivery_amount'=> $request->delivery_amount,
            ]);

        foreach($otherImages as $image){
            ProductImage::create([
                'product_id' => $product->id,
                'image' => $image
            ]);
        }
        DB::commit();

        return $this->successResponse(new ProductResource($product),201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
         return $this->successResponse(new ProductResource($product->load('images')),200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        // dd($request->all());
         $validator = Validator::make($request->all(),[
            'name'=> 'required|string',
            'brand_id'=>'required|integer',
            'category_id'=>'required|integer',
            'primary_image'=>'nullable|image',
            'description'=>'required',
            'price'=>'integer',
            'quantity'=>'integer',
            'delivery_amount' =>'nullable|integer',
            'images.*'=>'nullable|image' //می توانیم محدودیت حجم قرار دهیم
        ]); 

        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        DB::beginTransaction();
        //ذخیره تصاویر
        if($request->has('primary_image')){
            $primaryImageName = Carbon::now()->microsecond . '.' .$request->primary_image->extension();
            $request->primary_image->storeAs('images/products',  $primaryImageName, 'public');
        }

        $otherImages=[];
        if($request->has('images')){
            foreach($request->images as $image){
                $imageName = Carbon::now()->microsecond . '.' .$image->extension();
                $image->storeAs('images/products',  $imageName, 'public');
                array_push($otherImages, $imageName);
            }
        }
         
        $product->update([
            'name'=> $request->name,
            'brand_id'=> $request->brand_id,
            'category_id'=> $request->category_id,
            'primary_image'=> $request->has('primary_image') ? $primaryImageName : $product->primary_image,
            'description'=> $request->description,
            'price'=> $request->price,
            'quantity'=> $request->quantity,
            'delivery_amount'=> $request->delivery_amount,
            ]);

        if($request->has('images')){
            foreach($product->images as $img){
                $img->delete();
            }
            foreach($otherImages as $image){
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $image
                ]);
            }
        }
        DB::commit();

        return $this->successResponse(new ProductResource($product->load('images')) , 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        DB::beginTransaction();
        $product->delete();
        DB::commit();
        return $this->successResponse(new ProductResource($product),200);
    }
}