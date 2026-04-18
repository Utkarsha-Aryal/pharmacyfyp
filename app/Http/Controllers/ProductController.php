<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;
use App\Models\Common;
use App\Models\DropdownOption;
use App\Models\Product;
use App\Models\Unit;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // Keep only one main product page so users do not have to guess between duplicate product screens.
    public function index()
    {
        return view('product.index', [
            'companies' => Company::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('unit_name')->get(),
            'saleUnits' => Unit::query()->forSales()->orderBy('unit_name')->get(),
            'purchaseUnits' => Unit::query()->forPurchase()->orderBy('unit_name')->get(),
            'productStatuses' => DropdownOption::query()->forAlias('product_status')->active()->orderBy('name')->get(),
            'formulations' => DropdownOption::query()->forAlias('formulation')->active()->orderBy('name')->get(),
        ]);
    }

    // Old inventory products link now points back to the same unified product page.
    public function inventoryIndex()
    {
        return redirect()->route('admin.product', request()->only('company_id'));
    }


public function globalSearch(Request $request)
{
    $query = $request->input('query');

    $products = Product::where('product_name', 'like', '%' . $query . '%')
        ->limit(10)
        ->get();

    $html = '';

    foreach ($products as $product) {
        $imageUrl = asset('storage/product/' . $product->image);
        $slug = $product->slug;

        $html .= '<a href="' . route('product',$slug) . '" class="list-group-item list-group-item-action d-flex align-items-center">'
            . '<img src="' . $imageUrl . '" alt="' . e($product->product_name) . '" class="me-2" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">'
            . '<span>' . e($product->product_name) . '</span>'
            . '</a>';

    }

    return response()->json(['html' => $html]);
}


    public function form(Request $request)
    {
        try {
            $post = $request->all();
            $companies = Company::all();
            $units = Unit::query()->orderBy('unit_name')->get();
            $prevPost = [];

            if (!empty($post['id'])) {
                $prevPost = Product::find($post['id']);

                if (!$prevPost) {
                    throw new \Exception("Couldn't find product details.", 1);
                }
            }

            $data = [
                'companies' => $companies,
                'prevPost' => $prevPost,
                'unit' => $units,
                'saleUnits' => $units->whereIn('type', ['sales', 'both'])->values(),
                'purchaseUnits' => $units->whereIn('type', ['purchase', 'both'])->values(),
                'productStatuses' => DropdownOption::query()->forAlias('product_status')->active()->orderBy('name')->get(),
                'formulations' => DropdownOption::query()->forAlias('formulation')->active()->orderBy('name')->get(),
            ];

            if (!empty($prevPost) && $prevPost->image) {
                $data['image'] = '<img src="' . asset('/storage/product/' . $prevPost->image) . '" class="_image" height="160px" width="160px" alt="No image"/>';
            } else {
                $data['image'] = '<img src="' . asset('/no-image.jpg') . '" class="_image" height="160px" width="160px" alt="No image"/>';
            }

            $data['type'] = 'success';
            $data['message'] = 'Successfully got data.';
        } catch (QueryException $e) {
            $data['type'] = 'error';
            $data['message'] = $this->queryMessage;
        } catch (Exception $e) {
            $data['type'] = 'error';
            $data['message'] = $e->getMessage();
        }

        return view('product.form', $data);
    }


    // Product save now has backend validation too, not only html required attributes.
    public function save(Request $request)
    {
        try{
            $validated = $request->validate([
                'id' => ['nullable', 'exists:products,id'],
                'company_id' => ['required', 'exists:companies,id'],
                'unit_sale_id' => ['required', Rule::exists('units', 'id')->where(fn ($query) => $query->whereIn('type', ['sales', 'both']))],
                'unit_purchase_id' => ['required', Rule::exists('units', 'id')->where(fn ($query) => $query->whereIn('type', ['purchase', 'both']))],
                'product_code' => ['nullable', 'string', 'max:100', Rule::unique('products', 'product_code')->ignore($request->input('id'))],
                'product_name' => ['required', 'string', 'max:255'],
                'generic_name' => ['nullable', 'string', 'max:255'],
                'composition' => ['nullable', 'string', 'max:255'],
                'group_name' => ['nullable', 'string', 'max:255'],
                'manufacturer' => ['nullable', 'string', 'max:255'],
                'product_status_id' => ['nullable', Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'product_status'))],
                'formulation_id' => ['nullable', Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'formulation'))],
                'unit' => ['nullable', 'string', 'max:100'],
                'reorder_level' => ['nullable', 'integer', 'min:0'],
                'previous_price' => ['nullable', 'numeric', 'min:0'],
                'mrp' => ['required', 'numeric', 'min:0'],
                'cc_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'purchase_price' => ['nullable', 'numeric', 'min:0'],
                'keywords' => ['nullable', 'string'],
                'description' => ['required', 'string'],
                'image' => ['nullable', 'image', 'max:5120'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $post = array_merge($request->all(), $validated);
            $type = 'success';
            $message = 'Product saved successfully';
            DB::beginTransaction();
            $result = Product::saveData($post);
            if (!$result) {
                throw new Exception('Could not save record', 1);
            }
            DB::commit();
        }catch(QueryException $e){
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();

        }catch(Exception $e){
             DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        
        }
     

        return response()->json(['type' => $type, 'message' => $message]);

    }

    // Quick product save is used from billing screens so users can add a missing product without leaving the page.
    public function quickStore(Request $request)
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'unit_sale_id' => ['required', Rule::exists('units', 'id')->where(fn ($query) => $query->whereIn('type', ['sales', 'both']))],
            'unit_purchase_id' => ['required', Rule::exists('units', 'id')->where(fn ($query) => $query->whereIn('type', ['purchase', 'both']))],
            'product_name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'product_status_id' => ['nullable', Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'product_status'))],
            'formulation_id' => ['nullable', Rule::exists('dropdown_options', 'id')->where(fn ($query) => $query->where('alias', 'formulation'))],
            'mrp' => ['required', 'numeric', 'min:0'],
            'cc_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $company = Company::query()->find($validated['company_id']);
        $inputCcRate = (float) ($validated['cc_rate'] ?? 0);
        $defaultCompanyCcRate = (float) ($company?->default_cc_rate ?? 0);
        $ccRate = ($inputCcRate > 0 || $defaultCompanyCcRate <= 0) ? $inputCcRate : $defaultCompanyCcRate;

        $product = Product::query()->create([
            'company_id' => $validated['company_id'],
            'sale_unit_id' => $validated['unit_sale_id'],
            'purchase_unit_id' => $validated['unit_purchase_id'],
            'name' => $validated['product_name'],
            'product_name' => $validated['product_name'],
            'generic_name' => $validated['generic_name'] ?? null,
            'manufacturer' => $validated['manufacturer'] ?? null,
            'formulation_id' => $validated['formulation_id'] ?? null,
            'formulation' => DropdownOption::query()->find($validated['formulation_id'] ?? null)?->name,
            'mrp' => round((float) $validated['mrp'], 2),
            'cc_rate' => round($ccRate, 2),
            'purchase_price' => round((float) ($validated['purchase_price'] ?? 0), 2),
            'reorder_level' => $validated['reorder_level'] ?? 10,
            'alert_quantity' => $validated['reorder_level'] ?? 10,
            'description' => $validated['description'] ?? ('Quick product created for billing: ' . $validated['product_name']),
            'product_status_id' => $validated['product_status_id'] ?? DropdownOption::findIdByAliasAndName('product_status', 'In Stock'),
            'product_status' => Product::legacyStatusCode(
                DropdownOption::query()->find($validated['product_status_id'] ?? null)?->name ?? 'In Stock'
            ),
            'status' => 'Y',
            'is_active' => true,
            'slug' => Str::slug($validated['product_name']) . '-' . Str::lower(Str::random(8)),
        ]);

        return response()->json([
            'type' => 'success',
            'message' => 'Product added successfully.',
            'data' => [
                'id' => $product->id,
                'text' => $product->display_name,
                'mrp' => round((float) $product->mrp, 2),
                'cc_rate' => round((float) $product->effective_cc_rate, 2),
                'purchase_price' => round((float) ($product->purchase_price ?? 0), 2),
            ],
        ]);
    }

  public function list(Request $request)
    {
        try {
            $post = $request->all();
            $data = Product::list($post);
            $i = 0;
            $array = [];
            $filtereddata = ($data['totalfilteredrecs'] > 0 ? $data['totalfilteredrecs'] : $data['totalrecs']);
            $totalrecs = $data['totalrecs'];
            unset($data['totalfilteredrecs']);
            unset($data['totalrecs']);
            foreach ($data as $row) {
                $mrp = $row->mrp;
                $discount = $row->discount;
                $price = round($mrp - ($mrp * $discount / 100), 2);
                $currentStock = $row->batches->sum('quantity_available');
                if ($currentStock <= 0) {
                    $currentStock = $row->productBatches->sum('quantity');
                }

                $array[$i]['sno'] = $i + 1;
                $array[$i]['product_name'] = $row->display_name;
                $array[$i]['company'] = $row->company?->name ?? '-';
                $array[$i]['stock_quantity'] = $currentStock;
                $array[$i]['generic_name'] = $row->generic_name;
                $array[$i]['formulation'] = $row->formulation_label ?: '-';
                $array[$i]['unit'] = $row->unit ?: '-';
                $array[$i]['reorder_level'] = $row->reorder_level ?? $row->alert_quantity ?? 10;
                $array[$i]['mrp'] = money_value($row->mrp ?? 0);
                $array[$i]['cc_rate'] = number_format((float) ($row->effective_cc_rate ?? 0), 2) . '%';
                $array[$i]['description'] = $row->description;
                $array[$i]['keywords'] = Str::limit($row->keywords, 25, '...');
                $array[$i]['display_price'] =$price;
                // $array[$i]['stock_quantity'] = $row->stock_quantity;
                // $array[$i]['sold_qty'] = $row->orderDetails->sum('qty');
                // $array[$i]['available_qty'] = $row->stock_quantity - $row->orderDetails->sum('qty');
                $image = asset('images/no-image.jpg');
                if (!empty($row->image) && file_exists(public_path('storage/product/' . $row->image))) {
                    $image = asset("storage/product/" . $row->image);
                }
                $array[$i]["image"] = '<img src="' . $image . '" height="30px" width="30px" alt="image"/>';
                if (!empty($post['type']) && $post['type'] === 'trashed') {
                    // Trashed rows only show a label, no toggle.
                    $array[$i]['status'] = '<span class="report-badge report-badge-danger">Trashed</span>';
                } else {
                    // Put the active toggle inside the Status column so the action column stays clean.
                    $array[$i]['status'] = '<form action="' . route('admin.product.toggle-active', $row->id) . '" method="POST" class="d-inline js-confirm-submit" data-confirm-title="' . ($row->is_active ? 'Deactivate this product?' : 'Activate this product?') . '" data-confirm-text="This will change the product availability in the " data-confirm-button="Yes, update status">' . csrf_field() . '<button type="submit" class="btn btn-sm ' . ($row->is_active ? 'btn-outline-success' : 'btn-outline-danger') . ' table-action-btn status-toggle-btn" title="' . ($row->is_active ? 'Deactivate Product' : 'Activate Product') . '" aria-label="' . ($row->is_active ? 'Deactivate Product' : 'Activate Product') . '"><i class="fa-solid ' . ($row->is_active ? 'fa-toggle-on' : 'fa-toggle-off') . '"></i></button></form>';
                }
                $action = '<div class="table-action-group">';
                if (!empty($post['type']) && $post['type'] != 'trashed') {
                    $action .= '<button type="button" class="btn btn-sm btn-outline-success table-action-btn viewProduct" title="View Product" data-id="' . $row->id . '"><i class="fa-solid fa-eye"></i></button>';
                    $action .= '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn editNews" title="Edit Product" data-id="' . $row->id . '" data-name="' . $row->product_name . '"><i class="fa-solid fa-pen-to-square"></i></button>';
                    $action .= '<a href="' . route('admin.batch', $row->slug) . '" class="btn btn-sm btn-outline-info table-action-btn addBatch" title="Batch History"><i class="fa-solid fa-boxes-stacked"></i></a>';

                } else if (!empty($post['type']) && $post['type'] == 'trashed') {
                    $action .= '<button type="button" class="btn btn-sm btn-outline-success table-action-btn restoreProduct" title="Restore Product" data-id="' . $row->id . '"><i class="fa-solid fa-rotate-left"></i></button>';
                }
                
                    $action .= '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn deleteNews" title="Delete Product" data-id="' . $row->id . '"><i class="fa-solid fa-trash"></i></button>';
                    $action .= '</div>';

                $array[$i]['action'] = $action;
                $i++;
            }
            if (!$filtereddata)
                $filtereddata = 0;
            if (!$totalrecs)
                $totalrecs = 0;
        } catch (QueryException $e) {
            $array = [];
            $totalrecs = 0;
            $filtereddata = 0;
        } catch (Exception $e) {
            $array = [];
            $totalrecs = 0;
            $filtereddata = 0;
        }
        return response()->json(['recordsFiltered' => $filtereddata, 'recordsTotal' => $totalrecs, 'data' => $array]);
    }


    public function view(Request $request)
    {
         try {
            $post = $request->all();
            $prevPost = Product::where('id', $post['id'])
                ->where('status', 'Y')
                ->first();

            $company = Company::where('id', $prevPost['company_id'])->where('status', 'Y')->first();


            $data = [
                'prevPost' => $prevPost,
                'company' => $company
            ];

            $data['type'] = 'success';
            $data['message'] = 'Successfully fetched data of portfolio.';
        } catch (QueryException $e) {
            $data['type'] = 'error';
            $data['message'] = $this->queryMessage;
        } catch (Exception $e) {
            $data['type'] = 'error';
            $data['message'] = $e->getMessage();
        }
        return view('product.view', $data);
    }

    public function delete(Request $request)
    {
        try {
            $type = 'success';
            $message = 'Record deleted successfully';
            $post = $request->all();
            $class = new Product();
            $directory = public_path('storage/product');
            DB::beginTransaction();
            $result = Common::deleteSingleData($post, $class, $directory);
            if (!$result) {
                throw new Exception("Couldn't delete record", 1);
            }
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = $this->queryMessage;
        } catch (Exception $e) {
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        }
        return response()->json(['type' => $type, 'message' => $message]);
    }

    //function to restore
    public function restore(Request $request)
    {
        try {
            $post = $request->all();
            $type = 'success';
            $message = "Product restored successfully";
            DB::beginTransaction();
            $result = Product::restoreData($post);
            if (!$result) {
                throw new Exception("Could not restore Product. Please try again.", 1);
            }
            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $type = 'error';
            $message = $this->queryMessage;
        } catch (Exception $e) {
            DB::rollBack();
            $type = 'error';
            $message = $e->getMessage();
        }
        return response()->json(['type' => $type, 'message' => $message]);
    }

    public function toggleActive(Product $product)
    {
        try {
            $product->update([
                'is_active' => ! (bool) $product->is_active,
                'updated_at' => now(),
            ]);

            $message = $product->is_active ? 'Product activated successfully.' : 'Product deactivated successfully.';
            return back()->with('success', $message);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }


}
