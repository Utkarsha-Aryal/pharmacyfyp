<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class AdvancedController extends Controller
{
    public function index(){
        $manufacturers = Product::select('manufacturer')
    ->whereNotNull('manufacturer')
    ->distinct()
    ->pluck('manufacturer');
    $compositions = Product::select('composition')
    ->whereNotNull('composition')
    ->distinct()
    ->orderBy('composition')
    ->pluck('composition');


        return view('frontend.advanced.index',compact('manufacturers','compositions'));
    }


    public function selection( Request $request){
  $query = Product::query();

if (!empty($request->categories)) {
    $query->whereIn('category_id', $request->categories);
}

if (!empty($request->product_name)) {
    $query->where('product_name', 'like', '%' . $request->product_name . '%');
}

if (!empty($request->company_name)) {
    $query->where('manufacturer', $request->company_name);
}

if (!empty($request->composition)) {
    $query->where('composition', 'like', '%' . $request->composition . '%');
}

$products = $query->get();

$html = view('frontend.advanced.product_card', compact('products'))->render();

return response()->json(['html' => $html]);

    }

}
