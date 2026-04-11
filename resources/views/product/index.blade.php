@extends('layouts.main')

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
                <a href="{{ route('admin.export.product') }}" class="btn btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.product-pdf') }}" class="btn btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#productImportModal">
                    <i class="fa-solid fa-upload"></i> Import
                </button>
                <button type="button" class="btn btn-primary addProductBtn"><i class="fa fa-add"></i> Add Product</button>
            </div>
        </div>
    </div>
    @if (session('import_summary'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            Imported: {{ session('import_summary.imported', 0) }},
            Updated: {{ session('import_summary.updated', 0) }},
            Failed: {{ session('import_summary.failed', 0) }}
            @if (!empty(session('import_summary.errors')))
                <div class="small mt-2">{{ implode(' | ', array_slice(session('import_summary.errors'), 0, 5)) }}</div>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <!-- Modal -->
    <div class="modal fade" id="productModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                {{-- Content goes here --}}
            </div>
        </div>
    </div>

    <div class="modal fade" id="productImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.imports.products') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Import Products</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <a href="{{ route('admin.imports.sample.products') }}" class="btn btn-outline-primary">
                                <i class="fa-solid fa-download"></i> Download Sample File
                            </a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select CSV or XLSX</label>
                            <input type="file" name="file" class="form-control js-import-preview-input" data-preview-target="#productImportPreview" accept=".csv,.xlsx" required>
                        </div>
                        <div class="d-none" id="productImportPreview"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-upload"></i> Import
                        </button>
                    </div>
                </form>
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
                    <div class="d-flex my-xl-auto right-content gap-2">
                        <form class="d-flex gap-2 align-items-end" method="GET">
                            <select name="company_id" id="product_company_id" class="form-select js-select2" data-placeholder="All companies" data-allow-clear="1" style="min-width: 220px;">
                                <option value="">All Companies</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected(request('company_id') == $company->id)>{{ $company->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-primary btn-sm icon-only-btn" type="submit" title="Filter" aria-label="Filter">
                                <i class="fa-solid fa-filter"></i>
                            </button>
                            <a href="{{ route('admin.product') }}" class="btn btn-outline-secondary btn-sm icon-only-btn" title="Reset" aria-label="Reset">
                                <i class="fa-solid fa-rotate-right"></i>
                            </a>
                        </form>
                        <div class="pe-1 mb-xl-0 d-flex align-items-center">
                            <input class="form-check-input" type="checkbox" value="Y" id="trashed_file"
                                name="trashed_file">
                            <label class="form-check-label ms-2" for="trashed_file">
                                View Deleted
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
                                <th>Company</th>
                                <th>Formulation</th>
                                <th>Unit</th>
                                <th>Reorder Level</th>
                                <th>Stock Qty</th>
                                <th>MRP</th>
                                <th>CC Rate</th>
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

    @include('partials.quick-create-modals', [
        'showQuickDropdownOption' => auth()->user()->can('settings.manage'),
        'showQuickUnit' => auth()->user()->can('inventory.unit'),
        'units' => $units,
        'companies' => $companies,
        'formulations' => $formulations,
        'productStatuses' => $productStatuses,
        'saleUnits' => $saleUnits,
        'purchaseUnits' => $purchaseUnits,
    ])
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
                    if (window.syncCsrfInputs) {
                        window.syncCsrfInputs(document.getElementById('productModal'));
                    }
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
                        data: "company"
                    },
                    {
                        data: "formulation"
                    },
                    {
                        data: "unit"
                    },
                    {
                        data: "reorder_level"
                    },
                    {
                        data: "stock_quantity"
                    },
                    {
                        data: "mrp"
                    },
                    {
                        data: "cc_rate"
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
                    d.company_id = $('#product_company_id').val() || '';
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
                    if (window.syncCsrfInputs) {
                        window.syncCsrfInputs(document.getElementById('productModal'));
                    }
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
                    if (window.syncCsrfInputs) {
                        window.syncCsrfInputs(document.getElementById('productModal'));
                    }
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
