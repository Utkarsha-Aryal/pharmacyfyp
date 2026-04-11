@extends('layouts.main')

@section('title')
    Company
@endsection

@section('main-content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div class="my-auto">
            <h5 class="page-title fs-21 mb-1">Company</h5>
        </div>
        <div class="d-flex gap-2 mt-3 mt-md-0">
            <a href="{{ route('admin.export.company') }}" class="btn btn-excel">
                <i class="fa-solid fa-file-excel"></i> Excel
            </a>
            <a href="{{ route('admin.export.company-pdf') }}" class="btn btn-pdf">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </a>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#companyImportModal">
                <i class="fa-solid fa-upload"></i> Import
            </button>
            <button type="button" class="btn btn-primary addCompanyBtn">
                <i class="fa fa-plus"></i> Add Company
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

    <div class="modal fade" id="companyModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('admin.company.save')}}" method="POST" id="companyForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Company Form</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row gy-4">
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6">
                                <input type="hidden" name="id" value="" id="id">
                                <label for="name" class="form-label">Company Name <span class="required-field">*</span></label>
                                <input type="text" class="form-control" id="name" placeholder="Enter company name..." name="name">
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6">
                                <label for="company_type" class="form-label">Company Type <span class="required-field">*</span></label>
                                <select class="form-select js-select2" id="company_type" name="company_type" data-placeholder="Select company type">
                                    <option value="">Select company type</option>
                                    <option value="domestic">Domestic</option>
                                    <option value="foreign">Foreign</option>
                                </select>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6">
                                <label for="default_cc_rate" class="form-label">Default CC Rate (%)</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="default_cc_rate" placeholder="0.00" name="default_cc_rate" value="0">
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

    <div class="modal fade" id="companyImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.imports.companies') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Import Companies</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <a href="{{ route('admin.imports.sample.companies') }}" class="btn btn-outline-primary">
                                <i class="fa-solid fa-download"></i> Download Company Sample File
                            </a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select CSV or XLSX</label>
                            <input type="file" name="file" class="form-control js-import-preview-input" data-preview-target="#companyImportPreview" accept=".csv,.xlsx" required>
                        </div>
                        <div class="d-none" id="companyImportPreview"></div>
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
                        Company List
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
                        <table id="companyTable" class="table table-bordered text-nowrap w-100 mt-3">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Company Name</th>
                                    <th>Type</th>
                                    <th>Default CC Rate</th>
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
        var companyTable;
        $(document).ready(function() {
            var companyModalElement = document.getElementById('companyModal');
            var companyModal = companyModalElement ? new bootstrap.Modal(companyModalElement) : null;

            function resetCompanyForm() {
                $('#companyForm')[0].reset();
                $('#id').val('');
                $('#default_cc_rate').val('0');
                $('.saveData').html('<i class="fa fa-save"></i> Save');
            }

            $(document).on('click', '.addCompanyBtn', function() {
                resetCompanyForm();
                if (companyModal) {
                    companyModal.show();
                }
            });

            companyTable = window.initServerSideDataTable({
                selector: '#companyTable',
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
                        data: 'company_type'
                    },
                    {
                        data: 'default_cc_rate'
                    },
                    {
                        data: 'action'
                    },
                ],
                ajaxUrl: "{{ route('admin.company.list') }}",
                ajaxData: function(d) {
                    d.type = $('#trashed_file').is(':checked') == true ? 'trashed' : 'nottrashed';
                }
            });

            $(document).on('hidden.bs.modal', '#companyModal', function() {
                resetCompanyForm();
            });

            $('#companyForm').validate({
                rules: {
                    name: "required",
                    company_type: "required",
                    default_cc_rate: {
                        number: true,
                        min: 0,
                        max: 100
                    },
                },
                messages: {
                    name: {
                        required: "This field is required."
                    },
                    company_type: {
                        required: "Company type is required."
                    },
                    default_cc_rate: {
                        number: "CC rate must be numeric.",
                        min: "CC rate cannot be negative.",
                        max: "CC rate cannot exceed 100.",
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
                if ($('#companyForm').valid()) {
                    showLoader();
                    $('#companyForm').ajaxSubmit({
                        success: function(response) {
                            if (response) {
                                if (response.type === 'success') {
                                    showNotification(response.message, 'success');
                                    hideLoader();
                                    companyTable.draw();
                                    if (companyModal) {
                                        companyModal.hide();
                                    } else {
                                        resetCompanyForm();
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

            // update company
            $(document).off('click', '.editCompany');
            $(document).on('click', '.editCompany', function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var companyType = $(this).data('company_type');
                var defaultCcRate = $(this).data('default_cc_rate');
                $('#companyForm input[name = "id"]').val(id);
                $('#companyForm input[name = "name"]').val(name);
                $('#companyForm select[name = "company_type"]').val(companyType).trigger('change');
                $('#companyForm input[name = "default_cc_rate"]').val(defaultCcRate);
                $('.saveData').html('<i class="fa fa-save"></i> Update');
                if (companyModal) {
                    companyModal.show();
                }
            });


            // view trashed items-start
            $('#trashed_file').off('change');
            $('#trashed_file').on('change', function(e) {
                companyTable.draw();
            });
            // view trashed items-ends


            // Delete company
            $(document).on('click', '.deleteCompany', function(e) {
                e.preventDefault();

                var type = $('#trashed_file').is(':checked') == true ? 'trashed' :
                    'nottrashed';
                Swal.fire({
                    title: type === "nottrashed" ? "Are you sure you want to delete this company?" :
                        "Are you sure you want to delete this company permanently?",
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
                        var url = "{{route('admin.company.delete')}}";
                        $.post(url, data, function(response) {

                            if (response) {
                                showNotification(response.message, response.type);
                                if (response.type === 'success') {
                                    companyTable.draw();
                                    $('#companyForm')[0].reset();
                                    $('#id').val('');
                                }
                            }
                        });
                    }
                });
            });

            // Restore company
            $(document).off('click', '.restoreCompany');
            $(document).on('click', '.restoreCompany', function() {
                Swal.fire({
                    title: "Are you sure you want to restore Company?",
                    text: "This will restore the Company.",
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
                        var url = "{{route('admin.company.restore')}}";
                        $.post(url, data, function(response) {
                            if (response) {
                                if (response.type === 'success') {
                                    showNotification(response.message, 'success');
                                    companyTable.draw();
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
