@extends('backend.layouts.main')

@section('title')
    Product Batches
@endsection

@section('main-content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Batches for Product: <span class="text-primary">{{$product->product_name}}</span></h5>
        </div>
    </div>
    <!-- Page Header Close -->

    <div class="row">
        <!-- Form -->
        <div class="col-xl-4">
            <div class="card custom-card">
                <form action="{{route('batch.save')}}" method="POST" id="batchForm">
                    <div class="card-body">
                        <div class="row gy-4">
                            <input type="hidden" value="" name="id" id="id">
                            <input type="hidden" value="{{$product->id}}" name="product_id" id="product_id">

                            <div class="col-12">
                                <label for="batch_no" class="form-label">Batch Number</label>
                                <input type="text" class="form-control" id="batch_no" name="batch_no" placeholder="Enter batch number">
                            </div>

                            <div class="col-12">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="month" class="form-control" id="expiry_date" name="expiry_date">
                            </div>

                            <div class="col-12">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" placeholder="Enter quantity">
                            </div>

                            <div class="col-12">
                                <label for="purchase_price" class="form-label">Purchase Price</label>
                                <input type="text" class="form-control" id="purchase_price" name="purchase_price" placeholder="Enter price">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="button" class="btn btn-primary saveData"><i class="fa fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- List -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Batch List</div>
                        <div class="row ms-0">
                        <div class="form-check col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <input class="form-check-input" type="checkbox" value="Y" id="trashed_file"
                                name="trashed_file">
                            <label class="form-check-label" for="trashed_file">
                                View Trashed
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="batchTable" class="table table-bordered text-nowrap w-100">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Batch No</th>
                                    <th>Expiry Date</th>
                                    <th>Quantity</th>
                                    <th>Purchase Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Filled by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
    

             var categoryTable;
        $(document).ready(function() {
            categoryTable = $('#batchTable').DataTable({
                "sPaginationType": "full_numbers",
                "bSearchable": false,
                "lengthMenu": [
                    [5, 10, 15, 20, 25, -1],
                    [5, 10, 15, 20, 25, "All"]
                ],
                'iDisplayLength': 15,
                "sDom": 'ltipr',
                "bAutoWidth": false,
                "aaSorting": [
                    [0, 'desc']
                ],
                "bSort": false,
                "bProcessing": true,
                "bServerSide": true,
                "oLanguage": {
                    "sEmptyTable": "<p class='no_data_message'>No data available.</p>"
                },
                "aoColumnDefs": [{
                    "bSortable": false,
                    "aTargets": [1]
                }],
                "aoColumns": [{
                        "data": "sno"
                    },
                    {
                        "data": "batch_no"
                    },
                    {
                        "data": "expiry_date"
                    },
                    {
                        "data": "quantity"
                    },
                    {
                        "data": "purchase_price"
                    },
                    {
                        "data": "action"
                    },
                ],
                "ajax": {
                    "url": "{{route('batch.list')}}",
                    "type": "POST",
                    "data": function(d) {
                        var type = $('#trashed_file').is(':checked') == true ? 'trashed' :
                            'nottrashed';
                        d.type = type;
                        d.product_id = $('#product_id').val();

                    }
                },
                "initComplete": function() {
                    // Ensure text input fields in the header for specific columns with placeholders
                    this.api().columns([1]).every(function() {
                        var column = this;
                        var input = document.createElement("input");
                        var columnName = column.header().innerText.trim();
                        // Append input field to the header, set placeholder, and apply CSS styling
                        $(input).appendTo($(column.header()).empty())
                            .attr('placeholder', columnName).css('width',
                                '100%') // Set width to 100%
                            .addClass(
                                'search-input-highlight') // Add a CSS class for highlighting
                            .on('keyup change', function() {
                                column.search(this.value).draw();
                            });
                    });

                    // this.api().columns([2]).every(function() {
                    //     var column = this;
                    //     var input = document.createElement("input");
                    //     var columnName = column.header().innerText.trim();
                    //     // Append input field to the header, set placeholder, and apply CSS styling
                    //     $(input).appendTo($(column.header()).empty())
                    //         .attr('placeholder', columnName).css('width',
                    //             '100%') // Set width to 100%
                    //         .addClass(
                    //             'search-input-highlight') // Add a CSS class for highlighting
                    //         .on('keyup change', function() {
                    //             column.search(this.value).draw();
                    //         });
                    // });
                }
            });
             $(document).off('click', '.saveData');
            $(document).on('click', '.saveData', function() {
                    showLoader();
                    $('#batchForm').ajaxSubmit({
                        success: function(response) {
                            if (response) {
                                if (response.type === 'success') {
                                    $('.saveData').html('<i class="fa fa-save"></i> Save');
                                    showNotification(response.message, 'success');
                                    hideLoader();
                                    categoryTable.draw();
                                    $('#batchForm')[0].reset();
                                    $('#id').val('');
                                } else {
                                    showNotification(response.message, 'error');
                                    hideLoader();
                                }
                            } else {
                                hideLoader();
                            }
                        },
                        error: function(xhr) {
                            hideLoader();
                            var response = xhr.responseJSON;
                            showNotification(response && response.message ? response.message :
                                'An error occurred', 'error');
                        }
                    });
                
            });
        });

          // update category
            $(document).off('click', '.editCategory');
            $(document).on('click', '.editCategory', function() {
                var id = $(this).data('id');
                var batch_no = $(this).data('batch_no');
                var expiry_date = $(this).data('expiry_date');
                var quantity = $(this).data('quantity');
                var purchase_price = $(this).data('purchase_price');
                $('#batchForm input[name = "id"]').val(id);
                $('#batchForm input[name = "batch_no"]').val(batch_no);
                $('#batchForm input[name = "quantity"]').val(quantity);
                $('#batchForm input[name = "expiry_date"]').val(expiry_date);
                $('#batchForm input[name = "purchase_price"]').val(purchase_price);
                $('.saveData').html('<i class="fa fa-save"></i> Update');
            });

            
            // view trashed items-start
            $('#trashed_file').off('change');
            $('#trashed_file').on('change', function(e) {
                categoryTable.draw();
            });
            // view trashed items-ends

              // Delete Category
            $(document).on('click', '.deletecategory', function(e) {
                e.preventDefault();

                var type = $('#trashed_file').is(':checked') == true ? 'trashed' :
                    'nottrashed';
                Swal.fire({
                    title: type === "nottrashed" ? "Are you sure you want to delete this item?" :
                        "Are you sure you want to delete permanently  this item?",
                    text: "You won't be able to revert it!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DB1F48",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        var id = $(this).data('id');
                        var data = {
                            id: id,
                            type: type,
                        };
                        var url = "{{route('batch.delete')}}";
                        $.post(url, data, function(response) {

                            if (response) {
                                showNotification(response.message, response.type);
                                if (response.type === 'success') {
                                    categoryTable.draw();
                                    $('#categoryForm')[0].reset();
                                    $('#id').val('');
                                }
                            }
                        });
                    }
                });
            });

            // Restore category
            $(document).off('click', '.restoreCategory');
            $(document).on('click', '.restoreCategory', function() {
                Swal.fire({
                    title: "Are you sure you want to restore Category?",
                    text: "This will restore the Category.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#28a745",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Restore it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        showLoader();
                        var id = $(this).data('id');
                        var data = {
                            id: id,
                            type: 'restore'
                        };
                        var url = "{{route('batch.restore')}}";
                        $.post(url, data, function(response) {
                            if (response) {
                                if (response.type === 'success') {
                                    showNotification(response.message, 'success');
                                    categoryTable.draw();
                                    hideLoader();
                                } else {
                                    showNotification(response.message, 'error');
                                    hideLoader();
                                }
                            }
                        });
                    }
                });
            });



</script>
@endsection

