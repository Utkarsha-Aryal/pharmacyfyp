@extends('layouts.main')

@section('title')
    Edit Purchase Return
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Edit Purchase Return</h5>
                <p class="mb-0 text-muted">Adjust the already returned rows and save the stock rollback again.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase-returns.index') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form action="{{ route('admin.purchase-returns.update', $purchaseReturn) }}" method="POST" id="purchaseReturnForm" class="card custom-card">
            @csrf
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Supplier</label>
                        <select name="supplier_id" id="purchaseReturnSupplier" class="form-select js-select2" data-placeholder="Select supplier" required>
                            <option value="">Select supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((int) $purchaseReturn->supplier_id === (int) $supplier->id)>{{ $supplier->supplier_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Purchase Bill</label>
                        <select name="purchase_id" id="purchaseReturnPurchase" class="form-select js-select2" data-placeholder="Select purchase" required>
                            <option value="{{ $purchaseReturn->purchase_id }}">
                                {{ $purchaseReturn->purchase?->reference?->reference_no ?: ('PUR-' . $purchaseReturn->purchase_id) }} | {{ $purchaseReturn->purchase?->purchase_date_show ?? '-' }} | {{ money_value($purchaseReturn->purchase?->grand_total ?? 0) }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Return Date</label>
                        <input type="date" name="return_date" class="form-control" value="{{ $purchaseReturn->return_date }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Short note" value="{{ $purchaseReturn->notes }}">
                    </div>
                </div>
            </div>
            <div class="card-body border-top">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="purchaseReturnItemsTable">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Batch No</th>
                                <th>Original Qty</th>
                                <th>Already Returned</th>
                                <th>Max Returnable</th>
                                <th>Return Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($itemsRows as $index => $row)
                                <tr>
                                    <td>
                                        {{ $row['product_name'] }}
                                        <input type="hidden" name="items[{{ $index }}][purchase_item_id]" value="{{ $row['purchase_item_id'] }}">
                                        <input type="hidden" name="items[{{ $index }}][batch_id]" value="{{ $row['batch_id'] }}">
                                        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $row['product_id'] }}">
                                    </td>
                                    <td>{{ $row['batch_no'] }}</td>
                                    <td>{{ $row['original_qty'] }}</td>
                                    <td>{{ $row['already_returned'] }}</td>
                                    <td>{{ $row['max_returnable'] }}</td>
                                    <td>
                                        <input type="number" name="items[{{ $index }}][return_qty]" class="form-control" min="0" max="{{ $row['max_returnable'] }}" value="{{ $row['return_qty'] }}">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No returnable items found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Update Purchase Return
                </button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script>
        $(function () {
            $(document).on('change', '#purchaseReturnSupplier', function () {
                var supplierId = $(this).val();
                var $purchaseSelect = $('#purchaseReturnPurchase');
                $purchaseSelect.empty().append('<option value="">Select purchase</option>').trigger('change');
                $('#purchaseReturnItemsTable tbody').html('<tr><td colspan="6" class="text-center text-muted">Select purchase bill to load returnable items.</td></tr>');

                if (!supplierId) {
                    return;
                }

                $.get('{{ route('admin.purchase-returns.get-purchases') }}', { supplier_id: supplierId }, function (response) {
                    (response || []).forEach(function (row) {
                        $purchaseSelect.append(new Option(row.text, row.id, false, false));
                    });
                });
            });

            $(document).on('change', '#purchaseReturnPurchase', function () {
                var purchaseId = $(this).val();
                var tbody = $('#purchaseReturnItemsTable tbody');
                tbody.html('<tr><td colspan="6" class="text-center text-muted">Loading items...</td></tr>');

                if (!purchaseId) {
                    tbody.html('<tr><td colspan="6" class="text-center text-muted">Select purchase bill to load returnable items.</td></tr>');
                    return;
                }

                $.get('{{ route('admin.purchase-returns.get-items') }}', { purchase_id: purchaseId }, function (response) {
                    tbody.empty();

                    if (!(response || []).length) {
                        tbody.html('<tr><td colspan="6" class="text-center text-muted">No returnable items found.</td></tr>');
                        return;
                    }

                    response.forEach(function (row, index) {
                        tbody.append(
                            '<tr>' +
                                '<td>' + row.product_name +
                                    '<input type="hidden" name="items[' + index + '][purchase_item_id]" value="' + row.purchase_item_id + '">' +
                                    '<input type="hidden" name="items[' + index + '][batch_id]" value="' + row.batch_id + '">' +
                                    '<input type="hidden" name="items[' + index + '][product_id]" value="' + row.product_id + '">' +
                                '</td>' +
                                '<td>' + row.batch_no + '</td>' +
                                '<td>' + row.original_qty + '</td>' +
                                '<td>' + row.already_returned + '</td>' +
                                '<td>' + row.max_returnable + '</td>' +
                                '<td><input type="number" name="items[' + index + '][return_qty]" class="form-control" min="0" max="' + row.max_returnable + '" value="0"></td>' +
                            '</tr>'
                        );
                    });
                });
            });
        });
    </script>
@endsection
