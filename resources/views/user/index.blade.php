@extends('layouts.main')

@section('title')
    Users
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Users</h5>
                <p class="mb-0 text-muted">Admin can create staff, update roles and manage access here.</p>
            </div>
            <div class="d-flex my-xl-auto right-content gap-2">
                <a href="{{ route('admin.export.user') }}" class="btn btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('admin.export.user-pdf') }}" class="btn btn-pdf">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </a>
                <a href="{{ route('admin.user.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add User
                </a>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">User List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="userTable" class="table table-bordered align-middle w-100" data-list-url="{{ route('admin.user.list') }}">
                        <thead>
                            <tr>
                                <th style="width: 70px;">S.No</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th style="width: 260px;">Action</th>
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
        $(document).ready(function() {
            window.userTable = window.initServerSideDataTable({
                selector: '#userTable',
                pageLength: 10,
                sort: false,
                searchable: false,
                searchColumns: [1, 2, 3],
                columns: [
                    { data: 'sno' },
                    { data: 'name' },
                    { data: 'email' },
                    { data: 'role' },
                    { data: 'status' },
                    { data: 'created' },
                    { data: 'action' },
                ],
                ajaxUrl: '{{ route('admin.user.list') }}'
            });

            function submitInlineUserUpdate(selectElement, payload, confirmTitle, confirmText) {
                var $select = $(selectElement);
                var isSelectInput = $select.is('select');
                var previousValue = isSelectInput ? $select.data('currentValue') : null;

                Swal.fire({
                    title: confirmTitle,
                    text: confirmText,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update',
                    cancelButtonText: 'Cancel'
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        if (isSelectInput) {
                            $select.val(previousValue);
                        }
                        return;
                    }

                    showLoader();

                    $.post($select.data('url'), payload, function(response) {
                        hideLoader();
                        showNotification(response.message, response.type || 'success');
                        if (isSelectInput) {
                            $select.data('currentValue', $select.val());
                        }
                        if (window.userTable) {
                            window.userTable.draw(false);
                        }
                    }).fail(function(xhr) {
                        hideLoader();
                        var response = xhr.responseJSON || {};
                        showNotification(response.message || 'Could not update user now.', 'error');
                        if (isSelectInput) {
                            $select.val(previousValue);
                        }
                    });
                });
            }

            $(document).on('change', '.js-user-role-select', function() {
                submitInlineUserUpdate(
                    this,
                    { role: $(this).val() },
                    'Change user role?',
                    'This will update what the user can access in the system.'
                );
            });

            $(document).on('click', '.js-user-status-toggle', function() {
                var $button = $(this);

                submitInlineUserUpdate(
                    this,
                    { is_active: Number($button.data('nextValue')) },
                    $button.data('confirmTitle') || 'Change user status?',
                    $button.data('confirmText') || 'This will switch whether the account can login.'
                );
            });
        });
    </script>
@endsection
