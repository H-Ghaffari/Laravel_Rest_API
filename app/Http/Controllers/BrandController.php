<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\BrandResource;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;

class BrandController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands= Brand::paginate(2);
        return $this->successResponse([
            'brands'=> BrandResource::collection($brands),
            'links'=> BrandResource::collection($brands)->response()->getData()->links,
            'meta'=> BrandResource::collection($brands)->response()->getData()->meta,
        ],200); 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name'=> 'required',
            'display_name' => 'required|unique:brands',
        ]); 

        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        //first approach
        // try {
        //     DB::beginTransaction();

        //     $brand = Brand::create([
        //         'name'=> $request->name,
        //         'display_name' => $request->display_name,
        //     ]);

        //     DB::commit();

        // }catch(\Exception $e) {
        //     DB::rollback();
        //     return $this->errorResponse($e->getMessage(), 500);
        // }

        //second approach
        //rest-api-laravel\app\Exceptions\Handler.php
            DB::beginTransaction();
            $brand = Brand::create([
                'name'=> $request->name,
                'display_name' => $request->display_name,
            ]);
            DB::commit();


        return $this->successResponse(new BrandResource($brand),201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Brand $brand)
    {
         return $this->successResponse(new BrandResource($brand),200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
    {
        $validator = Validator::make($request->all(),[
            'name'=> 'required',
            'display_name' => 'required|unique:brands',
        ]); 

        if($validator->fails()){
            return $this->errorResponse($validator->messages(), 422);
        }

        DB::beginTransaction();
            $brand->update([
                'name'=> $request->name,
                'display_name' => $request->display_name,
            ]);
        DB::commit();

        return $this->successResponse(new BrandResource($brand),200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Brand $brand)
    {
        DB::beginTransaction();
        $brand->delete(); //soft delete mishavad
        DB::commit();
        return $this->successResponse(new BrandResource($brand),200);
    }

     public function products(Brand $brand){
         return $this->successResponse(new BrandResource($brand->load('products')),200);
     }
}