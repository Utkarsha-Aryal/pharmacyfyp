@extends('layouts.main')

@section('title')
    Unit
@endsection

@section('main-content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Unit</h5>
        </div>
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="{{ route('admin.export.unit') }}" class="btn btn-excel">
                <i class="fa-solid fa-file-excel"></i> Excel
            </a>
            <a href="{{ route('admin.export.unit-pdf') }}" class="btn btn-pdf">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            <button type="button" class="btn btn-primary addUnitBtn">
                <i class="fa fa-plus"></i> Add Unit
            </button>
        </div>
    </div>

    <div class="modal fade" id="unitModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('admin.unit.save')}}" method="POST" id="unitForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Unit Form</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gy-4">
                            <div class="col-12">
                                <input type="hidden" name="id" value="" id="id">
                                <label for="unit_name" class="form-label">Unit Name <span class="required-field">*</span></label>
                                <input type="text" class="form-control" id="unit_name" placeholder="Enter unit name..." name="unit_name">
                            </div>
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" placeholder="Enter description..." name="description"></textarea>
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

    <!-- Start::row-1 -->
    <div class="row">
        <div class="col-xl-12">
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
                                View Deleted
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="unitTable" class="table table-bordered text-nowrap w-100 mt-3">
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
    <!--End::row-1 -->
@endsection

@section('script')
    <script>
        var unitTable;
        $(document).ready(function() {
            var unitModalElement = document.getElementById('unitModal');
            var unitModal = unitModalElement ? new bootstrap.Modal(unitModalElement) : null;

            function resetUnitForm() {
                $('#unitForm')[0].reset();
                $('#id').val('');
                $('.saveData').html('<i class="fa fa-save"></i> Save');
            }

            $(document).on('click', '.addUnitBtn', function() {
                resetUnitForm();
                if (unitModal) {
                    unitModal.show();
                }
            });

            unitTable = window.initServerSideDataTable({
                selector: '#unitTable',
                pageLength: 15,
                sort: false,
                searchColumns: [1],
                columnDefs: [{
                    bSortable: false,
                    aTargets: [1]
                }],
                columns: [
                    { data: "sno" },
                    { data: "unit_name" },
                    { data: "description" },
                    { data: "added_date" },
                    { data: "action" },
                ],
                ajaxUrl: "{{ route('admin.unit.list') }}",
                ajaxData: function(d) {
                    d.type = $('#trashed_file').is(':checked') == true ? 'trashed' : 'nottrashed';
                }
            });

            $(document).on('hidden.bs.modal', '#unitModal', function() {
                resetUnitForm();
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
                                    showNotification(response.message, 'success');
                                    hideLoader();
                                    unitTable.draw();
                                    if (unitModal) {
                                        unitModal.hide();
                                    } else {
                                        resetUnitForm();
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
                if (unitModal) {
                    unitModal.show();
                }
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
