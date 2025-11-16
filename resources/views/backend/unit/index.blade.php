@extends('backend.layouts.main')

@section('title')
    Unit
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
            <h5 class="page-title fs-21 mb-1">Unit</h5>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="unitModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
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
                <form action="{{ route('admin.unit.save')}}" method="POST" id="unitForm" enctype="multipart/form-data">
                    @csrf
                    <div class="card-body">
                        <div class="row gy-4">
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                                <input type="hidden" name="id" value="" id="id">
                                <label for="unit_name" class="form-label">Unit Name <span class="required-field">*</span></label>
                                <input type="text" class="form-control" id="unit_name" placeholder="Enter unit name..."
                                    name="unit_name">
                            </div>
                            <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" placeholder="Enter description..." name="description"></textarea>
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
                        Unit List
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
                                        <table id="unitTable"
                                            class="table table-bordered text-nowrap w-100 dataTable no-footer mt-3"
                                            aria-describedby="datatable-basic_info">
                                            <thead>
                                                <tr>
                                                    <th>S.No</th>
                                                    <th>Unit Name</th>
                                                    <th>Description</th>
                                                    <th>Added At</th>
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
                </div>
            </div>
        </div>
    </div>
    <!--End::row-1 -->
@endsection

@section('script')
    <script>
        var unitTable;
        $(document).ready(function() {
            unitTable = $('#unitTable').DataTable({
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
                    { "data": "unit_name" },
                    { "data": "description" },
                    { "data": "added_date" },
                    { "data": "action" },
                ],
                "ajax": {
                    "url": "{{route('admin.unit.list')}}",
                    "type": "POST",
                    "data": function(d) {
                        var type = $('#trashed_file').is(':checked') == true ? 'trashed' : 'nottrashed';
                        d.type = type;
                    }
                },
                "initComplete": function() {
                    this.api().columns([1]).every(function() {
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

            $('#unitForm').validate({
                rules: {
                    unit_name: "required"
                },
                messages: {
                    unit_name: {
                        required: "This field is required."
                    }
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
                if ($('#unitForm').valid()) {
                    showLoader();
                    $('#unitForm').ajaxSubmit({
                        success: function(response) {
                            if (response) {
                                if (response.type === 'success') {
                                    $('.saveData').html('<i class="fa fa-save"></i> Save');
                                    showNotification(response.message, 'success');
                                    hideLoader();
                                    unitTable.draw();
                                    $('#unitForm')[0].reset();
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
                            showNotification(response && response.message ? response.message : 'An error occurred', 'error');
                        }
                    });
                }
            });

            // update unit
            $(document).off('click', '.editUnit');
            $(document).on('click', '.editUnit', function() {
                var id = $(this).data('id');
                var unit_name = $(this).data('unit_name');
                var description = $(this).data('description');
                $('#unitForm input[name = "id"]').val(id);
                $('#unitForm input[name = "unit_name"]').val(unit_name);
                $('#unitForm textarea[name = "description"]').val(description);
                $('.saveData').html('<i class="fa fa-save"></i> Update');
            });

            // view trashed items
            $('#trashed_file').off('change');
            $('#trashed_file').on('change', function(e) {
                unitTable.draw();
            });

            // Delete Unit
            $(document).on('click', '.deleteUnit', function(e) {
                e.preventDefault();
                var type = $('#trashed_file').is(':checked') == true ? 'trashed' : 'nottrashed';
                Swal.fire({
                    title: type === "nottrashed" ? "Are you sure you want to delete this item?" :
                        "Are you sure you want to delete permanently this item?",
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
                        var url = "{{route('admin.unit.delete')}}";
                        $.post(url, data, function(response) {
                            if (response) {
                                showNotification(response.message, response.type);
                                if (response.type === 'success') {
                                    unitTable.draw();
                                    $('#unitForm')[0].reset();
                                    $('#id').val('');
                                }
                            }
                        });
                    }
                });
            });

            // Restore unit
            $(document).off('click', '.restoreUnit');
            $(document).on('click', '.restoreUnit', function() {
                Swal.fire({
                    title: "Are you sure you want to restore Unit?",
                    text: "This will restore the Unit.",
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
                        var url = "{{route('admin.unit.restore')}}";
                        $.post(url, data, function(response) {
                            if (response) {
                                if (response.type === 'success') {
                                    showNotification(response.message, 'success');
                                    unitTable.draw();
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