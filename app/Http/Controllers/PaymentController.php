<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\AccountTransaction;
use App\Models\Payment;
use App\Models\PaymentBillAllocation;
use App\Models\PaymentMode;
use App\Models\Purchase;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    // Show one combined payment list for both incoming and outgoing vouchers.
    public function index(Request $request)
    {
        $query = Payment::query()
            ->with(['paymentMode', 'customer', 'supplier', 'allocations'])
            ->latest('payment_date')
            ->latest('id');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('party_type')) {
            $query->where('party_type', $request->input('party_type'));
        }

        return view('payment.index', [
            'payments' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['type', 'party_type']),
            'openModal' => $request->input('open'),
            'editPaymentId' => $request->input('edit'),
            'customers' => Customer::query()->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('status', 'Y')->orderBy('supplier_name')->get(),
            'paymentModes' => PaymentMode::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    // Return one payment row in JSON so the edit modal can refill the form cleanly.
    public function edit(Payment $payment)
    {
        $payment = $payment->load(['paymentMode', 'customer', 'supplier', 'allocations']);

        return response()->json([
            'type' => 'success',
            'data' => [
                'id' => $payment->id,
                'type' => $payment->type,
                'party_type' => $payment->party_type,
                'party_id' => $payment->party_id,
                'party_name' => $payment->party_name,
                'payment_date' => $payment->payment_date,
                'amount' => round((float) $payment->amount, 2),
                'payment_mode_id' => $payment->payment_mode_id,
                'reference_number' => $payment->reference_number,
                'notes' => $payment->notes,
                'update_url' => route('admin.payments.update', $payment),
                'rows' => $this->allocationRows($payment, true),
            ],
        ]);
    }

    // Old payment-in route now lands on the single payments page and opens the receipt modal.
    public function createIn()
    {
        return redirect()->route('admin.payments.index', ['open' => 'in']);
    }

    // Save one payment in row and optionally link it to customer bills.
    public function storeIn(Request $request)
    {
        return $this->storePayment($request, 'in', 'customer');
    }

    // Old payment-out route now lands on the single payments page and opens the payout modal.
    public function createOut()
    {
        return redirect()->route('admin.payments.index', ['open' => 'out']);
    }

    // Save one payment out row and optionally link it to supplier bills.
    public function storeOut(Request $request)
    {
        return $this->storePayment($request, 'out', 'supplier');
    }

    // Update one payment and rebuild bill allocations and ledger rows from the edited form.
    public function update(Request $request, Payment $payment)
    {
        return $this->storePayment($request, $payment->type, $payment->party_type, $payment);
    }

    // Return outstanding bills for the selected party so the form can allocate money bill by bill.
    public function outstandingBills(Request $request)
    {
        $validated = $request->validate([
            'party_id' => ['required', 'integer'],
            'party_type' => ['required', Rule::in(['customer', 'supplier'])],
        ]);

        if ($validated['party_type'] === 'customer') {
            $bills = SalesInvoice::query()
                ->where('customer_id', $validated['party_id'])
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->latest('invoice_date')
                ->get()
                ->map(function (SalesInvoice $invoice) {
                    return [
                        'bill_id' => $invoice->id,
                        'bill_type' => 'sales_invoice',
                        'bill_number' => $invoice->reference,
                        'bill_date' => $invoice->invoice_date_show,
                        'net_amount' => round((float) $invoice->total_amount, 2),
                        'total_paid' => round((float) $invoice->paid_amount, 2),
                        'outstanding' => round($invoice->due_amount, 2),
                    ];
                })
                ->values();

            return response()->json($bills);
        }

        $bills = Purchase::query()
            ->with('reference')
            ->where('supplier_id', $validated['party_id'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->latest('purchase_date')
            ->get()
            ->map(function (Purchase $purchase) {
                return [
                    'bill_id' => $purchase->id,
                    'bill_type' => 'purchase',
                    'bill_number' => $purchase->reference?->reference_no ?: ('PUR-' . $purchase->id),
                    'bill_date' => $purchase->purchase_date_show,
                    'net_amount' => round((float) $purchase->grand_total, 2),
                    'total_paid' => round((float) $purchase->paid_amount, 2),
                    'outstanding' => round($purchase->due_amount, 2),
                ];
            })
            ->values();

        return response()->json($bills);
    }

    // Show one payment voucher with linked bills and printable details.
    public function show(Payment $payment)
    {
        $payment = $payment->load(['paymentMode', 'customer', 'supplier', 'allocations']);

        return view('payment.show', [
            'payment' => $payment,
            'allocationRows' => $this->allocationRows($payment),
        ]);
    }

    // Stream the payment receipt or voucher as PDF in a new browser tab.
    public function print(Payment $payment)
    {
        $payment = $payment->load(['paymentMode', 'customer', 'supplier', 'allocations']);

        return Pdf::loadView('pdf.payment', [
            'payment' => $payment,
            'allocationRows' => $this->allocationRows($payment),
            'company' => pdf_company_context(),
            'logoSrc' => pdf_logo_src(),
        ])->setPaper('a4', 'portrait')
            ->stream('payment-' . $payment->id . '.pdf');
    }

    // Keep payment save logic in one place because payment in and payment out only differ by direction and party type.
    private function storePayment(Request $request, string $type, string $partyType, ?Payment $existingPayment = null)
    {
        $validated = $request->validate([
            'party_id' => ['required', 'integer'],
            'party_type' => ['required', Rule::in(['customer', 'supplier'])],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode_id' => ['required', 'exists:payment_modes,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.bill_id' => ['nullable', 'integer'],
            'allocations.*.bill_type' => ['nullable', Rule::in(['sales_invoice', 'purchase'])],
            'allocations.*.allocated_amount' => ['nullable', 'numeric', 'min:0.01'],
        ], [
            'allocations.*.bill_id.integer' => 'Please choose a valid bill for each linked row.',
            'allocations.*.bill_type.in' => 'Please choose a valid bill type for each linked row.',
            'allocations.*.allocated_amount.min' => 'Allocation amount must be at least 0.01.',
        ]);

        if ($validated['party_type'] !== $partyType) {
            throw ValidationException::withMessages([
                'party_type' => 'Invalid party type selected for this payment screen.',
            ]);
        }

        $allocations = collect($validated['allocations'] ?? [])
            ->filter(function (array $allocation) {
                return (float) ($allocation['allocated_amount'] ?? 0) > 0;
            })
            ->values();

        foreach ($allocations as $index => $allocation) {
            if (empty($allocation['bill_id']) || empty($allocation['bill_type'])) {
                throw ValidationException::withMessages([
                    'allocations' => 'Every allocated row must include a bill reference.',
                ]);
            }
        }

        $payment = DB::transaction(function () use ($validated, $type, $partyType, $request, $allocations, $existingPayment) {
            $payment = $existingPayment
                ? Payment::query()->lockForUpdate()->with('allocations')->findOrFail($existingPayment->id)
                : null;

            if ($payment) {
                $this->reversePaymentEffects($payment);

                PaymentBillAllocation::query()->where('payment_id', $payment->id)->delete();
                AccountTransaction::query()
                    ->where('reference_type', 'Payment')
                    ->where('reference_id', $payment->id)
                    ->delete();

                $payment->update([
                    'type' => $type,
                    'party_id' => $validated['party_id'],
                    'party_type' => $partyType,
                    'payment_date' => $validated['payment_date'],
                    'amount' => round((float) $validated['amount'], 2),
                    'payment_mode_id' => $validated['payment_mode_id'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            } else {
                $payment = Payment::query()->create([
                    'type' => $type,
                    'party_id' => $validated['party_id'],
                    'party_type' => $partyType,
                    'payment_date' => $validated['payment_date'],
                    'amount' => round((float) $validated['amount'], 2),
                    'payment_mode_id' => $validated['payment_mode_id'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            $allocatedTotal = 0;

            foreach ($allocations as $allocation) {
                $allocatedAmount = round((float) $allocation['allocated_amount'], 2);

                if ($allocatedAmount <= 0) {
                    continue;
                }

                $bill = $this->resolveBill((string) $allocation['bill_type'], (int) $allocation['bill_id'], $validated['party_id'], $partyType);
                $outstanding = $this->billOutstanding($bill, (string) $allocation['bill_type']);

                if ($allocatedAmount > $outstanding) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Allocated amount cannot be more than the outstanding bill balance.',
                    ]);
                }

                PaymentBillAllocation::query()->create([
                    'payment_id' => $payment->id,
                    'bill_id' => $bill->id,
                    'bill_type' => $allocation['bill_type'],
                    'allocated_amount' => $allocatedAmount,
                ]);

                $allocatedTotal += $allocatedAmount;

                $bill->paid_amount = round((float) $bill->paid_amount + $allocatedAmount, 2);
                $bill->payment_status = $this->resolveBillPaymentStatus($bill, (string) $allocation['bill_type']);
                $bill->save();

                if ($partyType === 'customer' && $bill instanceof SalesInvoice && $bill->customer_id) {
                    $customer = Customer::query()->lockForUpdate()->findOrFail($bill->customer_id);
                    $customer->current_balance = round(max(0, (float) $customer->current_balance - $allocatedAmount), 2);
                    $customer->save();
                }
            }

            if ($allocatedTotal > (float) $payment->amount) {
                throw ValidationException::withMessages([
                    'allocations' => 'Allocated total cannot be more than payment amount.',
                ]);
            }

            $payment->load('paymentMode');
            $cashAccount = $payment->paymentMode?->type === 'cash' ? 'cash' : 'bank';

            if ($type === 'in') {
                record_account_transaction([
                    'transaction_date' => $payment->payment_date,
                    'reference_type' => 'Payment',
                    'reference_id' => $payment->id,
                    'party_type' => $partyType,
                    'party_id' => $validated['party_id'],
                    'entry_type' => 'debit',
                    'account_type' => $cashAccount,
                    'amount' => $payment->amount,
                    'notes' => 'Payment received against customer account.',
                    'created_by' => $request->user()->id,
                ]);

                record_account_transaction([
                    'transaction_date' => $payment->payment_date,
                    'reference_type' => 'Payment',
                    'reference_id' => $payment->id,
                    'party_type' => $partyType,
                    'party_id' => $validated['party_id'],
                    'entry_type' => 'credit',
                    'account_type' => 'receivable',
                    'amount' => $payment->amount,
                    'notes' => 'Customer receipt adjusted in receivable ledger.',
                    'created_by' => $request->user()->id,
                ]);
            } else {
                record_account_transaction([
                    'transaction_date' => $payment->payment_date,
                    'reference_type' => 'Payment',
                    'reference_id' => $payment->id,
                    'party_type' => $partyType,
                    'party_id' => $validated['party_id'],
                    'entry_type' => 'debit',
                    'account_type' => 'payable',
                    'amount' => $payment->amount,
                    'notes' => 'Supplier payment adjusted in payable ledger.',
                    'created_by' => $request->user()->id,
                ]);

                record_account_transaction([
                    'transaction_date' => $payment->payment_date,
                    'reference_type' => 'Payment',
                    'reference_id' => $payment->id,
                    'party_type' => $partyType,
                    'party_id' => $validated['party_id'],
                    'entry_type' => 'credit',
                    'account_type' => $cashAccount,
                    'amount' => $payment->amount,
                    'notes' => 'Money paid out from business account.',
                    'created_by' => $request->user()->id,
                ]);
            }

            return $payment->fresh(['paymentMode', 'customer', 'supplier', 'allocations']);
        });

        return redirect()->route('admin.payments.show', $payment)->with('success', 'Payment saved successfully.');
    }

    // Roll back the previous payment rows so an edit can rebuild the totals without double counting.
    private function reversePaymentEffects(Payment $payment): void
    {
        foreach ($payment->allocations as $allocation) {
            $bill = $this->resolveBill((string) $allocation->bill_type, (int) $allocation->bill_id, (int) $payment->party_id, (string) $payment->party_type);
            $allocatedAmount = round((float) $allocation->allocated_amount, 2);

            $bill->paid_amount = round(max(0, (float) $bill->paid_amount - $allocatedAmount), 2);
            $bill->payment_status = $this->resolveBillPaymentStatus($bill, (string) $allocation->bill_type);
            $bill->save();

            if ($payment->party_type === 'customer' && $bill instanceof SalesInvoice && $bill->customer_id) {
                $customer = Customer::query()->lockForUpdate()->find($bill->customer_id);
                if ($customer) {
                    $customer->current_balance = round((float) $customer->current_balance + $allocatedAmount, 2);
                    $customer->save();
                }
            }
        }
    }

    // Resolve the bill model and ensure it belongs to the selected party before allocation is saved.
    private function resolveBill(string $billType, int $billId, int $partyId, string $partyType)
    {
        if ($billType === 'sales_invoice' && $partyType === 'customer') {
            return SalesInvoice::query()->where('customer_id', $partyId)->findOrFail($billId);
        }

        if ($billType === 'purchase' && $partyType === 'supplier') {
            return Purchase::query()->where('supplier_id', $partyId)->findOrFail($billId);
        }

        throw ValidationException::withMessages([
            'allocations' => 'Selected bill does not belong to the chosen party.',
        ]);
    }

    // This keeps outstanding calculation small and consistent for both sales and purchase bills.
    private function billOutstanding($bill, string $billType): float
    {
        if ($billType === 'sales_invoice') {
            return round(max(0, (float) $bill->total_amount - (float) $bill->paid_amount), 2);
        }

        return round(max(0, (float) $bill->grand_total - (float) $bill->paid_amount), 2);
    }

    // Update the bill payment status after each allocation.
    private function resolveBillPaymentStatus($bill, string $billType): string
    {
        $billTotal = $billType === 'sales_invoice'
            ? (float) $bill->total_amount
            : (float) $bill->grand_total;
        $paidAmount = (float) $bill->paid_amount;

        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $billTotal) {
            return 'paid';
        }

        return 'partial';
    }

    // Build one ready-to-render allocation row set for show and PDF.
    private function allocationRows(Payment $payment, bool $forEdit = false): Collection
    {
        return $payment->allocations->map(function (PaymentBillAllocation $allocation) use ($forEdit) {
            $bill = $allocation->bill_type === 'sales_invoice'
                ? SalesInvoice::query()->find($allocation->bill_id)
                : Purchase::query()->with('reference')->find($allocation->bill_id);

            if (!$bill) {
                return [
                    'bill_id' => $allocation->bill_id,
                    'bill_type_value' => $allocation->bill_type,
                    'bill_type' => ucfirst(str_replace('_', ' ', $allocation->bill_type)),
                    'bill_number' => '-',
                    'bill_date' => '-',
                    'bill_amount' => 0,
                    'total_paid' => 0,
                    'outstanding' => 0,
                    'allocated_amount' => (float) $allocation->allocated_amount,
                ];
            }

            $billAmount = $allocation->bill_type === 'sales_invoice'
                ? (float) $bill->total_amount
                : (float) $bill->grand_total;
            $paidAmount = $allocation->bill_type === 'sales_invoice'
                ? (float) $bill->paid_amount
                : (float) $bill->paid_amount;
            $outstanding = $allocation->bill_type === 'sales_invoice'
                ? (float) $bill->due_amount
                : (float) $bill->outstanding_amount;

            if ($forEdit) {
                $paidAmount = max(0, $paidAmount - (float) $allocation->allocated_amount);
                $outstanding = round(max(0, $outstanding + (float) $allocation->allocated_amount), 2);
            }

            return [
                'bill_id' => $allocation->bill_id,
                'bill_type_value' => $allocation->bill_type,
                'bill_type' => $allocation->bill_type === 'sales_invoice' ? 'Sales Invoice' : 'Purchase Bill',
                'bill_number' => $allocation->bill_type === 'sales_invoice'
                    ? $bill->reference
                    : ($bill->reference?->reference_no ?: ('PUR-' . $bill->id)),
                'bill_date' => $allocation->bill_type === 'sales_invoice'
                    ? $bill->invoice_date_show
                    : $bill->purchase_date_show,
                'bill_amount' => $billAmount,
                'total_paid' => $paidAmount,
                'outstanding' => $outstanding,
                'allocated_amount' => (float) $allocation->allocated_amount,
            ];
        })->values();
    }
}
