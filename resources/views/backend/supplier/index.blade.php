@extends('backend.layouts.main')

@section('title')
    Supplier
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
            <h5 class="page-title fs-21 mb-1">Add Supplier </h5>
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
    <form  id="supplierForm" enctype="multipart/form-data">
        @csrf
        <div class="card-body">
            <div class="row gy-4">

                <input type="hidden" name="id" value="" id="id">

                <div class="col-md-6">
                    <label for="supplier_name" class="form-label">Supplier Name <span class="required-field">*</span></label>
                    <input type="text" class="form-control" name="supplier_name" id="supplier_name" placeholder="Enter supplier name">
                </div>

                <div class="col-md-6">
                    <label for="contact_person" class="form-label">Contact Person</label>
                    <input type="text" class="form-control" name="contact_person" id="contact_person" placeholder="Enter contact person">
                </div>

                <div class="col-md-6">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone_number" id="phone_number" placeholder="Enter phone number">
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="Enter email">
                </div>

                <div class="col-md-6">
                    <label for="pan_number" class="form-label">PAN Number</label>
                    <input type="text" class="form-control" name="pan_number" id="pan_number" placeholder="Enter PAN number">
                </div>

                <div class="col-md-6">
                    <label for="opening_balance" class="form-label">Opening Balance</label>
                    <input type="text" class="form-control" name="opening_balance" id="opening_balance" placeholder="Enter opening balance">
                </div>

                <div class="col-md-6">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control" name="address" id="address" placeholder="Enter address">
                </div>

                <div class="col-md-6">
                    <label for="type" class="form-label">Type</label>
                    <select name="type" id="type" class="form-select">
                        <option value="credit" selected>Credit</option>
                        <option value="debit">Debit</option>
                    </select>
                </div>

            </div>
        </div>

        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary saveData"><i class="fa fa-save"></i> Save</button>
        </div>
    </form>
</div>

        </div>
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Supplier List
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
                                        <table id="supplierTable"
                                            class="table table-bordered text-nowrap w-100 dataTable no-footer mt-3"
                                            aria-describedby="datatable-basic_info">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Name</th>
                                                    <th>Contact Person</th>
                                                    <th>Current Balance</th>
                                                    <th>Phone</th>
                                                    <th>Added Date</th>
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
        var supplierTable;
        $(document).ready(function() {
            supplierTable = $('#supplierTable').DataTable({
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
            "aoColumns": [
                { "data": "sno" },
                { "data": "supplier_name" },
                { "data": "contact_person" },
                { "data": "opening_balance" },
                { "data": "phone_number" },
                { "data": "added_date" },
                { "data": "action" }
            ],
            "ajax": {
                "url": "{{ route('admin.supplier.list') }}",
                "type": "POST",
                "headers": {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                "data": function(d) {
                var type = $('#trashed_file').is(':checked') == true ? 'trashed' : 'nottrashed';
                d.type = type;
                }
            },
            "initComplete": function() {
                // Ensure text input fields in the header for specific columns with placeholders
                this.api().columns([1,2,3,4,5]).every(function() {
                var column = this;
                var input = document.createElement("input");
                var columnName = column.header().innerText.trim();
                $(input).appendTo($(column.header()).empty())
                    .attr('placeholder', columnName).css('width', '100%')
                    .addClass('search-input-highlight')
                    .on('keyup change', function() {
                    column.search(this.value).draw();
                    });
                });
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

          $('#supplierForm').validate({
    rules: {
        supplier_name: {
            required: true
        },
        contact_person: {
            required: true
        },
        phone_number: {
            required: true,
        },
        email: {
            required: true,
            email: true
        },
        pan_number: {
            required: true
        },
        opening_balance: {
            required: true,
            number: true
        },
        address: {
            required: true
        },
        type: {
            required: true
        }
    },
    messages: {
        supplier_name: {
            required: "Supplier name is required."
        },
        contact_person: {
            required: "Contact person is required."
        },
        phone_number: {
            required: "Phone number is required.",
        },
        email: {
            required: "Email is required.",
            email: "Please enter a valid email address."
        },
        pan_number: {
            required: "PAN number is required."
        },
        opening_balance: {
            required: "Opening balance is required.",
            number: "Please enter a valid number."
        },
        address: {
            required: "Address is required."
        },
        type: {
            required: "Please select a type."
        }
    },
    highlight: function (element) {
        $(element).addClass('border-danger');
    },
    unhighlight: function (element) {
        $(element).removeClass('border-danger');
    }
});


            $(document).off('click', '.saveData');
            $(document).on('click', '.saveData', function(e) {
                e.preventDefault();
                if ($('#supplierForm').valid()) {
                    var form = $('#supplierForm')[0];
                    var formData = new FormData(form);
                    $.ajax({
                        url: "{{ route('admin.supplier.save') }}",
                        type: "POST",
                        data: formData,
                        dataType: "json",
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response) {
                                if (response.type === 'success') {
                                    $('.saveData').html('<i class="fa fa-save"></i> Save');
                                    showNotification(response.message, 'success');
                                    hideLoader();
                                    supplierTable.draw();
                                    $('#supplierForm')[0].reset();
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
                $('#supplierForm input[name = "id"]').val(id);
                $('#supplierForm input[name = "name"]').val(name);
                $('#supplierForm textarea[name = "keywords"]').val(keywords);
                $('#supplierForm input[name = "order_number"]').val(order_number);
                $('#supplierForm ._image').attr('src', image);
                $('.saveData').html('<i class="fa fa-save"></i> Update');
            });


            // view trashed items-start
            $('#trashed_file').off('change');
            $('#trashed_file').on('change', function(e) {
                supplierTable.draw();
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
                                    supplierTable.draw();
                                    $('#supplierForm')[0].reset();
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
                                    supplierTable.draw();
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
