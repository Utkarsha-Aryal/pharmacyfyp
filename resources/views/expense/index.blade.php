@extends('layouts.main')

@section('title')
    Expense Tracking
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Expense Tracking</h5>
                <p class="mb-0 text-muted">Quick admin expense entry with reusable category and payment mode options.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.export.expenses') }}" class="btn btn-outline-primary btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <button type="button" class="btn btn-primary addExpenseBtn">
                    <i class="fa fa-plus"></i> Add Expense
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-blue">
                    <div class="card-body">
                        <p class="summary-card-label">This Month</p>
                        <h3 class="summary-card-value">{{ money_value($summary['this_month']) }}</h3>
                        <span class="summary-card-note">Total expense in current month.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-green">
                    <div class="card-body">
                        <p class="summary-card-label">Cash</p>
                        <h3 class="summary-card-value">{{ money_value($summary['cash']) }}</h3>
                        <span class="summary-card-note">Paid through cash account.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-orange">
                    <div class="card-body">
                        <p class="summary-card-label">Bank</p>
                        <h3 class="summary-card-value">{{ money_value($summary['bank']) }}</h3>
                        <span class="summary-card-note">Paid through bank account.</span>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card custom-card summary-card summary-card-red">
                    <div class="card-body">
                        <p class="summary-card-label">Total</p>
                        <h3 class="summary-card-value">{{ money_value($summary['total']) }}</h3>
                        <span class="summary-card-note">All expense records together.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="expenseModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form action="{{ route('admin.expenses.save') }}" method="POST" id="expenseForm" class="js-ajax-form" data-reload-table="#expenseTable">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Add Expense</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" id="expense_id">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Expense Date</label>
                                    <input type="date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex justify-content-between align-items-center">
                                        <span>Expense Category</span>
                                        @can('settings.manage')
                                            <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickDropdownOptionModal" data-quick-target-select="#expenseCategoryId" data-dropdown-alias="expense_category" data-dropdown-label="Expense Category" data-dropdown-supports-data="0" data-bs-toggle="tooltip" title="Add expense category">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        @endcan
                                    </label>
                                    <select name="expense_category_id" id="expenseCategoryId" class="form-select js-select2" data-placeholder="Select category" data-dropdown-alias="expense_category" required>
                                        <option value="">Select category</option>
                                        @foreach ($expenseCategories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}{{ $category->status ? '' : ' (Inactive)' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-flex justify-content-between align-items-center">
                                        <span>Payment Mode</span>
                                        @can('settings.manage')
                                            <button type="button" class="btn btn-success btn-sm quick-add-inline-btn js-open-quick-create" data-quick-modal="#quickDropdownOptionModal" data-quick-target-select="#expensePaymentMode" data-dropdown-alias="payment_mode" data-dropdown-label="Payment Mode" data-dropdown-supports-data="1" data-bs-toggle="tooltip" title="Add payment mode">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        @endcan
                                    </label>
                                    <select name="payment_mode_id" id="expensePaymentMode" class="form-select js-select2" data-placeholder="Select payment mode" data-dropdown-alias="payment_mode" required>
                                        <option value="">Select payment mode</option>
                                        @foreach ($paymentModes as $mode)
                                            <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vendor Name</label>
                                    <input type="text" name="vendor_name" class="form-control" placeholder="Optional vendor">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Amount</label>
                                    <input type="number" step="0.01" min="0" name="amount" class="form-control" value="0" required>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="3" placeholder="Small note for the expense"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary" id="expenseSaveBtn">
                                <i class="fa fa-save"></i> Save Expense
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-body border-bottom">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Expense Category</label>
                        <select id="expenseCategoryFilter" class="form-select js-select2" data-placeholder="All category" data-allow-clear="1">
                            <option value="">All</option>
                            @foreach ($expenseCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}{{ $category->status ? '' : ' (Inactive)' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Mode</label>
                        <select id="expensePaymentFilter" class="form-select js-select2" data-placeholder="All payment" data-allow-clear="1">
                            <option value="">All</option>
                            @foreach ($paymentModes as $mode)
                                <option value="{{ $mode->id }}">{{ $mode->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex justify-content-md-end">
                        <button type="button" class="btn btn-outline-secondary mt-md-4" id="resetExpenseFilters">
                            <i class="fa-solid fa-rotate-right me-1"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-header justify-content-between">
                <div class="card-title">Expense List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="expenseTable" class="table table-bordered align-middle w-100" data-list-url="{{ route('admin.expenses.list') }}">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Date</th>
                                <th>Expense Category</th>
                                <th>Vendor</th>
                                <th>Payment Mode</th>
                                <th>Amount</th>
                                <th>Notes</th>
                                <th>Created By</th>
                                <th style="width: 120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        @include('partials.quick-create-modals', [
            'showQuickExpenseCategory' => true,
            'showQuickPaymentMode' => auth()->user()->can('settings.manage'),
            'showQuickCustomer' => false,
            'showQuickSupplier' => false,
            'showQuickProduct' => false,
            'showQuickUnit' => false,
        ])
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            var expenseModalElement = document.getElementById('expenseModal');
            var expenseModal = expenseModalElement ? new bootstrap.Modal(expenseModalElement) : null;
            var expenseForm = $('#expenseForm');
            var expenseSaveBtn = $('#expenseSaveBtn');

            function resetExpenseForm() {
                expenseForm[0].reset();
                $('#expense_id').val('');
                $('#expenseCategoryId').val('').trigger('change');
                expenseSaveBtn.html('<i class="fa fa-save"></i> Save Expense');
            }

            function setExpenseCategoryValue(categoryId, categoryName) {
                var $select = $('#expenseCategoryId');

                if (categoryId) {
                    $select.val(String(categoryId)).trigger('change');
                    return;
                }

                if (categoryName) {
                    var matchedOption = $select.find('option').filter(function () {
                        var optionText = String($(this).text() || '').replace(/\s*\(Inactive\)\s*$/i, '').trim();
                        return optionText === String(categoryName).trim();
                    }).first();

                    if (matchedOption.length) {
                        $select.val(matchedOption.val()).trigger('change');
                        return;
                    }
                }

                $select.val('').trigger('change');
            }

            $(document).on('click', '.addExpenseBtn', function () {
                resetExpenseForm();
                if (expenseModal) {
                    expenseModal.show();
                }
            });

            $(document).on('click', '.editExpense', function () {
                $('#expense_id').val($(this).data('id'));
                expenseForm.find('[name="expense_date"]').val($(this).data('expense-date'));
                setExpenseCategoryValue($(this).data('expense-category-id'), $(this).data('expense-category-name'));
                expenseForm.find('[name="vendor_name"]').val($(this).data('vendor-name'));
                expenseForm.find('[name="payment_mode_id"]').val($(this).data('payment-mode-id')).trigger('change');
                expenseForm.find('[name="amount"]').val($(this).data('amount'));
                expenseForm.find('[name="notes"]').val($(this).data('notes'));
                expenseSaveBtn.html('<i class="fa fa-save"></i> Update Expense');
                if (expenseModal) {
                    expenseModal.show();
                }
            });

            $(document).on('hidden.bs.modal', '#expenseModal', function () {
                resetExpenseForm();
            });

            window.expenseTable = window.initServerSideDataTable({
                selector: '#expenseTable',
                pageLength: 10,
                sort: false,
                searchable: true,
                columns: [
                    { data: 'sno' },
                    { data: 'date' },
                    { data: 'category' },
                    { data: 'vendor' },
                    { data: 'payment_mode' },
                    { data: 'amount' },
                    { data: 'notes' },
                    { data: 'created_by' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.expenses.list') }}',
                ajaxData: function(request) {
                    request.expense_category_id = $('#expenseCategoryFilter').val() || '';
                    request.payment_mode_id = $('#expensePaymentFilter').val() || '';
                }
            });

            $(document).on('change', '#expenseCategoryFilter, #expensePaymentFilter', function () {
                if (window.expenseTable) {
                    window.expenseTable.draw();
                }
            });

            $(document).on('click', '#resetExpenseFilters', function () {
                $('#expenseCategoryFilter').val('').trigger('change');
                $('#expensePaymentFilter').val('').trigger('change');
            });
        });
    </script>
@endsection
