<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductCOntroller extends Controller
{
    public function index($slug)
    {
        $product = Product::where('slug',$slug)->first();
        $product['actualprice'] = $product->mrp - ((($product->discount)/100)*( $product->mrp ) ) ;

        return view('frontend.description.index',['product'=>$product]);
    }
}
