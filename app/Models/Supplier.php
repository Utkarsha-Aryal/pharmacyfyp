<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class Supplier extends Model
{
    protected $guarded = [];

    protected $casts = [
        'opening_balance' => 'float',
        'current_balance' => 'float',
    ];

    // Keep supplier due readable like customer balance.
    public function getBalanceAttribute(): float
    {
        return round((float) $this->current_balance, 2);
    }

    // Direct purchase bills belong to one supplier.
    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }

    // Payment out can link vouchers to suppliers.
    public function payments()
    {
        return $this->hasMany(Payment::class, 'party_id')->where('party_type', 'supplier');
    }

    // Accounting ledger can read supplier transactions from one relation.
    public function accountTransactions()
    {
        return $this->hasMany(AccountTransaction::class, 'party_id')->where('party_type', 'supplier');
    }

    public static function saveData($post)
    {
        try {
            $openingBalance = round((float) ($post['opening_balance'] ?? 0), 2);
            $dataArray = [
                'supplier_name' => $post['supplier_name'],
                'contact_person' => $post['contact_person'] ?? null,
                'phone_number' => $post['phone_number'] ?? null,
                'email' => $post['email'] ?? null,
                'pan_number' => $post['pan_number'] ?? null,
                'opening_balance' => $openingBalance,
                'address' => $post['address'] ?? null,
                'type' => $post['type'] ?? 'credit',
            ];
            if (!empty($post['id'])) {
                $supplier = Supplier::query()->lockForUpdate()->findOrFail($post['id']);
                $oldOpeningBalance = round((float) $supplier->opening_balance, 2);
                $currentBalance = round((float) $supplier->current_balance, 2);
                $dataArray['current_balance'] = round($currentBalance + ($openingBalance - $oldOpeningBalance), 2);
                $dataArray['updated_at'] = now();
                if (!$supplier->update($dataArray)) {
                    throw new Exception("Couldn't update Records", 1);
                }
            } else {
                $dataArray['current_balance'] = $openingBalance;
                $dataArray['created_at'] = now();
                if (!Supplier::insert($dataArray)) {
                    throw new Exception("Couldn't Save Records", 1);
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
            $columns = $get['columns'] ?? [];
            $sorting = !empty($get['order'][0]['dir']) && strtolower($get['order'][0]['dir']) === 'desc' ? 'desc' : 'asc';
            $orderColumnIndex = isset($get['order'][0]['column']) ? (int) $get['order'][0]['column'] : 0;
            $sortableColumns = [
                0 => 'id',
                1 => 'supplier_name',
                2 => 'contact_person',
                3 => 'current_balance',
                4 => 'phone_number',
                5 => 'created_at',
            ];
            $orderBy = $sortableColumns[$orderColumnIndex] ?? 'id';
            $limit = isset($get['length']) ? (int) $get['length'] : 15;
            $offset = isset($get['start']) ? (int) $get['start'] : 0;
            $status = !empty($post['type']) && $post['type'] === 'trashed' ? 'N' : 'Y';

            $baseQuery = Supplier::query()->where('status', $status);
            $filteredQuery = (clone $baseQuery);

            // We only keep the useful column filters here, so the table search stays simple.
            self::applyColumnSearch($filteredQuery, $columns);

            $result = (clone $filteredQuery)
                ->orderBy($orderBy, $sorting)
                ->when($limit > -1, function (Builder $query) use ($offset, $limit) {
                    $query->offset($offset)->limit($limit);
                })
                ->get([
                    'id',
                    'supplier_name',
                    'contact_person',
                    'phone_number',
                    'email',
                    'pan_number',
                    'opening_balance',
                    'current_balance',
                    'created_at',
                    'address',
                    'type',
                ]);

            $result['totalrecs'] = (clone $baseQuery)->count();
            $result['totalfilteredrecs'] = (clone $filteredQuery)->count();

            return $result;
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Keep column search in one place so the supplier table stays easy to adjust later.
    private static function applyColumnSearch(Builder $query, array $columns): void
    {
        $nameSearch = self::searchValue($columns, 1);
        $contactSearch = self::searchValue($columns, 2);
        $phoneSearch = self::searchValue($columns, 4);

        if ($nameSearch !== '') {
            $query->where('supplier_name', 'like', '%' . $nameSearch . '%');
        }

        if ($contactSearch !== '') {
            $query->whereRaw('LOWER(COALESCE(contact_person, "")) like ?', ['%' . $contactSearch . '%']);
        }

        if ($phoneSearch !== '') {
            $query->whereRaw('LOWER(COALESCE(phone_number, "")) like ?', ['%' . $phoneSearch . '%']);
        }
    }

    // This helper keeps DataTable search input cleanup small and reusable.
    private static function searchValue(array $columns, int $index): string
    {
        return trim(strtolower((string) data_get($columns, $index . '.search.value', '')));
    }

    public static function adjustCurrentBalance(?int $supplierId, float $delta): void
    {
        if (!$supplierId || round($delta, 2) == 0.0) {
            return;
        }

        $supplier = Supplier::query()->lockForUpdate()->find($supplierId);
        if (!$supplier) {
            return;
        }

        $supplier->current_balance = round((float) $supplier->current_balance + $delta, 2);
        $supplier->save();
    }

    public static function restoreData($post)
    {
        try {
            $updateArray = [
                'status' => 'Y',
                'updated_at' => Carbon::now(),
            ];

            if (!Supplier::where(['id' => $post['id']])->update($updateArray)) {
                throw new Exception("Couldn't Restore Data. Please try again", 1);
            }

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
