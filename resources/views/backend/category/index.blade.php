@extends('backend.layouts.main')

@section('title')
    Category
@endsection

@section('styles')
    <style>
        .iconpicker-popover.popover.bottom {
            opacity: 1;
        }

        label#file_input-error {
            position: absolute;
            top: 8.3rem !important;
            left: 1rem;
        }
    </style>
@endsection

@section('main-content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Category</h5>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="categoryModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                {{-- Content goes here --}}
            </div>
        </div>
    </div>
    <!-- Page Header Close -->

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-4">
            <div class="card custom-card">
                <form action="{{ route('category.save')}}" method="POST" id="categoryForm" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="row gy-4">

                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6">
                                <input type="hidden" name="id" value="" id="id">
                                <label for="name" class="form-label">Category Name <span
                                        class="required-field">*</span></label>
                                <input type="text" class="form-control" id="name" placeholder="Enter name..."
                                    name="name">
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6">
                                <label for="order_number" class="form-label">Order <span
                                        class="required-field">*</span></label>
                                <input type="number" class="form-control" id="order_number" placeholder="Enter order..."
                                    name="order_number">
                            </div>

                            <div class="row mt-2">
                                <label for="photo" class="form-label">Photo</label>
                                <div class="col-10 relative" id="edit-image">

                                    <div class="profile-user">
                                        <label for="file_input"
                                            class="fe fe-camera profile-edit text-primary absolute"></label>
                                    </div>
                                    <input type="file" class="file_input" id="file_input"
                                        style="position: absolute; clip: rect(0, 0, 0, 0); pointer-events: none;"
                                        accept="image/*" name="image">
                                    <img id="upload-image" src="{{ asset('/images/no-image.jpg') }}" width="160px"
                                        alt="Default Image" class='_image'>
                                </div>
                            </div>
                            <div class="row mt-4 ms-1">
                                <p class="p-0 m-0">Accepted Format :<span class="text-muted"> jpg/jpeg/png</span></p>
                                <p class="p-0 m-0">File size :<span class="text-muted"> 512KB </span></p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="button" class="btn btn-primary saveData"><i class="fa fa-save"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-xl-8">
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
                                View Trashed
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <div id="datatable-basic_wrapper" class="dataTables_wrapper dt-bootstrap5 no-footer">
                            <div class="row">
                                <div class="col-sm-12 col-md-12 mb-3">
                                    <div class="dataTables_length" id="datatable-basic_length">
                                        <table id="categoryTable"
                                            class="table table-bordered text-nowrap w-100 dataTable no-footer mt-3"
                                            aria-describedby="datatable-basic_info">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Name</th>
                                                    <th>Order</th>
                                                    <th>Image</th>
                                                    <th>Action</th>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
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
            categoryTable = $('#categoryTable').DataTable({
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
                        "data": "name"
                    },
                    {
                        "data": "order_number"
                    },
                    {
                        "data": "image"
                    },

                    {
                        "data": "action"
                    },
                ],
                "ajax": {
                    "url": "{{route('category.list')}}",
                    "type": "POST",
                    "data": function(d) {
                        var type = $('#trashed_file').is(':checked') == true ? 'trashed' :
                            'nottrashed';
                        d.type = type;
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

            // Save testimonial

            //upload image

            $('#file_input').on('change', function(event) {
                var selectedFile = event.target.files[0];
                if (selectedFile) {
                    $('._image').attr('src', URL.createObjectURL(selectedFile));
                }
            });
            //end upload image

            $('#categoryForm').validate({
                rules: {
                    name: "required",
                    order_number: "required",
                    rating: "required",
                    designation: "required",
                    image: {
                        required: function() {
                            return $('#id').val() === '';
                        }
                    }
                },
                message: {
                    name: {
                        required: "This field is required."
                    },
                    designation: {
                        required: "This field is required."
                    },
                    order_number: {
                        required: "This field is required."
                    },
                    rating: {
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
                                    $('.saveData').html('<i class="fa fa-save"></i> Save');
                                    showNotification(response.message, 'success');
                                    hideLoader();
                                    categoryTable.draw();
                                    $('#categoryForm')[0].reset();
                                    $('#id').val('');
                                    $('._image').attr('src', "{{ asset('/images/no-image.jpg') }}");
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
                var keywords = $(this).data('keywords');
                var designation = $(this).data('designation');
                var order_number = $(this).data('order_number');
                var image = $(this).data('image');
                $('#categoryForm input[name = "id"]').val(id);
                $('#categoryForm input[name = "name"]').val(name);
                $('#categoryForm textarea[name = "keywords"]').val(keywords);
                $('#categoryForm input[name = "order_number"]').val(order_number);
                $('#categoryForm ._image').attr('src', image);
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
                        var url = "{{route('category.delete')}}";
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
                        var url = "{{route('category.restore')}}";
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
