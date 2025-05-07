<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
use App\Models\product;
use Illuminate\Support\Str;
class ProductController
{
    public function show($id)
    {
        $product = Product::where('slug', $id)->orWhere('id', $id)->first();
        return response()->json([
            'product' => $product
        ]);
    }
    
    public function addProduct(Request $request)
    {

        $request ->validate([
            'title' => 'required|string',
            'photo' => 'nullable|string',
            'quantity' =>'nullable|numeric',
            'description' =>'nullable|string',
            'summary' => 'nullable|string',
            
            'price'=> 'numeric',

        ]);
        $data =$request->all();
    
        $slug =Str::slug($request ->input('title'));
        $slug_count =Product::where('slug','=', $slug)->count();
        if($slug_count > 0)
        {
            $slug .=time();
        }
        $data['slug'] = $slug;

   
    $product =Product::create($data);

    return response()->json([
        'product' => $product
     ]);
    }
}