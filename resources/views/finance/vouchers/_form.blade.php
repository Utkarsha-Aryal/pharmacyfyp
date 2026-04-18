@php
    $rows = old('entries', $entryRows ?? []);
    $selectedVoucherType = old('voucher_type', $voucher?->voucher_type ?? 'journal');
    $selectedVoucherDate = old('voucher_date', $voucher?->voucher_date ?? now()->toDateString());
@endphp

<form action="{{ $formAction }}" method="POST" class="card custom-card" id="voucherForm">
    @csrf
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Voucher Date</label>
                <input type="date" name="voucher_date" class="form-control" value="{{ $selectedVoucherDate }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Voucher Type</label>
                <select name="voucher_type" class="form-select js-select2" data-placeholder="Select voucher type" required>
                    @foreach ($voucherTypes as $voucherTypeKey => $voucherTypeLabel)
                        <option value="{{ $voucherTypeKey }}" @selected($selectedVoucherType === $voucherTypeKey)>{{ $voucherTypeLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Notes</label>
                <input type="text" name="notes" class="form-control" value="{{ old('notes', $voucher?->notes ?? '') }}" placeholder="Short narration for this voucher">
            </div>
            <div class="col-12">
                <div class="alert alert-light border mb-0 small text-muted">
                    Receivable lines must point to a customer. Payable lines must point to a supplier. Debit and credit totals must match before saving.
                </div>
            </div>
        </div>
    </div>

    <div class="card-body border-top">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div class="card-title mb-0">Voucher Entries</div>
            <button type="button" class="btn btn-primary btn-sm" id="addVoucherRow">
                <i class="fa-solid fa-plus"></i> Add Line
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="voucherEntriesTable">
                <thead>
                    <tr>
                        <th style="width: 60px;">S.No</th>
                        <th style="width: 180px;">Account</th>
                        <th style="width: 150px;">Party Type</th>
                        <th>Party</th>
                        <th style="width: 140px;">Entry</th>
                        <th style="width: 150px;">Amount</th>
                        <th>Notes</th>
                        <th style="width: 70px;">Action</th>
                    </tr>
                </thead>
                <tbody data-next-index="{{ count($rows) }}">
                    @foreach ($rows as $index => $row)
                        <tr>
                            <td class="voucher-row-number">{{ $index + 1 }}</td>
                            <td>
                                <select name="entries[{{ $index }}][account_type]" class="form-select voucher-account-type" required>
                                    <option value="">Select account</option>
                                    @foreach ($accountTypes as $accountTypeKey => $accountTypeLabel)
                                        <option value="{{ $accountTypeKey }}" @selected(($row['account_type'] ?? '') === $accountTypeKey)>{{ $accountTypeLabel }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="entries[{{ $index }}][party_type]" class="form-select voucher-party-type">
                                    <option value="">No party</option>
                                    <option value="customer" @selected(($row['party_type'] ?? '') === 'customer')>Customer</option>
                                    <option value="supplier" @selected(($row['party_type'] ?? '') === 'supplier')>Supplier</option>
                                </select>
                            </td>
                            <td>
                                <select name="entries[{{ $index }}][party_id]" class="form-select voucher-party-select" data-selected="{{ $row['party_id'] ?? '' }}">
                                    <option value="">Select party</option>
                                </select>
                            </td>
                            <td>
                                <select name="entries[{{ $index }}][entry_type]" class="form-select voucher-entry-type" required>
                                    <option value="debit" @selected(($row['entry_type'] ?? 'debit') === 'debit')>Debit</option>
                                    <option value="credit" @selected(($row['entry_type'] ?? '') === 'credit')>Credit</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0.01" name="entries[{{ $index }}][amount]" class="form-control voucher-amount-input" value="{{ $row['amount'] ?? '' }}" required>
                            </td>
                            <td>
                                <input type="text" name="entries[{{ $index }}][notes]" class="form-control" value="{{ $row['notes'] ?? '' }}" placeholder="Line note">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger removeVoucherRow table-action-btn">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Debit Total</th>
                        <th><input type="text" id="voucherDebitTotal" class="form-control" value="0.00" readonly></th>
                        <th colspan="2"></th>
                    </tr>
                    <tr>
                        <th colspan="5" class="text-end">Credit Total</th>
                        <th><input type="text" id="voucherCreditTotal" class="form-control" value="0.00" readonly></th>
                        <th colspan="2"></th>
                    </tr>
                    <tr>
                        <th colspan="5" class="text-end">Difference</th>
                        <th><input type="text" id="voucherDifferenceTotal" class="form-control" value="0.00" readonly></th>
                        <th colspan="2"><span class="badge bg-success" id="voucherBalanceBadge">Balanced</span></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <a href="{{ route('admin.finance.vouchers.index') }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save"></i> {{ $submitLabel }}
        </button>
    </div>
</form>

<template id="voucherRowTemplate">
    <tr>
        <td class="voucher-row-number">__ROW__</td>
        <td>
            <select name="entries[__INDEX__][account_type]" class="form-select voucher-account-type" required>
                <option value="">Select account</option>
                @foreach ($accountTypes as $accountTypeKey => $accountTypeLabel)
                    <option value="{{ $accountTypeKey }}">{{ $accountTypeLabel }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="entries[__INDEX__][party_type]" class="form-select voucher-party-type">
                <option value="">No party</option>
                <option value="customer">Customer</option>
                <option value="supplier">Supplier</option>
            </select>
        </td>
        <td>
            <select name="entries[__INDEX__][party_id]" class="form-select voucher-party-select">
                <option value="">Select party</option>
            </select>
        </td>
        <td>
            <select name="entries[__INDEX__][entry_type]" class="form-select voucher-entry-type" required>
                <option value="debit">Debit</option>
                <option value="credit">Credit</option>
            </select>
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" name="entries[__INDEX__][amount]" class="form-control voucher-amount-input" value="" required>
        </td>
        <td>
            <input type="text" name="entries[__INDEX__][notes]" class="form-control" value="" placeholder="Line note">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger removeVoucherRow table-action-btn">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

@section('script')
    <script>
        $(function () {
            var customers = @json($customers->map(fn ($customer) => ['id' => $customer->id, 'text' => $customer->name])->values());
            var suppliers = @json($suppliers->map(fn ($supplier) => ['id' => $supplier->id, 'text' => $supplier->supplier_name])->values());
            var $tableBody = $('#voucherEntriesTable tbody');
            var $template = $('#voucherRowTemplate');

            function updateVoucherRowNumbers() {
                $('#voucherEntriesTable tbody tr').each(function (index) {
                    $(this).find('.voucher-row-number').text(index + 1);
                });
            }

            function updateVoucherTotals() {
                var debitTotal = 0;
                var creditTotal = 0;

                $('#voucherEntriesTable tbody tr').each(function () {
                    var amount = parseFloat($(this).find('.voucher-amount-input').val()) || 0;
                    var entryType = $(this).find('.voucher-entry-type').val();

                    if (entryType === 'credit') {
                        creditTotal += amount;
                    } else {
                        debitTotal += amount;
                    }
                });

                var difference = Math.abs(debitTotal - creditTotal);
                $('#voucherDebitTotal').val(debitTotal.toFixed(2));
                $('#voucherCreditTotal').val(creditTotal.toFixed(2));
                $('#voucherDifferenceTotal').val(difference.toFixed(2));

                if (difference === 0 && debitTotal > 0 && creditTotal > 0) {
                    $('#voucherBalanceBadge').removeClass('bg-danger bg-warning text-dark').addClass('bg-success').text('Balanced');
                    return;
                }

                $('#voucherBalanceBadge').removeClass('bg-success').addClass('bg-warning text-dark').text('Not balanced');
            }

            function fillPartyOptions($row) {
                var accountType = $row.find('.voucher-account-type').val();
                var $partyType = $row.find('.voucher-party-type');
                var $partySelect = $row.find('.voucher-party-select');
                var selectedPartyId = $partySelect.data('selected') || '';
                var partyTypeValue = $partyType.val();

                if (accountType === 'receivable') {
                    partyTypeValue = 'customer';
                    $partyType.val('customer').prop('disabled', true);
                } else if (accountType === 'payable') {
                    partyTypeValue = 'supplier';
                    $partyType.val('supplier').prop('disabled', true);
                } else {
                    $partyType.prop('disabled', false);
                }

                $partySelect.empty().append('<option value="">Select party</option>');

                if (!partyTypeValue) {
                    $partySelect.prop('disabled', true);
                    return;
                }

                var source = partyTypeValue === 'customer' ? customers : suppliers;
                source.forEach(function (row) {
                    var option = new Option(row.text, row.id, false, String(selectedPartyId) === String(row.id));
                    $partySelect.append(option);
                });

                $partySelect.prop('disabled', false);
                $partySelect.data('selected', '');
            }

            function initVoucherRows() {
                $('#voucherEntriesTable tbody tr').each(function () {
                    var $row = $(this);
                    $row.find('.voucher-party-select').data('selected', $row.find('.voucher-party-select').data('selected') || '');
                    fillPartyOptions($row);
                });

                updateVoucherRowNumbers();
                updateVoucherTotals();
            }

            $(document).on('click', '#addVoucherRow', function () {
                var nextIndex = parseInt($tableBody.data('next-index') || $tableBody.children().length || 0, 10);
                var html = $template.html()
                    .replace(/__INDEX__/g, nextIndex)
                    .replace(/__ROW__/g, nextIndex + 1);

                $tableBody.append(html);
                $tableBody.data('next-index', nextIndex + 1);
                fillPartyOptions($tableBody.find('tr:last'));
                updateVoucherRowNumbers();
                updateVoucherTotals();
            });

            $(document).on('click', '.removeVoucherRow', function () {
                if ($('#voucherEntriesTable tbody tr').length <= 2) {
                    return;
                }

                $(this).closest('tr').remove();
                updateVoucherRowNumbers();
                updateVoucherTotals();
            });

            $(document).on('change', '.voucher-account-type', function () {
                var $row = $(this).closest('tr');
                $row.find('.voucher-party-select').data('selected', '');
                fillPartyOptions($row);
            });

            $(document).on('change', '.voucher-party-type', function () {
                var $row = $(this).closest('tr');
                $row.find('.voucher-party-select').data('selected', '');
                fillPartyOptions($row);
            });

            $(document).on('input change', '.voucher-entry-type, .voucher-amount-input', function () {
                updateVoucherTotals();
            });

            initVoucherRows();
        });
    </script>
@endsection
