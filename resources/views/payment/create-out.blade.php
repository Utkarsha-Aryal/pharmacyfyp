@extends('layouts.main')

@section('title')
    Payment Out
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Payment Out</h5>
                <p class="mb-0 text-muted">Pay suppliers and optionally settle one or more purchase bills.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form action="{{ route('admin.payments.out.store') }}" method="POST" id="paymentOutForm" class="card custom-card">
            @csrf
            <input type="hidden" name="party_type" value="supplier">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
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
                    <div class="col-md-3">
                        <label class="form-label">Payment Mode</label>
                        <select name="payment_mode_id" class="form-select js-select2" data-placeholder="Select mode" required>
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
            </div>
            <div class="card-body border-top">
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
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary" id="paymentOutSubmitBtn">
                    <i class="fa fa-save"></i> Save Payment Out
                </button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script>
        $(function () {
            function moneyFormat(value) {
                return '{{ currency_symbol() }} ' + parseFloat(value || 0).toFixed(2);
            }

            function updatePaymentOutBalance() {
                var amount = parseFloat($('#paymentOutAmount').val()) || 0;
                var allocated = 0;

                $('#paymentOutBillTable .allocation-input').each(function () {
                    allocated += parseFloat($(this).val()) || 0;
                });

                $('#paymentOutRemaining').text(moneyFormat(amount - allocated));
                $('#paymentOutAllocationError').toggleClass('d-none', allocated <= amount);
                $('#paymentOutSubmitBtn').prop('disabled', allocated > amount);
            }

            function renderPaymentOutBills(rows) {
                var tbody = $('#paymentOutBillTable tbody');
                tbody.empty();

                if (!rows.length) {
                    tbody.html('<tr><td colspan="6" class="text-center text-muted">No outstanding bills found.</td></tr>');
                    updatePaymentOutBalance();
                    return;
                }

                rows.forEach(function (row, index) {
                    tbody.append(
                        '<tr>' +
                            '<td>' + row.bill_number + '<input type="hidden" name="allocations[' + index + '][bill_id]" value="' + row.bill_id + '"><input type="hidden" name="allocations[' + index + '][bill_type]" value="' + row.bill_type + '"></td>' +
                            '<td>' + row.bill_date + '</td>' +
                            '<td>' + moneyFormat(row.net_amount) + '</td>' +
                            '<td>' + moneyFormat(row.total_paid) + '</td>' +
                            '<td>' + moneyFormat(row.outstanding) + '</td>' +
                            '<td><input type="number" step="0.01" min="0" max="' + row.outstanding + '" name="allocations[' + index + '][allocated_amount]" class="form-control allocation-input" value=""></td>' +
                        '</tr>'
                    );
                });

                updatePaymentOutBalance();
            }

            $(document).on('change', '#paymentOutParty', function () {
                var partyId = $(this).val();
                if (!partyId) {
                    renderPaymentOutBills([]);
                    return;
                }

                $.get('{{ route('admin.payments.outstanding-bills') }}', {
                    party_id: partyId,
                    party_type: 'supplier'
                }, function (response) {
                    renderPaymentOutBills(response || []);
                });
            });

            $(document).on('input', '#paymentOutAmount, #paymentOutBillTable .allocation-input', updatePaymentOutBalance);
        });
    </script>
@endsection
