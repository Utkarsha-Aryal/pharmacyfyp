<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Company;
use Exception;
use App\Models\Common;
use Illuminate\Support\Str;
use Carbon\Carbon;


class Product extends Model
{
    protected $guarded = [];

    public const LEGACY_STATUS_CODES = [
        'In Stock' => 'instock',
        'Out of Stock' => 'stockout',
        'Discontinued' => 'discontinued',
    ];

    public const LEGACY_STATUS_LABELS = [
        'instock' => 'In Stock',
        'stockout' => 'Out of Stock',
        'discontinued' => 'Discontinued',
    ];

    // One product belongs to one company.
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Product status is now driven from the shared dropdown options table.
    public function productStatusOption()
    {
        return $this->belongsTo(DropdownOption::class, 'product_status_id');
    }

    // Formulation is also managed from the shared dropdown options table now.
    public function formulationOption()
    {
        return $this->belongsTo(DropdownOption::class, 'formulation_id');
    }

    // Old purchase bill batches still point here, so this relation stays for backward compatibility.
    public function productBatches()
    {
        return $this->hasMany(ProductBatch::class, 'product_id');
    }

    // Newer inventory flow uses the clean batches table.
    public function batches()
    {
        return $this->hasMany(Batch::class, 'product_id');
    }

    // Direct purchase bill rows live here for return and print use.
    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class, 'product_id');
    }

    // Sales rows live here for sales analysis and PDF use.
    public function salesInvoiceItems()
    {
        return $this->hasMany(SalesInvoiceItem::class, 'product_id');
    }

    // Keep one simple display name so views do not guess which name column to use.
    public function getDisplayNameAttribute(): string
    {
        return (string) ($this->name ?: $this->product_name ?: 'Untitled product');
    }

    // Reorder level can come from the newer field or the old alert field.
    public function getEffectiveReorderLevelAttribute(): int
    {
        if (!is_null($this->reorder_level)) {
            return (int) $this->reorder_level;
        }

        return (int) ($this->alert_quantity ?? 10);
    }

    // Unit is also mixed between legacy and new data, so keep a fallback.
    public function getEffectiveUnitAttribute(): string
    {
        return (string) ($this->unit ?? 'Unit');
    }

    // Company default CC rate acts as fallback when product-level CC rate was never set.
    public function getEffectiveCcRateAttribute(): float
    {
        $productRate = round((float) ($this->cc_rate ?? 0), 2);
        if ($productRate > 0) {
            return $productRate;
        }

        if ($this->relationLoaded('company') && $this->company) {
            return round((float) ($this->company->default_cc_rate ?? 0), 2);
        }

        return round((float) (Company::query()->whereKey($this->company_id)->value('default_cc_rate') ?? 0), 2);
    }

    // Prefer the shared dropdown label, but keep the old string value as a fallback during migration.
    public function getProductStatusLabelAttribute(): string
    {
        return (string) ($this->productStatusOption?->name ?: static::legacyStatusLabel($this->product_status) ?: 'Out of Stock');
    }

    // Prefer the shared dropdown label, but keep the old string value as a fallback during migration.
    public function getFormulationLabelAttribute(): string
    {
        return (string) ($this->formulationOption?->name ?: ucfirst((string) ($this->formulation ?: '')));
    }

    // Keep product save logic in one place because both add and edit use the same modal form.
    public static function saveData($post)
    {
        try {
            $productStatus = !empty($post['product_status_id'])
                ? DropdownOption::query()->forAlias('product_status')->find($post['product_status_id'])
                : null;
            $formulation = !empty($post['formulation_id'])
                ? DropdownOption::query()->forAlias('formulation')->find($post['formulation_id'])
                : null;
            $legacyProductStatus = static::legacyStatusCode($productStatus?->name ?? ($post['product_status'] ?? null));
            $companyCcRate = (float) (Company::query()->find($post['company_id'] ?? null)?->default_cc_rate ?? 0);
            $inputCcRate = (float) ($post['cc_rate'] ?? 0);
            $resolvedCcRate = (!empty($post['id']) || $inputCcRate > 0 || $companyCcRate <= 0)
                ? $inputCcRate
                : $companyCcRate;

            $dataArray = [
                'product_code' => $post['product_code'] ?? null,
                'name' => $post['product_name'] ?? null,
                'product_name' => $post['product_name'] ?? null,
                'generic_name' => $post['generic_name'] ?? null,
                'composition' => $post['composition'] ?? null,
                'group_name' => $post['group_name'] ?? null,
                'description' => $post['description'] ?? null,
                'manufacturer' => $post['manufacturer'] ?? null,
                'previous_price' => $post['previous_price'] ?? null,
                'mrp' => $post['mrp'] ?? null,
                'cc_rate' => round($resolvedCcRate, 2),
                'company_id' => $post['company_id'] ?? null,
                'formulation' => $formulation?->name ?? ($post['formulation'] ?? null),
                'formulation_id' => $formulation?->id,
                'unit' => $post['unit_name'] ?? $post['unit'] ?? null,
                'reorder_level' => $post['reorder_level'] ?? $post['alert_quantity'] ?? 10,
                'alert_quantity' => $post['reorder_level'] ?? $post['alert_quantity'] ?? 10,
                'is_active' => array_key_exists('is_active', $post) ? (bool) $post['is_active'] : true,
                'sale_unit_id' => $post['unit_sale_id'] ?? null,
                'purchase_unit_id' => $post['unit_purchase_id'] ?? null,
                'product_status' => $legacyProductStatus,
                'product_status_id' => $productStatus?->id,
                'slug' => Str::slug((string) ($post['product_name'] ?? 'product')) . '-' . Str::random(12) . '-' . time(),
                'keywords' => $post['keywords'] ?? null,
                'alert_quantity' => $post['alert_quantity'] ?? ($post['reorder_level'] ?? 10),
                'discount' => $post['discount'] ?? 0,
                'purchase_price' => $post['purchase_price'] ?? null,
            ];

            if (!empty($post['image'])) {
                $fileName = Common::uploadFile('product', $post['image']);
                if (!$fileName) {
                    return false;
                }
                $dataArray['image'] = $fileName;
            }

            if (!empty($post['id'])) {
                $dataArray['updated_at'] = Carbon::now();
                if (!Product::where('id', $post['id'])->update($dataArray)) {
                    throw new Exception("Couldn't update Product", 1);
                }
                $productId = $post['id'];
            } else {
                $dataArray['created_at'] = Carbon::now();
                $productId = Product::insertGetId($dataArray);
                if (!$productId) {
                    throw new Exception("Couldn't Save Product", 1);
                }
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }


     public static function list($post)
    {
        try {
            $get = $post;
            $sorting = !empty($get['order'][0]['dir']) ? $get['order'][0]['dir'] : 'asc';
            $orderby = " product_name " . $sorting . "";
            if (!empty($get['order'][0]['column']) && $get['order'][0]['column'] == 6) {
                $orderby = " product_name " . $sorting . "";
            }
            foreach ($get['columns'] as $key => $value) {
                $get['columns'][$key]['search']['value'] = trim(strtolower(htmlspecialchars($value['search']['value'], ENT_QUOTES)));
            }

            $cond = " status = 'Y'";

            if (!empty($post['type']) && $post['type'] === "trashed") {
                $cond = " status = 'N'";
            }

            if ($get['columns'][1]['search']['value'])
                $cond .= " and lower(product_name) like '%" . $get['columns'][1]['search']['value'] . "%'";

            if (!empty($post['company_id'])) {
                $cond .= " and company_id = '" . addslashes($post['company_id']) . "'";
            }

            $limit = 15;
            $offset = 0;
            if (!empty($get["length"]) && $get["length"]) {
                $limit = $get['length'];
                $offset = $get["start"];
            }

            $query = Product::with([
                'company',
                'productBatches' => function ($batchQuery) {
                    $batchQuery->where('status', 'Y');
                },
                'batches' => function ($batchQuery) {
                    $batchQuery->where('is_active', true);
                },
            ])
                ->selectRaw("(SELECT COUNT(*) FROM products WHERE {$cond}) 
               AS totalrecs, id, name, product_name, description, mrp, cc_rate, discount, slug, image, company_id, keywords, generic_name, display_price, manufacturer, formulation, formulation_id, product_status, product_status_id, unit, reorder_level, is_active")
                ->whereRaw($cond);
            if ($limit > -1) {
                $result = $query->orderByRaw($orderby)->offset($offset)->limit($limit)->get();
            } else {
                $result = $query->orderByRaw($orderby)->get();
            }
            if ($result) {
                $ndata = $result;
                $ndata['totalrecs'] = @$result[0]->totalrecs ? $result[0]->totalrecs : 0;
                $ndata['totalfilteredrecs'] = @$result[0]->totalrecs ? $result[0]->totalrecs : 0;
            } else {
                $ndata = array();
            }
            return $ndata;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function restoreData($post)
    {
        try {
            $updateArray = [
                'status' => 'Y',
                'updated_at' => Carbon::now(),
            ];
            if (!Product::where(['id' => $post['id']])->update($updateArray)) {
                throw new Exception("Couldn't Restore Data. Please try again", 1);
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Shared dropdown labels still need a safe value inside the old product_status enum column.
    public static function legacyStatusCode(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'stockout';
        }

        $normalized = mb_strtolower($value);

        return match ($normalized) {
            'in stock', 'instock' => 'instock',
            'out of stock', 'stockout' => 'stockout',
            'discontinued' => 'discontinued',
            default => self::LEGACY_STATUS_CODES[$value] ?? 'stockout',
        };
    }

    // When only the old enum exists on a row, this keeps the UI label friendly.
    public static function legacyStatusLabel(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return self::LEGACY_STATUS_LABELS[mb_strtolower((string) $value)] ?? null;
    }
}
