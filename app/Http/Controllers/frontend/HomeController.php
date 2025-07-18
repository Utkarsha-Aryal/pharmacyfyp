<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;


class HomeController extends Controller
{
    public function index(){
        $categories = Category::with('products')->get(); // Eager load products
        foreach ($categories as $category) {
    foreach ($category->products as $product) {
        $product->final_price = $product->mrp - ($product->mrp* $product->discount/100);
    }



        return view('frontend.home.index',compact('categories')); 
    }
    
}
}
