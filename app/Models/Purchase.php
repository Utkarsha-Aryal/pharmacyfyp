<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $guarded = [];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function reference()
    {
        return $this->belongsTo(PurchaseReference::class, 'reference_id');
    }

    // Purchase bill can also remember which payment mode was used for direct payment.
    public function paymentMode()
    {
        return $this->belongsTo(DropdownOption::class, 'payment_mode_id');
    }

    public function batches()
    {
        return $this->hasMany(ProductBatch::class, 'reference_id', 'reference_id');
    }

    // Direct purchase bill rows now live in their own table for returns, PDF and allocation logic.
    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    // Purchase return headers point back to the original bill.
    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_id');
    }

    public static function list($post)
    {
        try {
            $get = $post;

            foreach ($get['columns'] as $key => $value) {
                $get['columns'][$key]['search']['value'] = trim(strtolower(htmlspecialchars($value['search']['value'], ENT_QUOTES)));
            }

            $query = Purchase::with(['supplier', 'reference'])
                ->withCount('batches')
                ->where('status', 'Y');

            if (!empty($post['supplier_id'])) {
                $query->where('supplier_id', $post['supplier_id']);
            }

            if (!empty($post['order_status'])) {
                $query->where('order_status', $post['order_status']);
            }

            if (!empty($get['columns'][1]['search']['value'])) {
                $search = $get['columns'][1]['search']['value'];
                $query->whereHas('reference', function ($referenceQuery) use ($search) {
                    $referenceQuery->whereRaw("lower(reference_no) like ?", ['%' . $search . '%']);
                });
            }

            if (!empty($get['columns'][3]['search']['value'])) {
                $search = $get['columns'][3]['search']['value'];
                $query->whereHas('supplier', function ($supplierQuery) use ($search) {
                    $supplierQuery->whereRaw("lower(supplier_name) like ?", ['%' . $search . '%']);
                });
            }

            $totalrecs = (clone $query)->count();

            $limit = 15;
            $offset = 0;
            if (!empty($get['length'])) {
                $limit = $get['length'];
                $offset = $get['start'];
            }

            $result = $query
                ->orderByDesc('purchase_date')
                ->orderByDesc('id')
                ->offset($offset)
                ->limit($limit)
                ->get();

            if ($result->isNotEmpty()) {
                $result['totalrecs'] = $totalrecs;
                $result['totalfilteredrecs'] = $totalrecs;
            } else {
                $result = collect();
                $result['totalrecs'] = 0;
                $result['totalfilteredrecs'] = 0;
            }

            return $result;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function getDueAmountAttribute()
    {
        return (float) $this->grand_total - (float) $this->paid_amount;
    }

    // Keep one common payable value because payment-out and dashboard both need it.
    public function getOutstandingAmountAttribute(): float
    {
        return round(max(0, (float) $this->grand_total - (float) $this->paid_amount), 2);
    }

    public static function resolvePaymentStatus(float $grandTotal, float $paidAmount): string
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $grandTotal) {
            return 'paid';
        }

        return 'partial';
    }

    public function getOrderStatusLabelAttribute(): string
    {
        return match ($this->order_status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            default => 'Received',
        };
    }

    public function getPurchaseDateShowAttribute()
    {
        if (!$this->purchase_date) {
            return '-';
        }

        return Carbon::parse($this->purchase_date)->format('M j, Y');
    }
}
