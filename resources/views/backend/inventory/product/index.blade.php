@extends('backend.layouts.main')

@section('title')
    Inventory Products
@endsection

@section('main-content')
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Inventory Products</h5>
        </div>
        <div class="d-flex my-xl-auto right-content">
            <div class="pe-1 mb-xl-0 d-flex gap-2">
                <a href="{{ route('admin.export.inventory-products') }}" class="btn btn-outline-primary">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <button type="button" class="btn btn-primary addProductBtn"><i class="fa fa-add"></i> Add Product</button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content"></div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">Inventory Product List</div>
                    <div class="d-flex my-xl-auto right-content gap-2">
                        <form class="d-flex gap-2 align-items-end" method="GET">
                            <select name="category_id" class="form-select js-select2" data-placeholder="All categories" data-allow-clear="1" style="min-width: 220px;">
                                <option value="">All Category</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-primary btn-sm icon-only-btn" type="submit" title="Filter" aria-label="Filter">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <a href="{{ route('admin.inventory.products.index') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset" aria-label="Reset">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="productTable" class="table table-bordered text-nowrap w-100 mt-3">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Formulation</th>
                                    <th>Unit</th>
                                    <th>Reorder Level</th>
                                    <th>Current Stock</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var productTable;
        $(document).ready(function() {
            var productModalElement = document.getElementById('productModal');
            var productModal = productModalElement ? new bootstrap.Modal(productModalElement) : null;

            $('.addProductBtn').on('click', function(e) {
                e.preventDefault();
                var url = '{{ route('admin.product.form') }}';
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
                columns: [
                    { data: 'sno' },
                    { data: 'product_name' },
                    { data: 'category' },
                    { data: 'formulation' },
                    { data: 'unit' },
                    { data: 'reorder_level' },
                    { data: 'stock_quantity' },
                    { data: 'status' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.inventory.products.list') }}',
                ajaxData: function(d) {
                    d.category_id = '{{ request('category_id') }}';
                    d.type = $('#trashed_file').is(':checked') ? 'trashed' : 'nottrashed';
                }
            });

            $(document).off('click', '.editNews');
            $(document).on('click', '.editNews', function() {
                var id = $(this).data('id');
                var url = '{{ route('admin.product.form') }}';
                $.post(url, { id: id }, function(response) {
                    $('#productModal .modal-content').html(response);
                    if (window.initEnhancedSelects) {
                        window.initEnhancedSelects(document.getElementById('productModal'));
                    }
                    if (productModal) {
                        productModal.show();
                    }
                });
            });

            $('#trashed_file').off('change').on('change', function() {
                productTable.draw();
            });

            $(document).off('click', '.deleteNews');
            $(document).on('click', '.deleteNews', function() {
                var type = $('#trashed_file').is(':checked') ? 'trashed' : 'nottrashed';
                Swal.fire({
                    title: type === 'nottrashed' ? 'Are you sure you want to delete this item' : 'Are you sure you want to delete permanently this item',
                    text: "You won't be able to revert it!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DB1F48',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        showLoader();
                        $.post('{{ route('admin.product.delete') }}', {
                            id: $(this).data('id'),
                            type: type,
                        }, function(response) {
                            showNotification(response.message, response.type);
                            hideLoader();
                            productTable.draw();
                        }).fail(function(xhr) {
                            var response = xhr.responseJSON || {};
                            showNotification(response.message || 'Could not delete record.', 'error');
                            hideLoader();
                        });
                    }
                });
            });
        });
    </script>
@endsection
