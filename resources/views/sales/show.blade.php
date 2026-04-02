@extends('layouts.main')

@section('title')
    Sales Invoice Detail
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">{{ $invoice->reference }}</h5>
                <p class="mb-0 text-muted">One invoice screen for payment, returns and stock trace.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.sales.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('admin.sales-invoices.print', $invoice) }}" target="_blank" class="btn btn-primary">
                    <i class="fa-solid fa-print"></i> Print / PDF
                </a>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="fa-solid fa-wallet"></i> Payment
                </button>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#returnModal">
                    <i class="fa-solid fa-rotate-left"></i> Return
                </button>
            </div>
        </div>

        <div id="salesInvoicePrintArea" class="print-sheet">
            <div class="print-sheet-header">
                <h2>{{ setting('app_name', 'Pharmacy Management System') }}</h2>
                <p>Sales invoice print copy</p>
                <div class="print-sheet-meta">
                    <span><strong>Invoice:</strong> {{ $invoice->reference }}</span>
                    <span><strong>Date:</strong> {{ $invoice->invoice_date_show }}</span>
                    <span><strong>Party:</strong> {{ $invoice->customer?->name ?? '-' }}</span>
                    <span><strong>Printed:</strong> {{ now()->format('M j, Y h:i A') }}</span>
                </div>
            </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Total Amount</p>
                        <h3 class="summary-card-value">{{ money_value($invoice->total_amount) }}</h3>
                        <span class="summary-card-note">Invoice total after line discount.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Paid Amount</p>
                        <h3 class="summary-card-value">{{ money_value($invoice->paid_amount) }}</h3>
                        <span class="summary-card-note">Money already collected.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Due Amount</p>
                        <h3 class="summary-card-value">{{ money_value($invoice->due_amount) }}</h3>
                        <span class="summary-card-note">Still pending from the party.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Payment</p>
                        <h3 class="summary-card-value">{{ $invoice->payment_label }}</h3>
                        <span class="summary-card-note">{{ $invoice->sale_type_label }} sale with {{ $invoice->payment_method_label }} method.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <strong class="d-block text-muted">Customer</strong>
                        <span>{{ $invoice->customer?->name ?? '-' }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong class="d-block text-muted">Sale Type</strong>
                        <span>{{ $invoice->sale_type_label }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong class="d-block text-muted">Date</strong>
                        <span>{{ $invoice->invoice_date_show }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong class="d-block text-muted">Status</strong>
                        <span class="report-badge {{ $invoice->status_badge_class }}">{{ $invoice->status_label }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">Invoice Items</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle js-datatable" data-page-length="10">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Qty</th>
                                <th>Free Qty</th>
                                <th>MRP</th>
                                <th>Unit Price</th>
                                <th>Discount %</th>
                                <th>CC Rate %</th>
                                <th>Free Goods Value</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item->product?->display_name ?? '-' }}</td>
                                    <td>{{ $item->batch?->batch_number ?? '-' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->free_qty ?? 0 }}</td>
                                    <td>{{ money_value($item->mrp ?? 0) }}</td>
                                    <td>{{ money_value($item->unit_price) }}</td>
                                    <td>{{ number_format((float) $item->discount_percent, 2) }}%</td>
                                    <td>{{ number_format((float) ($item->cc_rate ?? 0), 2) }}%</td>
                                    <td>{{ money_value($item->free_goods_value ?? 0) }}</td>
                                    <td>{{ money_value($item->subtotal) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Return History</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle js-datatable" data-page-length="10">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th>Qty</th>
                                <th>Refund</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invoice->returns as $index => $returnItem)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $returnItem->return_date_show }}</td>
                                    <td>{{ $returnItem->product?->display_name ?? '-' }}</td>
                                    <td>{{ $returnItem->batch?->batch_number ?? '-' }}</td>
                                    <td>{{ $returnItem->quantity }}</td>
                                    <td>{{ money_value($returnItem->refund_amount) }}</td>
                                    <td>{{ $returnItem->reason ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No return history yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        </div>

        <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-md modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.sales.payment', $invoice) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Update Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Payment Status</label>
                                    <select name="payment_status" class="form-select">
                                        <option value="unpaid" @selected($invoice->payment_status === 'unpaid')>Unpaid</option>
                                        <option value="partial" @selected($invoice->payment_status === 'partial')>Partial</option>
                                        <option value="paid" @selected($invoice->payment_status === 'paid')>Paid</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_mode_id" class="form-select">
                                        @foreach ($paymentModes as $mode)
                                            <option value="{{ $mode->id }}" @selected((int) $invoice->payment_mode_id === (int) $mode->id)>{{ $mode->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Paid Amount</label>
                                    <input type="number" min="0" step="0.01" name="paid_amount" class="form-control" value="{{ $invoice->paid_amount }}">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Save Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('admin.sales.return.store', $invoice) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Create Sales Return</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Invoice Item</label>
                                    <select name="sales_invoice_item_id" class="form-select" required>
                                        <option value="">Select item</option>
                                        @foreach ($invoice->items as $item)
                                            <option value="{{ $item->id }}">{{ $item->product?->display_name ?? '-' }} | Batch {{ $item->batch?->batch_number ?? '-' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" min="1" step="1" name="quantity" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Refund Amount</label>
                                    <input type="number" min="0" step="0.01" name="refund_amount" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Reason</label>
                                    <input type="text" name="reason" class="form-control" placeholder="Damaged / wrong item / customer return">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Notes</label>
                                    <input type="text" name="notes" class="form-control" placeholder="Short note for return">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fa fa-rotate-left"></i> Save Return
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            var hash = window.location.hash || '';

            if (hash === '#paymentModal') {
                var paymentModalEl = document.getElementById('paymentModal');
                if (paymentModalEl) {
                    bootstrap.Modal.getOrCreateInstance(paymentModalEl).show();
                }
            }

            if (hash === '#returnModal') {
                var returnModalEl = document.getElementById('returnModal');
                if (returnModalEl) {
                    bootstrap.Modal.getOrCreateInstance(returnModalEl).show();
                }
            }
        });
    </script>
@endsection
