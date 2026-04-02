@extends('layouts.main')

@section('title')
    Payments
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Payments</h5>
                <p class="mb-0 text-muted">Track payment in from customers and payment out to suppliers in one place.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentInModal">
                    <i class="fa-solid fa-arrow-down"></i> Payment In
                </button>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#paymentOutModal">
                    <i class="fa-solid fa-arrow-up"></i> Payment Out
                </button>
            </div>
        </div>

        <form method="GET" class="card custom-card filter-card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select js-select2" data-placeholder="All type" data-allow-clear="1">
                            <option value="">All</option>
                            <option value="in" @selected(($filters['type'] ?? '') === 'in')>Payment In</option>
                            <option value="out" @selected(($filters['type'] ?? '') === 'out')>Payment Out</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Party Type</label>
                        <select name="party_type" class="form-select js-select2" data-placeholder="All party" data-allow-clear="1">
                            <option value="">All</option>
                            <option value="customer" @selected(($filters['party_type'] ?? '') === 'customer')>Customer</option>
                            <option value="supplier" @selected(($filters['party_type'] ?? '') === 'supplier')>Supplier</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary mt-md-4">
                            <i class="fa-solid fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary mt-md-4">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </a>
                    </div>
                </div>
            </div>
        </form>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Payment List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Party</th>
                                <th>Mode</th>
                                <th>Amount</th>
                                <th>Linked Bills</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($payments as $index => $payment)
                                <tr>
                                    <td>{{ $payments->firstItem() + $index }}</td>
                                    <td>{{ $payment->payment_date_show }}</td>
                                    <td>
                                        <span class="report-badge {{ $payment->type === 'in' ? 'report-badge-success' : 'report-badge-warning' }}">
                                            {{ $payment->type === 'in' ? 'Payment In' : 'Payment Out' }}
                                        </span>
                                    </td>
                                    <td>{{ $payment->party_name }}</td>
                                    <td>{{ $payment->paymentMode?->name ?? '-' }}</td>
                                    <td>{{ money_value($payment->amount) }}</td>
                                    <td>{{ $payment->allocations->count() }}</td>
                                    <td>
                                        <div class="table-action-group">
                                            <a href="{{ route('admin.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary table-action-btn" title="View">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-warning table-action-btn editPaymentBtn" title="Edit" data-url="{{ route('admin.payments.edit', $payment) }}">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <a href="{{ route('admin.payments.print', $payment) }}" target="_blank" class="btn btn-sm btn-outline-dark table-action-btn" title="Print / PDF">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No payment records found yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $payments->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentInModal" tabindex="-1" aria-hidden="true" data-open-intent="{{ $openModal === 'in' ? '1' : '0' }}">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('admin.payments.in.store') }}" method="POST" id="paymentInForm">
                    @csrf
                    <input type="hidden" name="payment_id" value="">
                    <input type="hidden" name="party_type" value="customer">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentInModalTitle">Payment In</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Customer</span>
                                    @can('party.manage')
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickCustomerModal" data-quick-target-select="#paymentInParty">
                                        <i class="fa-solid fa-plus"></i> Quick Add
                                        </button>
                                    @endcan
                                </label>
                                <select name="party_id" id="paymentInParty" class="form-select js-select2" data-placeholder="Select customer" required>
                                    <option value="">Select customer</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" id="paymentInAmount" class="form-control" step="0.01" min="0.01" value="0.00" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Payment Mode</span>
                                    @can('settings.manage')
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickPaymentModeModal" data-quick-target-select="#paymentInMode">
                                        <i class="fa-solid fa-plus"></i>
                                        </button>
                                    @endcan
                                </label>
                                <select name="payment_mode_id" id="paymentInMode" class="form-select js-select2" data-placeholder="Select mode" required>
                                    <option value="">Select mode</option>
                                    @foreach ($paymentModes as $mode)
                                        <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control" placeholder="Cheque no / transaction id">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" class="form-control" placeholder="Short note if needed">
                            </div>
                        </div>

                        <div class="border rounded-3 p-3 mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#paymentInAllocationWrap">
                                    <i class="fa-solid fa-link"></i> Link to Bills (Optional)
                                </button>
                                <div class="fw-semibold">Unallocated Amount: <span id="paymentInRemaining">{{ money_value(0) }}</span></div>
                            </div>
                            <div class="collapse show" id="paymentInAllocationWrap">
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle" id="paymentInBillTable">
                                        <thead>
                                            <tr>
                                                <th>Bill Number</th>
                                                <th>Date</th>
                                                <th>Bill Amount</th>
                                                <th>Already Paid</th>
                                                <th>Outstanding</th>
                                                <th>Allocate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">Select customer to load outstanding bills.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-danger small d-none" id="paymentInAllocationError">Allocated total cannot be more than payment amount.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="paymentInSubmitBtn">
                            <i class="fa fa-save"></i> Save Payment In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="paymentOutModal" tabindex="-1" aria-hidden="true" data-open-intent="{{ $openModal === 'out' ? '1' : '0' }}">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('admin.payments.out.store') }}" method="POST" id="paymentOutForm">
                    @csrf
                    <input type="hidden" name="payment_id" value="">
                    <input type="hidden" name="party_type" value="supplier">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentOutModalTitle">Payment Out</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Supplier</span>
                                    @can('purchase.supplier')
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickSupplierModal" data-quick-target-select="#paymentOutParty">
                                        <i class="fa-solid fa-plus"></i> Quick Add
                                        </button>
                                    @endcan
                                </label>
                                <select name="party_id" id="paymentOutParty" class="form-select js-select2" data-placeholder="Select supplier" required>
                                    <option value="">Select supplier</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Amount</label>
                                <input type="number" name="amount" id="paymentOutAmount" class="form-control" step="0.01" min="0.01" value="0.00" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label d-flex justify-content-between align-items-center">
                                    <span>Payment Mode</span>
                                    @can('settings.manage')
                                        <button type="button" class="btn btn-sm btn-outline-primary quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickPaymentModeModal" data-quick-target-select="#paymentOutMode">
                                        <i class="fa-solid fa-plus"></i>
                                        </button>
                                    @endcan
                                </label>
                                <select name="payment_mode_id" id="paymentOutMode" class="form-select js-select2" data-placeholder="Select mode" required>
                                    <option value="">Select mode</option>
                                    @foreach ($paymentModes as $mode)
                                        <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control" placeholder="Cheque no / transaction id">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" class="form-control" placeholder="Short note if needed">
                            </div>
                        </div>

                        <div class="border rounded-3 p-3 mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#paymentOutAllocationWrap">
                                    <i class="fa-solid fa-link"></i> Link to Bills (Optional)
                                </button>
                                <div class="fw-semibold">Unallocated Amount: <span id="paymentOutRemaining">{{ money_value(0) }}</span></div>
                            </div>
                            <div class="collapse show" id="paymentOutAllocationWrap">
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle" id="paymentOutBillTable">
                                        <thead>
                                            <tr>
                                                <th>Bill Number</th>
                                                <th>Date</th>
                                                <th>Bill Amount</th>
                                                <th>Already Paid</th>
                                                <th>Outstanding</th>
                                                <th>Allocate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">Select supplier to load outstanding bills.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-danger small d-none" id="paymentOutAllocationError">Allocated total cannot be more than payment amount.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="paymentOutSubmitBtn">
                            <i class="fa fa-save"></i> Save Payment Out
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('partials.quick-create-modals', [
        'showQuickCustomer' => auth()->user()->can('party.manage'),
        'showQuickSupplier' => auth()->user()->can('purchase.supplier'),
        'showQuickPaymentMode' => auth()->user()->can('settings.manage'),
    ])
@endsection

@section('script')
    <script>
        $(function () {
            $('#paymentInForm').data('default-action', $('#paymentInForm').attr('action'));
            $('#paymentInForm').data('default-title', $('#paymentInModalTitle').text());
            $('#paymentInForm').data('default-button', $('#paymentInSubmitBtn').html());
            $('#paymentOutForm').data('default-action', $('#paymentOutForm').attr('action'));
            $('#paymentOutForm').data('default-title', $('#paymentOutModalTitle').text());
            $('#paymentOutForm').data('default-button', $('#paymentOutSubmitBtn').html());
            var editPaymentId = @json($editPaymentId ?? null);

            function moneyFormat(value) {
                return '{{ currency_symbol() }} ' + parseFloat(value || 0).toFixed(2);
            }

            function resetPaymentForm(prefix) {
                var $form = $('#' + prefix + 'Form');
                var defaultAction = $form.data('default-action');
                var defaultTitle = $form.data('default-title');
                var defaultButton = $form.data('default-button');

                if ($form.length && defaultAction) {
                    $form.attr('action', defaultAction);
                }

                if ($form.length) {
                    $form.find('input[name="payment_id"]').val('');
                    $form.find('input[name="party_type"]').val(prefix === 'paymentIn' ? 'customer' : 'supplier');
                    $form.trigger('reset');
                    $('#' + prefix + 'Party').val(null).trigger('change');
                    $('#' + prefix + 'Mode').val(null).trigger('change');
                    $('#' + prefix + 'Amount').val('0.00');
                }

                $('#' + prefix + 'ModalTitle').text(defaultTitle);
                $('#' + prefix + 'SubmitBtn').html(defaultButton);
                $('#' + prefix + 'Remaining').text(moneyFormat(0));
                $('#' + prefix + 'BillTable tbody').html('<tr><td colspan="6" class="text-center text-muted">Select ' + (prefix === 'paymentIn' ? 'customer' : 'supplier') + ' to load outstanding bills.</td></tr>');
                $('#' + prefix + 'AllocationError').addClass('d-none');
                $('#' + prefix + 'SubmitBtn').prop('disabled', false);

                if (window.initEnhancedSelects) {
                    window.initEnhancedSelects(document.getElementById(prefix + 'Modal'));
                }
            }

            function updateAllocationState(prefix) {
                var amount = parseFloat($('#' + prefix + 'Amount').val()) || 0;
                var allocated = 0;

                $('#' + prefix + 'BillTable .allocation-input').each(function () {
                    allocated += parseFloat($(this).val()) || 0;
                });

                $('#' + prefix + 'Remaining').text(moneyFormat(amount - allocated));
                $('#' + prefix + 'AllocationError').toggleClass('d-none', allocated <= amount);
                $('#' + prefix + 'SubmitBtn').prop('disabled', allocated > amount);
            }

            function renderPaymentBills(prefix, rows) {
                var tbody = $('#' + prefix + 'BillTable tbody');
                tbody.empty();

                if (!rows.length) {
                    tbody.html('<tr><td colspan="6" class="text-center text-muted">No outstanding bills found.</td></tr>');
                    updateAllocationState(prefix);
                    return;
                }

                rows.forEach(function (row, index) {
                    tbody.append(
                        '<tr>' +
                            '<td>' + row.bill_number + '<input type="hidden" name="allocations[' + index + '][bill_id]" value="' + (row.bill_id || '') + '"><input type="hidden" name="allocations[' + index + '][bill_type]" value="' + (row.bill_type_value || row.bill_type || '') + '"></td>' +
                            '<td>' + row.bill_date + '</td>' +
                            '<td>' + moneyFormat(row.bill_amount || row.net_amount) + '</td>' +
                            '<td>' + moneyFormat(row.total_paid || 0) + '</td>' +
                            '<td>' + moneyFormat(row.outstanding || 0) + '</td>' +
                            '<td><input type="number" step="0.01" min="0" max="' + (row.outstanding || 0) + '" name="allocations[' + index + '][allocated_amount]" class="form-control allocation-input" value="' + (row.allocated_amount || '') + '"></td>' +
                        '</tr>'
                    );
                });

                updateAllocationState(prefix);
            }

            function loadOutstandingBills(prefix, partyType, partyId) {
                if (!partyId) {
                    renderPaymentBills(prefix, []);
                    return;
                }

                $.get('{{ route('admin.payments.outstanding-bills') }}', {
                    party_id: partyId,
                    party_type: partyType
                }, function (response) {
                    renderPaymentBills(prefix, response || []);
                });
            }

            function fillPaymentForm(prefix, payload) {
                var $form = $('#' + prefix + 'Form');
                var modalElement = document.getElementById(prefix + 'Modal');
                var modalInstance = modalElement ? new bootstrap.Modal(modalElement) : null;

                $(modalElement).data('prefilling', true);
                $form.attr('action', payload.update_url);
                $form.find('input[name="payment_id"]').val(payload.id);
                $form.find('input[name="party_type"]').val(payload.party_type);
                $('#' + prefix + 'ModalTitle').text((payload.type === 'in' ? 'Edit Payment In' : 'Edit Payment Out'));
                $('#' + prefix + 'SubmitBtn').html('<i class="fa fa-save"></i> Update Payment');

                $('#' + prefix + 'Party').val(payload.party_id).trigger('change');
                $('#' + prefix + 'Amount').val(payload.amount);
                $('#' + prefix + 'Amount').trigger('input');
                $('#' + prefix + 'Mode').val(payload.payment_mode_id).trigger('change');
                $form.find('input[name="payment_date"]').val(payload.payment_date);
                $form.find('input[name="reference_number"]').val(payload.reference_number || '');
                $form.find('input[name="notes"]').val(payload.notes || '');

                renderPaymentBills(prefix, payload.rows || []);
                $(modalElement).data('prefilling', false);

                if (modalInstance) {
                    modalInstance.show();
                }
            }

            $(document).on('change', '#paymentInParty', function () {
                if ($('#paymentInModal').data('prefilling')) {
                    return;
                }
                loadOutstandingBills('paymentIn', 'customer', $(this).val());
            });

            $(document).on('change', '#paymentOutParty', function () {
                if ($('#paymentOutModal').data('prefilling')) {
                    return;
                }
                loadOutstandingBills('paymentOut', 'supplier', $(this).val());
            });

            $(document).on('input', '#paymentInAmount, #paymentInBillTable .allocation-input', function () {
                updateAllocationState('paymentIn');
            });

            $(document).on('input', '#paymentOutAmount, #paymentOutBillTable .allocation-input', function () {
                updateAllocationState('paymentOut');
            });

            $(document).on('click', '.editPaymentBtn', function () {
                var url = $(this).data('url');

                if (!url) {
                    return;
                }

                $.get(url, function (response) {
                    if (!response || response.type !== 'success' || !response.data) {
                        showNotification('Could not load payment data.', 'error');
                        return;
                    }

                    var prefix = response.data.type === 'in' ? 'paymentIn' : 'paymentOut';
                    fillPaymentForm(prefix, response.data);
                }).fail(function () {
                    showNotification('Could not load payment data.', 'error');
                });
            });

            if ($('#paymentInModal').data('open-intent') == 1) {
                new bootstrap.Modal(document.getElementById('paymentInModal')).show();
            }

            if ($('#paymentOutModal').data('open-intent') == 1) {
                new bootstrap.Modal(document.getElementById('paymentOutModal')).show();
            }

            if (editPaymentId) {
                $.get('{{ url('/admin/payments') }}/' + editPaymentId + '/edit', function (response) {
                    if (response && response.type === 'success' && response.data) {
                        var prefix = response.data.type === 'in' ? 'paymentIn' : 'paymentOut';
                        fillPaymentForm(prefix, response.data);
                    }
                });
            }

            $('#paymentInModal, #paymentOutModal').on('hidden.bs.modal', function () {
                var prefix = this.id === 'paymentInModal' ? 'paymentIn' : 'paymentOut';
                resetPaymentForm(prefix);
            });
        });
    </script>
@endsection
