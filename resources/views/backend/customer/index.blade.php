@extends('backend.layouts.main')

@section('title')
    Party Management
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Party Management</h5>
                <p class="mb-0 text-muted">Customer and institution master with ledger access and quick status control.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.customers') }}" class="btn btn-outline-primary btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <button type="button" class="btn btn-primary addCustomerBtn">
                    <i class="fa fa-plus"></i> Add Party
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">Total Parties</p>
                        <h3 class="summary-card-value">{{ $partySummary['total'] }}</h3>
                        <span class="summary-card-note">All customer and institution records.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Active Parties</p>
                        <h3 class="summary-card-value">{{ $partySummary['active'] }}</h3>
                        <span class="summary-card-note">Visible for sales and billing.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Customers</p>
                        <h3 class="summary-card-value">{{ $partySummary['customers'] }}</h3>
                        <span class="summary-card-note">Retail and regular buyers.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Institutions</p>
                        <h3 class="summary-card-value">{{ $partySummary['institutions'] }}</h3>
                        <span class="summary-card-note">Hospitals, clinics and schools.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="customerModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form action="{{ route('admin.customers.save') }}" method="POST" id="customerForm" class="js-ajax-form" data-reload-table="#customerTable">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Add Party</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="customer_id">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Party Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="Kathmandu Clinic Pvt. Ltd." required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Party Type</label>
                                    <select name="party_type" class="form-select" required>
                                        <option value="customer">Customer</option>
                                        <option value="institution">Institution</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control" placeholder="Person name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="party@example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Credit Limit</label>
                                    <input type="number" step="0.01" min="0" name="credit_limit" class="form-control" value="0">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control" rows="3" placeholder="Short address note"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Opening Balance</label>
                                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="customerSaveBtn">
                                <i class="fa fa-save"></i> Save Party
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header justify-content-between">
                <div class="card-title">Party List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="customerTable" class="table table-bordered align-middle w-100" data-list-url="{{ route('admin.customers.list') }}">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Contact Person</th>
                                <th>Phone</th>
                                <th>Credit Limit</th>
                                <th>Balance</th>
                                <th>Aging</th>
                                <th>Status</th>
                                <th style="width: 180px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            var customerModalElement = document.getElementById('customerModal');
            var customerModal = customerModalElement ? new bootstrap.Modal(customerModalElement) : null;
            var customerForm = $('#customerForm');
            var customerSaveBtn = $('#customerSaveBtn');

            function resetCustomerForm() {
                customerForm[0].reset();
                $('#customer_id').val('');
                customerSaveBtn.html('<i class="fa fa-save"></i> Save Party');
                customerForm.find('select[name="party_type"]').val('customer');
            }

            $(document).on('click', '.addCustomerBtn', function () {
                resetCustomerForm();
                if (customerModal) {
                    customerModal.show();
                }
            });

            $(document).on('click', '.editCustomer', function () {
                $('#customer_id').val($(this).data('id'));
                customerForm.find('[name="name"]').val($(this).data('name'));
                customerForm.find('[name="party_type"]').val($(this).data('party_type'));
                customerForm.find('[name="contact_person"]').val($(this).data('contact_person'));
                customerForm.find('[name="phone"]').val($(this).data('phone'));
                customerForm.find('[name="email"]').val($(this).data('email'));
                customerForm.find('[name="address"]').val($(this).data('address'));
                customerForm.find('[name="credit_limit"]').val($(this).data('credit_limit'));
                customerForm.find('[name="opening_balance"]').val($(this).data('opening_balance'));
                customerSaveBtn.html('<i class="fa fa-save"></i> Update Party');
                if (customerModal) {
                    customerModal.show();
                }
            });

            $(document).on('hidden.bs.modal', '#customerModal', function () {
                resetCustomerForm();
            });

            window.customerTable = window.initServerSideDataTable({
                selector: '#customerTable',
                pageLength: 10,
                sort: false,
                columns: [
                    { data: 'sno' },
                    { data: 'name' },
                    { data: 'party_type' },
                    { data: 'contact_person' },
                    { data: 'phone' },
                    { data: 'credit_limit' },
                    { data: 'balance' },
                    { data: 'aging' },
                    { data: 'status' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.customers.list') }}'
            });
        });
    </script>
@endsection
