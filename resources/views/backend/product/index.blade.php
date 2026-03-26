@extends('backend.layouts.main')

@section('title')
    Product
@endsection
@section('main-content')
    <!-- Page Header -->
    <div class="row ms-0">

    </div>
    </div>
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Product</h5>
        </div>
        <div class="d-flex my-xl-auto right-content">
            <div class="pe-1 mb-xl-0 d-flex gap-2">
                <a href="{{ route('admin.export.product') }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <button type="button" class="btn btn-primary addProductBtn"><i class="fa fa-add"></i> Add Product</button>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="productModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                {{-- Content goes here --}}
            </div>
        </div>
    </div>

    <div class="row ">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Product List
                    </div>
                    <div class="d-flex my-xl-auto right-content">
                        <div class="pe-1 mb-xl-0">
                            <input class="form-check-input" type="checkbox" value="Y" id="trashed_file"
                                name="trashed_file">
                            <label class="form-check-label" for="trashed_file">
                                Show Deleted
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="productTable" class="table table-bordered text-nowrap w-100 mt-3">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Stock Qty</th>
                                <th>Order</th>
                                <th>Generic Name</th>
                                <th>Price</th>
                                <th>Image</th>
                                <th>Keywords</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--End::row-1 -->
@endsection

@section('script')
    <script>
        var productTable;
        $(document).ready(function() {
            var productModalElement = document.getElementById('productModal');
            var productModal = productModalElement ? new bootstrap.Modal(productModalElement) : null;

            $('.addProductBtn').on('click', function(e) {
                e.preventDefault();
                var url = '{{route('admin.product.form')}}';
                $.get(url, function(response) {
                    $('#productModal .modal-content').html(response);
                    if (window.initEnhancedSelects) {
                        window.initEnhancedSelects(document.getElementById('productModal'));
                    }
                    if (productModal) {
                        productModal.show();
                    }
                });
            });

            productTable = window.initServerSideDataTable({
                selector: '#productTable',
                pageLength: 15,
                sort: false,
                searchColumns: [1],
                columnDefs: [{
                    bSortable: false,
                    aTargets: [1]
                }],
                columns: [{
                        data: "sno"
                    },
                    {
                        data: "product_name"
                    },
                    {
                        data: "category"
                    },
                    {
                        data: "stock_quantity"
                    },
                    {
                        data: "order_number"
                    },
                    {
                        data: "generic_name"
                    },
                    {
                        data: "display_price"
                    },
                    {
                        data:"image"
                    },
                    {
                        data: "keywords"
                    },
                    {
                        data: "status"
                    },
                    {
                        data: "action"
                    },
                ],
                ajaxUrl: '{{route('admin.product.list')}}',
                ajaxData: function(d) {
                    d.type = $('#trashed_file').is(':checked') == true ? 'trashed' : 'nottrashed';
                }
            });


            // Edit news-start
            $(document).off('click', '.editNews');
            $(document).on('click', '.editNews', function() {
                var id = $(this).data('id');
                var url = '{{route('admin.product.form')}}';
                var data = {
                    id: id
                };
                $.post(url, data, function(response) {
                    $('#productModal .modal-content').html(response);
                    if (window.initEnhancedSelects) {
                        window.initEnhancedSelects(document.getElementById('productModal'));
                    }
                    if (productModal) {
                        productModal.show();
                    }
                });
            });
            //edit news -end

            // view trashed items-start
            $('#trashed_file').off('change');
            $('#trashed_file').on('change', function(e) {
                productTable.draw();
            });
            // view trashed items-ends


            // Delete news
            $(document).off('click', '.deleteNews');
            $(document).on('click', '.deleteNews', function() {

                var type = $('#trashed_file').is(':checked') == true ? 'trashed' :
                    'nottrashed';

                Swal.fire({
                    title: type === "nottrashed" ? "Are you sure you want to delete this item" :
                        "Are you sure you want to delete permanently  this item",
                    text: "You won't be able to revert it!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DB1F48",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        showLoader();
                        var id = $(this).data('id');
                        var data = {
                            id: id,
                            type: type,
                        };
                        var url = '{{route('admin.product.delete')}}';
                        $.post(url, data, function(response) {
                            if (response) {
                                if (response.type === 'success') {
                                    showNotification(response.message, 'success');
                                    productTable.draw();
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

            $(document).off('click', '.restoreProduct');
            $(document).on('click', '.restoreProduct', function() {
                Swal.fire({
                    title: "Are you sure you want to restore Product?",
                    text: "This will restore the Product.",
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
                        var url = '{{route('admin.product.restore')}}';
                        $.post(url, data, function(response) {
                            if (response) {
                                if (response.type === 'success') {
                                    showNotification(response.message, 'success');
                                    productTable.draw();
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

            //View Product
            $(document).off('click', '.viewProduct');
            $(document).on('click', '.viewProduct', function() {
                var id = $(this).data('id');
                var url = '{{route('admin.product.view')}}';
                var data = {
                    id: id
                };
                $.post(url, data, function(response) {
                    $('#productModal .modal-content').html(response);
                    if (window.initEnhancedSelects) {
                        window.initEnhancedSelects(document.getElementById('productModal'));
                    }
                    if (productModal) {
                        productModal.show();
                    }
                });
            });

        });
    </script>
@endsection
