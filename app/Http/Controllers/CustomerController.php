<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\PartyType;
use App\Models\SalesInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    // Show the customer master page.
    public function index()
    {
        $partySummary = [
            'total' => Customer::query()->count(),
            'active' => Customer::query()->where('is_active', true)->count(),
            'customers' => Customer::query()->where('party_type', 'customer')->count(),
            'institutions' => Customer::query()->where('party_type', 'institution')->count(),
        ];

        return view('customer.index', [
            'partySummary' => $partySummary,
            'partyTypes' => PartyType::query()->orderBy('name')->get(),
            'filters' => [
                'party_type' => request('party_type', ''),
                'status' => request('status', ''),
            ],
        ]);
    }

    // Return the server-side rows for the customer table.
    public function list(Request $request)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = max((int) $request->input('length', 10), 1);

        $query = Customer::query()->orderByDesc('id');
        $recordsTotal = (clone $query)->count();

        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('contact_person', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('party_type', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('party_type')) {
            $query->where('party_type', $request->party_type);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $recordsFiltered = (clone $query)->count();
        $customers = $query->skip($start)->take($length)->get();

        $data = [];

        foreach ($customers as $index => $customer) {
            $statusLabel = $customer->is_active ? 'Active' : 'Inactive';
            $statusClass = $customer->is_active ? 'text-success' : 'text-danger';
            $toggleClass = $customer->is_active ? 'btn-outline-success' : 'btn-outline-danger';
            $toggleIcon = $customer->is_active ? 'fa-toggle-on' : 'fa-toggle-off';
            $agingDays = $this->customerAgingDays($customer);
            $agingBadge = $agingDays > 60 ? 'report-badge-danger' : ($agingDays > 30 ? 'report-badge-warning' : 'report-badge-info');
            $partyBadgeClass = match ($customer->party_type) {
                'customer' => 'report-badge-success',
                'institution' => 'report-badge-info',
                default => 'report-badge-primary',
            };

            $statusHtml = '<div class="d-inline-flex align-items-center gap-2">';
            $statusHtml .= '<form action="' . route('admin.customers.toggle-active', $customer) . '" method="POST" class="js-confirm-submit" data-confirm-title="Change customer status?" data-confirm-text="This will switch the account access." data-confirm-button="Yes, update status">' . csrf_field() . '<button type="submit" class="btn btn-sm ' . $toggleClass . ' table-action-btn status-toggle-btn" title="' . e($customer->is_active ? 'Deactivate Customer' : 'Activate Customer') . '" aria-label="' . e($customer->is_active ? 'Deactivate Customer' : 'Activate Customer') . '"><i class="fa-solid ' . $toggleIcon . '"></i></button></form>';
            $statusHtml .= '<span class="status-state-text ' . $statusClass . '">' . e($statusLabel) . '</span>';
            $statusHtml .= '</div>';

            $action = '<div class="table-action-group">';
            $action .= '<a href="' . route('admin.customers.ledger', $customer) . '" class="btn btn-sm btn-outline-success table-action-btn" title="View Ledger" aria-label="View Ledger"><i class="fa-solid fa-book"></i></a>';
            $action .= '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn editCustomer" title="Edit Customer" aria-label="Edit Customer" data-id="' . $customer->id . '" data-name="' . e($customer->name) . '" data-party_type="' . e($customer->party_type) . '" data-contact_person="' . e($customer->contact_person) . '" data-phone="' . e($customer->phone) . '" data-email="' . e($customer->email) . '" data-address="' . e($customer->address) . '" data-credit_limit="' . e($customer->credit_limit) . '" data-opening_balance="' . e($customer->opening_balance) . '" data-is_active="' . (int) $customer->is_active . '"><i class="fa-solid fa-pen-to-square"></i></button>';
            $action .= '<form action="' . route('admin.customers.delete', $customer) . '" method="POST" class="js-confirm-submit" data-confirm-title="Delete this party?" data-confirm-text="This customer will be removed if it has no invoice history." data-confirm-button="Yes, delete party">' . csrf_field() . '<button type="submit" class="btn btn-sm btn-outline-danger table-action-btn" title="Delete Party" aria-label="Delete Party"><i class="fa-solid fa-trash"></i></button></form>';
            $action .= '</div>';

            $data[] = [
                'sno' => $start + $index + 1,
                'name' => e($customer->name),
                'party_type' => '<span class="report-badge ' . $partyBadgeClass . '">' . e($customer->party_type_label ?: Str::headline((string) $customer->party_type)) . '</span>',
                'contact_person' => e($customer->contact_person ?: '-'),
                'phone' => e($customer->phone ?: '-'),
                'credit_limit' => money_value($customer->credit_limit),
                'balance' => '<span class="report-badge ' . ($customer->balance > 0 ? 'report-badge-warning' : 'report-badge-success') . '">' . money_value($customer->balance) . '</span>',
                'aging' => '<span class="report-badge ' . $agingBadge . '">' . $agingDays . ' days</span>',
                'status' => $statusHtml,
                'action' => $action,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    // Save a customer from the modal form.
    public function save(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => ['nullable', 'integer', 'exists:customers,id'],
                'name' => ['required', 'string', 'max:255'],
                'party_type' => ['required', 'string', Rule::exists('party_types', 'code')],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
                'address' => ['nullable', 'string'],
                'credit_limit' => ['nullable', 'numeric', 'min:0'],
                'opening_balance' => ['nullable', 'numeric'],
            ]);

            $customer = !empty($validated['id']) ? Customer::findOrFail($validated['id']) : new Customer();
            $customer->fill([
                'name' => $validated['name'],
                'party_type' => $validated['party_type'],
                'contact_person' => $validated['contact_person'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'credit_limit' => $validated['credit_limit'] ?? 0,
            ]);

            if (!$customer->exists) {
                // New customer starts with the opening balance we typed in the form.
                $customer->opening_balance = $validated['opening_balance'] ?? 0;
                $customer->current_balance = $validated['opening_balance'] ?? 0;
                $customer->is_active = true;
            }

            $customer->save();

            return response()->json([
                'type' => 'success',
                'message' => empty($validated['id']) ? 'Customer added successfully.' : 'Customer updated successfully.',
                'data' => [
                    'id' => $customer->id,
                    'text' => $customer->name,
                ],
            ]);
        } catch (QueryException|Exception $e) {
            return response()->json([
                'type' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // Search active customers for select2.
    public function options(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $customers = Customer::query()
            ->where('is_active', true)
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($builder) use ($keyword) {
                    $builder->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('contact_person', 'like', '%' . $keyword . '%')
                        ->orWhere('phone', 'like', '%' . $keyword . '%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($customer) => [
                'id' => $customer->id,
                'text' => $customer->name . ($customer->contact_person ? ' - ' . $customer->contact_person : ''),
            ])
            ->values();

        return response()->json([
            'results' => $customers,
        ]);
    }

    // Show the customer ledger and history.
    public function ledger(Customer $customer)
    {
        $invoiceQuery = $customer->salesInvoices();

        return view('customer.ledger', [
            'customer' => $customer,
            'outstanding' => $customer->balance,
            'invoiceTotal' => (float) (clone $invoiceQuery)->sum('total_amount'),
            'paidTotal' => (float) (clone $invoiceQuery)->sum('paid_amount'),
            'salesCount' => (clone $invoiceQuery)->count(),
            'agingDays' => $this->customerAgingDays($customer),
        ]);
    }

    public function ledgerInvoicesList(Request $request, Customer $customer)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = $customer->salesInvoices()
            ->with(['saleTypeOption'])
            ->latest('invoice_date')
            ->latest('id');

        $recordsTotal = (clone $query)->count();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('reference', 'like', '%' . $keyword . '%')
                    ->orWhere('status', 'like', '%' . $keyword . '%')
                    ->orWhere('payment_status', 'like', '%' . $keyword . '%')
                    ->orWhereHas('saleTypeOption', function (Builder $typeQuery) use ($keyword) {
                        $typeQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $invoices = $query->get();
        $data = [];

        foreach ($invoices as $index => $invoice) {
            $data[] = [
                'sno' => $start + $index + 1,
                'reference' => e($invoice->reference),
                'date' => e($invoice->invoice_date_show),
                'sale_type' => e($invoice->sale_type_label),
                'status' => '<span class="badge bg-light text-dark border">' . e($invoice->status_label) . '</span>',
                'payment' => '<span class="badge bg-light text-dark border">' . e($invoice->payment_label) . '</span>',
                'total' => money_value($invoice->total_amount),
                'paid' => money_value($invoice->paid_amount),
                'due' => money_value($invoice->due_amount),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function ledgerReturnsList(Request $request, Customer $customer)
    {
        $keyword = trim((string) $request->input('search.value', ''));
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);

        $query = $customer->salesReturns()
            ->with(['product', 'paymentMode'])
            ->latest('return_date')
            ->latest('id');

        $recordsTotal = (clone $query)->count();

        if ($keyword !== '') {
            $query->where(function (Builder $builder) use ($keyword) {
                $builder->where('reason', 'like', '%' . $keyword . '%')
                    ->orWhere('notes', 'like', '%' . $keyword . '%')
                    ->orWhere('refund_status', 'like', '%' . $keyword . '%')
                    ->orWhereHas('product', function (Builder $productQuery) use ($keyword) {
                        $productQuery->where('product_name', 'like', '%' . $keyword . '%')
                            ->orWhere('generic_name', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('paymentMode', function (Builder $modeQuery) use ($keyword) {
                        $modeQuery->where('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $recordsFiltered = (clone $query)->count();

        if ($length > -1) {
            $query->skip($start)->take($length);
        }

        $returns = $query->get();
        $data = [];

        foreach ($returns as $index => $returnItem) {
            $settlementLabel = $returnItem->cash_refund_amount > 0
                ? ($returnItem->payment_mode_label ?: 'Paid out')
                : ($returnItem->pending_credit_amount > 0 ? 'Pending customer credit' : 'Adjusted against balance');

            $data[] = [
                'sno' => $start + $index + 1,
                'date' => e($returnItem->return_date_show),
                'product' => e($returnItem->product?->display_name ?? '-'),
                'quantity' => '<span class="badge bg-secondary">' . (float) $returnItem->quantity . '</span>',
                'discount' => e(number_format((float) $returnItem->effective_discount_percent, 2)) . '% / ' . e(money_value($returnItem->effective_discount_amount)),
                'net_rate' => money_value($returnItem->effective_net_unit_price),
                'refund' => '<div>' . e(money_value($returnItem->refund_amount)) . '</div><small class="text-muted d-block">Adj ' . e(money_value($returnItem->receivable_adjusted_amount)) . ' | Cash ' . e(money_value($returnItem->cash_refund_amount)) . '</small>',
                'settlement' => '<div>' . e($returnItem->refund_status_label) . '</div><small class="text-muted d-block">' . e($settlementLabel) . '</small>',
                'reason' => e($returnItem->reason ?: '-'),
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 0),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    // Stream the party ledger as PDF so ledger print follows the same pdf-only flow.
    public function ledgerPdf(Customer $customer)
    {
        $customer->load(['salesInvoices.items.product', 'salesReturns.product', 'salesReturns.paymentMode']);

        $invoices = $customer->salesInvoices()->with('items.product')->latest('invoice_date')->get();
        $returns = $customer->salesReturns()->with(['product', 'paymentMode'])->latest('return_date')->take(20)->get();

        return Pdf::loadView('pdf.customer-ledger', [
            'customer' => $customer,
            'invoices' => $invoices,
            'returns' => $returns,
            'outstanding' => $customer->balance,
            'invoiceTotal' => $invoices->sum('total_amount'),
            'paidTotal' => $invoices->sum('paid_amount'),
            'salesCount' => $invoices->count(),
            'agingDays' => $this->customerAgingDays($customer),
            'company' => pdf_company_context(),
            'logoSrc' => pdf_logo_src(),
        ])->setPaper('a4', 'portrait')
            ->stream('party-ledger-' . $customer->id . '.pdf');
    }

    // Toggle customer active state from the table.
    public function toggleActive(Customer $customer)
    {
        $customer->update([
            'is_active' => ! (bool) $customer->is_active,
        ]);

        return back()->with('success', 'Customer status updated successfully.');
    }

    // Delete a customer only when there is no invoice history.
    public function delete(Customer $customer)
    {
        if ($customer->salesInvoices()->exists()) {
            return back()->with('error', 'Customer cannot be deleted because invoice history already exists.');
        }

        $customer->delete();

        return back()->with('success', 'Customer deleted successfully.');
    }

    // Calculate how old the oldest unpaid invoice is.
    private function customerAgingDays(Customer $customer): int
    {
        $oldestDueInvoice = SalesInvoice::query()
            ->where('customer_id', $customer->id)
            ->whereRaw('(total_amount - paid_amount) > 0')
            ->orderBy('invoice_date')
            ->value('invoice_date');

        if (!$oldestDueInvoice) {
            return 0;
        }

        return (int) max(0, round(Carbon::parse($oldestDueInvoice)->diffInDays(now(), false)));
    }
}
