@extends('backend.layouts.main')

@section('title')
    Expense Tracking
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Expense Tracking</h5>
                <p class="mb-0 text-muted">Quick admin expense entry with cash and bank payment lines.</p>
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
                                    <label class="form-label">Category</label>
                                    <select name="category" class="form-select" required>
                                        @foreach ($expenseCategories as $category)
                                            <option value="{{ $category }}">{{ $category }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_mode" class="form-select" required>
                                        <option value="cash">Cash</option>
                                        <option value="bank">Bank</option>
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
                                <th>Category</th>
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
                expenseSaveBtn.html('<i class="fa fa-save"></i> Save Expense');
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
                expenseForm.find('[name="category"]').val($(this).data('category'));
                expenseForm.find('[name="vendor_name"]').val($(this).data('vendor-name'));
                expenseForm.find('[name="payment_mode"]').val($(this).data('payment-mode'));
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
                ajaxUrl: '{{ route('admin.expenses.list') }}'
            });
        });
    </script>
@endsection
