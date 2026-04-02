@extends('layouts.main')

@section('title')
    Category
@endsection

@section('main-content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Category</h5>
        </div>
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="{{ route('admin.export.category') }}" class="btn btn-excel">
                <i class="fa-solid fa-file-excel"></i> Excel
            </a>
            <a href="{{ route('admin.export.category-pdf') }}" class="btn btn-pdf">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryImportModal">
                <i class="fa-solid fa-upload"></i> Import
            </button>
            <button type="button" class="btn btn-primary addCategoryBtn">
                <i class="fa fa-plus"></i> Add Category
            </button>
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

    <div class="modal fade" id="categoryModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('admin.category.save')}}" method="POST" id="categoryForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Category Form</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gy-4">
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6">
                                <input type="hidden" name="id" value="" id="id">
                                <label for="name" class="form-label">Category Name <span class="required-field">*</span></label>
                                <input type="text" class="form-control" id="name" placeholder="Enter name..." name="name">
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6">
                                <label for="order_number" class="form-label">Display Order <span class="required-field">*</span></label>
                                <input type="number" class="form-control" id="order_number" placeholder="1, 2, 3..." name="order_number">
                            </div>
                            <div class="col-12">
                                <label for="photo" class="form-label">Photo</label>
                                <div class="relative" id="edit-image">
                                    <div class="profile-user">
                                        <label for="file_input" class="fe fe-camera profile-edit text-primary absolute"></label>
                                    </div>
                                    <input type="file" class="file_input" id="file_input"
                                        style="position: absolute; clip: rect(0, 0, 0, 0); pointer-events: none;"
                                        accept="image/*" name="image">
                                    <img id="upload-image" src="{{ asset('/images/no-image.jpg') }}" width="160px"
                                        alt="Default Image" class='_image'>
                                </div>
                            </div>
                            <div class="col-12">
                                <p class="p-0 m-0">Accepted Format :<span class="text-muted"> jpg/jpeg/png</span></p>
                                <p class="p-0 m-0">File size :<span class="text-muted"> 512KB </span></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary saveData"><i class="fa fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.imports.categories') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Import Categories</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <a href="{{ route('admin.imports.sample.categories') }}" class="btn btn-outline-primary">
                                <i class="fa-solid fa-download"></i> Download Sample File
                            </a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select CSV or XLSX</label>
                            <input type="file" name="file" class="form-control js-import-preview-input" data-preview-target="#categoryImportPreview" accept=".csv,.xlsx" required>
                        </div>
                        <div class="d-none" id="categoryImportPreview"></div>
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

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Category List
                    </div>
                    <div class="row ms-0">
                        <div class="form-check col-xl-12 col-lg-12 col-md-12 col-sm-12">
                            <input class="form-check-input" type="checkbox" value="Y" id="trashed_file"
                                name="trashed_file">
                            <label class="form-check-label" for="trashed_file">
                                View Deleted
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="categoryTable" class="table table-bordered text-nowrap w-100 mt-3">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Name</th>
                                    <th>Display Order</th>
                                    <th>Image</th>
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
        var categoryTable;
        $(document).ready(function() {
            var categoryModalElement = document.getElementById('categoryModal');
            var categoryModal = categoryModalElement ? new bootstrap.Modal(categoryModalElement) : null;

            function resetCategoryForm() {
                $('#categoryForm')[0].reset();
                $('#id').val('');
                $('._image').attr('src', "{{ asset('/images/no-image.jpg') }}");
                $('.saveData').html('<i class="fa fa-save"></i> Save');
            }

            $(document).on('click', '.addCategoryBtn', function() {
                resetCategoryForm();
                if (categoryModal) {
                    categoryModal.show();
                }
            });

            categoryTable = window.initServerSideDataTable({
                selector: '#categoryTable',
                pageLength: 15,
                sort: false,
                searchColumns: [1],
                columnDefs: [{
                    bSortable: false,
                    aTargets: [1]
                }],
                columns: [{
                        data: 'sno'
                    },
                    {
                        data: 'name'
                    },
                    {
                        data: 'order_number'
                    },
                    {
                        data: 'image'
                    },
                    {
                        data: 'action'
                    },
                ],
                ajaxUrl: "{{ route('admin.category.list') }}",
                ajaxData: function(d) {
                    d.type = $('#trashed_file').is(':checked') == true ? 'trashed' : 'nottrashed';
                }
            });

            // Save testimonial

            //upload image

            $('#file_input').on('change', function(event) {
                var selectedFile = event.target.files[0];
                if (selectedFile) {
                    $('._image').attr('src', URL.createObjectURL(selectedFile));
                }
            });
            //end upload image

            $(document).on('hidden.bs.modal', '#categoryModal', function() {
                resetCategoryForm();
            });

            $('#categoryForm').validate({
                rules: {
                    name: "required",
                    order_number: "required",
                    image: {
                        required: function() {
                            return $('#id').val() === '';
                        }
                    }
                },
                messages: {
                    name: {
                        required: "This field is required."
                    },
                    order_number: {
                        required: "This field is required."
                    },
                    image: {
                        required: "This field is required."
                    },
                },
                highlight: function(element) {
                    $(element).addClass('border-danger')
                },
                unhighlight: function(element) {
                    $(element).removeClass('border-danger')
                },
            });

            $(document).off('click', '.saveData');
            $(document).on('click', '.saveData', function() {
                if ($('#categoryForm').valid()) {
                    showLoader();
                    $('#categoryForm').ajaxSubmit({
                        success: function(response) {
                            if (response) {
                                if (response.type === 'success') {
                                    showNotification(response.message, 'success');
                                    hideLoader();
                                    categoryTable.draw();
                                    if (categoryModal) {
                                        categoryModal.hide();
                                    } else {
                                        resetCategoryForm();
                                    }
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
                }
            });

            // update category
            $(document).off('click', '.editCategory');
            $(document).on('click', '.editCategory', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var order_number = $(this).data('order_number');
                var image = $(this).data('image');
                $('#categoryForm input[name = "id"]').val(id);
                $('#categoryForm input[name = "name"]').val(name);
                $('#categoryForm input[name = "order_number"]').val(order_number);
                $('#categoryForm ._image').attr('src', image);
                $('.saveData').html('<i class="fa fa-save"></i> Update');
                if (categoryModal) {
                    categoryModal.show();
                }
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
                        var url = "{{route('admin.category.delete')}}";
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
                        var url = "{{route('admin.category.restore')}}";
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
        });
    </script>
@endsection
