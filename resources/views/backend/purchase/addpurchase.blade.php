@extends('backend.layouts.main')

@section('main-content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4>Add Purchase</h4>
        </div>
        <div class="card-body">
            <form id="purchaseForm">
                <input type="hidden" name="reference_id" value="">

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label>Reference No.</label>
                        <input type="text" class="form-control" readonly value="REF-0001">
                    </div>
                    <div class="col-md-4">
                        <label>Supplier</label>
                        <select name="supplier_id" class="form-control" required>
                            <option disabled selected>Select Supplier</option>
                            @foreach ($supplier as $supplierItem)
                                <option value="{{ $supplierItem->id }}" 
                                    {{ isset($prevPost) && $prevPost->supplier_id == $supplierItem->id ? 'selected' : '' }}>
                                    {{ $supplierItem->supplier_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="">
                    </div>
                </div>

                <hr>

                <h5>Items</h5>

                <table class="table table-bordered" id="itemsTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Batch No</th>
                            <th>Expiry</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                            <th><button type="button" id="addRowBtn" class="btn btn-sm btn-primary">+</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="items[0][product_id]" class="form-control product-select" required>
                                    <option disabled selected>Select Product</option>
                                    @foreach ($product as $productItem)
                                        <option value="{{ $productItem->id }}">{{ $productItem->product_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="text" name="items[0][batch_no]" class="form-control"></td>
                            <td><input type="month" name="items[0][expiry_date]" class="form-control"></td>
                            <td><input type="number" name="items[0][quantity]" class="form-control qty-input" value="0"></td>
                            <td><input type="number" step="0.01" name="items[0][purchase_price]" class="form-control price-input" value="0.00"></td>
                            <td><input type="text" name="items[0][subtotal]" class="form-control subtotal-input" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-danger removeRow">–</button></td>
                        </tr>
                    </tbody>
                </table>

                <div class="row mt-3">
                    <div class="col-md-6"></div>
                    <div class="col-md-3"><strong>Total</strong></div>
                    <div class="col-md-3">
                        <input type="text" id="grandTotal" class="form-control" readonly value="0.00">
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-success">Save Purchase</button>
                    <a href="#" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(function(){
        let rowIndex = $('#itemsTable tbody tr').length;

        $('#addRowBtn').on('click', () => {
            let blank = $('#itemsTable tbody tr:first').clone();
            blank.find('input,select').val('');
            blank.find('.qty-input, .price-input, .subtotal-input').val(0);
            blank.find('select').attr('name', `items[${rowIndex}][product_id]`);
            ['batch_no','expiry_date','quantity','purchase_price','subtotal'].forEach((field, i) => {
                blank.find(`td:eq(${i}) input`).attr('name', `items[${rowIndex}][${field}]`);
            });
            $('#itemsTable tbody').append(blank);
            rowIndex++;
        });

        $('#itemsTable').on('click', '.removeRow', function(){
            if($('#itemsTable tbody tr').length > 1) $(this).closest('tr').remove(), calculateTotals();
        });

        $('#itemsTable').on('input', '.qty-input, .price-input', function(){
            let tr = $(this).closest('tr');
            let q = parseFloat(tr.find('.qty-input').val()) || 0;
            let p = parseFloat(tr.find('.price-input').val()) || 0;
            let sub = (q * p).toFixed(2);
            tr.find('.subtotal-input').val(sub);
            calculateTotals();
        });

        function calculateTotals() {
            let gt = 0;
            $('#itemsTable .subtotal-input').each(function(){
                gt += parseFloat($(this).val()) || 0;
            });
            $('#grandTotal').val(gt.toFixed(2));
        }

        calculateTotals();
    });
</script>
@endsection
