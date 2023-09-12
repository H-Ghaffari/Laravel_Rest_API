<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\ProductImageResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id'=> $this->id,
            'name'=> $this->name,
            'brand_id'=> $this->brand_id,
            'category_id'=> $this->category_id,
            'primary_image'=> url(env('PRODUCT_IMAGES_UPLOAD_PATH') . $this->primary_image),
            'description'=> $this->description,
            'price'=> $this->price,
            'quantity'=> $this->quantity,
            'delivery_amount'=> $this->delivery_amount,
            'images' =>ProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}