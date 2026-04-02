@extends('layouts.main')

@section('title')
    Settings
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Settings</h5>
                <p class="mb-0 text-muted">Keep app name, branding and SMTP setup in one backend page.</p>
            </div>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" id="settingsForm">
            @csrf

            <div class="card custom-card">
                <div class="card-body">
                    <ul class="nav nav-tabs settings-tab-nav" id="settingsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#settings-general" type="button">General</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-branding" type="button">Branding</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-smtp" type="button">SMTP</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-payment-modes" type="button">Payment Modes</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-4">
                        <div class="tab-pane fade show active" id="settings-general" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">App Name</label>
                                    <input type="text" name="app_name" class="form-control" value="{{ old('app_name', $settings['app_name']) }}" placeholder="Enter app name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Company Email</label>
                                    <input type="email" name="company_email" class="form-control" value="{{ old('company_email', $settings['company_email']) }}" placeholder="Enter company email">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Company Phone</label>
                                    <input type="text" name="company_phone" class="form-control" value="{{ old('company_phone', $settings['company_phone']) }}" placeholder="Enter phone number">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Company Address</label>
                                    <div id="companyAddressEditor" class="settings-editor">{!! old('company_address', $settings['company_address']) !!}</div>
                                    <input type="hidden" name="company_address" id="company_address" value="{{ old('company_address', $settings['company_address']) }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Notification Email</label>
                                    <input type="email" name="notification_email" class="form-control" value="{{ old('notification_email', $settings['notification_email']) }}" placeholder="Send alerts to this email if needed">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Currency Symbol</label>
                                    <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $settings['currency_symbol']) }}" placeholder="NPR or Rs.">
                                    <small class="text-muted">This symbol will show before amounts across reports, sales, purchase and finance.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Low Stock Threshold</label>
                                    <input type="number" name="low_stock_threshold" class="form-control" min="1" value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" placeholder="10">
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="settings-branding" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">App Logo</label>
                                    <input type="file" name="app_logo" class="form-control" accept=".png,.jpg,.jpeg" data-image-preview-input="#appLogoPreview">
                                    <div class="settings-image-preview mt-3">
                                        <img src="{{ !empty($settings['app_logo']) ? asset($settings['app_logo']) : app_logo_url() }}" alt="App Logo" id="appLogoPreview">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Favicon</label>
                                    <input type="file" name="favicon" class="form-control" accept=".png,.jpg,.jpeg,.ico" data-image-preview-input="#faviconPreview">
                                    <div class="settings-image-preview mt-3">
                                        <img src="{{ !empty($settings['favicon']) ? asset($settings['favicon']) : app_favicon_url() }}" alt="Favicon" id="faviconPreview">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="settings-smtp" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control" value="{{ old('smtp_host', $settings['smtp_host']) }}" placeholder="smtp.example.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="text" name="smtp_port" class="form-control" value="{{ old('smtp_port', $settings['smtp_port']) }}" placeholder="587">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" name="smtp_username" class="form-control" value="{{ old('smtp_username', $settings['smtp_username']) }}" placeholder="Enter username">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="text" name="smtp_password" class="form-control" value="{{ old('smtp_password', $settings['smtp_password']) }}" placeholder="Enter password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Encryption</label>
                                    <input type="text" name="smtp_encryption" class="form-control" value="{{ old('smtp_encryption', $settings['smtp_encryption']) }}" placeholder="smtp / smtps (tls / ssl also works)">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mail From Name</label>
                                    <input type="text" name="mail_from_name" class="form-control" value="{{ old('mail_from_name', $settings['mail_from_name']) }}" placeholder="Display name">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Mail From Address</label>
                                    <input type="email" name="mail_from_address" class="form-control" value="{{ old('mail_from_address', $settings['mail_from_address']) }}" placeholder="from@example.com">
                                </div>
                                <div class="col-md-12">
                                    <div class="smtp-test-box">
                                        <div>
                                            <h6 class="mb-1">SMTP Test</h6>
                                            <p class="mb-0 text-muted">You can test the current SMTP fields directly from here without saving settings first.</p>
                                        </div>
                                        <div class="row g-2 align-items-end mt-2">
                                            <div class="col-md-8">
                                                <label class="form-label">Test Recipient</label>
                                                <input type="email" id="testMailRecipient" class="form-control" value="{{ old('email', $settings['notification_email']) }}" placeholder="mailbox@example.com">
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-pdf w-100" id="sendTestMailBtn" data-url="{{ route('admin.settings.test-mail') }}">
                                                    <i class="fa-solid fa-paper-plane"></i> Send Test Mail
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="settings-payment-modes" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1">Payment Mode Master</h6>
                                    <p class="mb-0 text-muted">Cash and Bank stay fixed. Custom digital and bank-like modes can be added here.</p>
                                </div>
                                <button type="button" class="btn btn-primary" id="addPaymentModeBtn">
                                    <i class="fa-solid fa-plus"></i> Add Payment Mode
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle" id="paymentModeTable">
                                    <thead>
                                        <tr>
                                            <th>S.No</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($paymentModes as $index => $mode)
                                            <tr data-id="{{ $mode->id }}">
                                                <td>{{ $index + 1 }}</td>
                                                <td class="mode-name">{{ $mode->name }}</td>
                                                <td class="mode-type">{{ ucfirst($mode->type) }}</td>
                                                <td class="mode-status">
                                                    <span class="report-badge {{ $mode->is_active ? 'report-badge-success' : 'report-badge-danger' }}">
                                                        {{ $mode->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="table-action-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary table-action-btn editPaymentModeBtn" data-id="{{ $mode->id }}" data-name="{{ $mode->name }}" data-type="{{ $mode->type }}" data-active="{{ $mode->is_active ? 1 : 0 }}" title="Edit">
                                                            <i class="fa-solid fa-pen-to-square"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm {{ $mode->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} table-action-btn togglePaymentModeBtn" data-id="{{ $mode->id }}" data-active="{{ $mode->is_active ? 1 : 0 }}" title="Toggle">
                                                            <i class="fa-solid {{ $mode->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                                        </button>
                                                        @if (!in_array(strtolower($mode->name), ['cash', 'bank'], true))
                                                            <button type="button" class="btn btn-sm btn-outline-danger table-action-btn deletePaymentModeBtn" data-id="{{ $mode->id }}" title="Delete">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Save Settings
                    </button>
                </div>
            </div>
        </form>

        <div class="modal fade" id="paymentModeModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="paymentModeForm">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Payment Mode</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="payment_mode_id">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" id="payment_mode_name" class="form-control" placeholder="eSewa" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select id="payment_mode_type" class="form-select" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank">Bank</option>
                                    <option value="digital">Digital</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Save Mode
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var editorElement = document.getElementById('companyAddressEditor');
            var hiddenInput = document.getElementById('company_address');
            var settingsForm = document.getElementById('settingsForm');

            if (editorElement && hiddenInput && settingsForm && typeof Quill !== 'undefined') {
                // small editor is enough here because admin may want styled address and footer text
                var addressEditor = new Quill('#companyAddressEditor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            ['bold', 'italic', 'underline'],
                            [{
                                'list': 'ordered'
                            }, {
                                'list': 'bullet'
                            }],
                            ['link']
                        ]
                    }
                });

                settingsForm.addEventListener('submit', function() {
                    hiddenInput.value = addressEditor.root.innerHTML;
                });
            }

            // Test mail reads the current SMTP fields directly, so admin can check mail before saving.
            $(document).on('click', '#sendTestMailBtn', function() {
                var $button = $(this);
                var testMailUrl = $button.data('url');

                if (!testMailUrl) {
                    return;
                }

                showLoader();
                $button.prop('disabled', true);

                $.ajax({
                    url: testMailUrl,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        email: $('#testMailRecipient').val(),
                        smtp_host: $('input[name=\"smtp_host\"]').val(),
                        smtp_port: $('input[name=\"smtp_port\"]').val(),
                        smtp_username: $('input[name=\"smtp_username\"]').val(),
                        smtp_password: $('input[name=\"smtp_password\"]').val(),
                        smtp_encryption: $('input[name=\"smtp_encryption\"]').val(),
                        mail_from_address: $('input[name=\"mail_from_address\"]').val(),
                        mail_from_name: $('input[name=\"mail_from_name\"]').val(),
                        notification_email: $('input[name=\"notification_email\"]').val()
                    },
                    success: function(response) {
                        showNotification(response.message || 'Test mail sent.', response.type || 'success');
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON || {};
                        showNotification(response.message || 'Test mail failed.', 'error');
                    },
                    complete: function() {
                        hideLoader();
                        $button.prop('disabled', false);
                    }
                });
            });

            var paymentModeModalElement = document.getElementById('paymentModeModal');
            var paymentModeModal = paymentModeModalElement ? new bootstrap.Modal(paymentModeModalElement) : null;

            function paymentModeRow(mode, index) {
                var deleteButton = ['cash', 'bank'].indexOf(String(mode.name).toLowerCase()) === -1
                    ? '<button type="button" class="btn btn-sm btn-outline-danger table-action-btn deletePaymentModeBtn" data-id="' + mode.id + '" title="Delete"><i class="fa-solid fa-trash"></i></button>'
                    : '';

                return '<tr data-id="' + mode.id + '">' +
                    '<td>' + index + '</td>' +
                    '<td class="mode-name">' + mode.name + '</td>' +
                    '<td class="mode-type">' + mode.type.charAt(0).toUpperCase() + mode.type.slice(1) + '</td>' +
                    '<td class="mode-status"><span class="report-badge ' + (mode.is_active ? 'report-badge-success' : 'report-badge-danger') + '">' + (mode.is_active ? 'Active' : 'Inactive') + '</span></td>' +
                    '<td><div class="table-action-group">' +
                        '<button type="button" class="btn btn-sm btn-outline-primary table-action-btn editPaymentModeBtn" data-id="' + mode.id + '" data-name="' + mode.name + '" data-type="' + mode.type + '" data-active="' + (mode.is_active ? 1 : 0) + '" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>' +
                        '<button type="button" class="btn btn-sm ' + (mode.is_active ? 'btn-outline-warning' : 'btn-outline-success') + ' table-action-btn togglePaymentModeBtn" data-id="' + mode.id + '" data-active="' + (mode.is_active ? 1 : 0) + '" title="Toggle"><i class="fa-solid ' + (mode.is_active ? 'fa-toggle-on' : 'fa-toggle-off') + '"></i></button>' +
                        deleteButton +
                    '</div></td>' +
                '</tr>';
            }

            function refreshPaymentModeTable() {
                $.get('{{ route('admin.payment-modes.index') }}', function(response) {
                    var rows = response.data || [];
                    var tbody = $('#paymentModeTable tbody');
                    tbody.empty();

                    rows.forEach(function(mode, index) {
                        tbody.append(paymentModeRow(mode, index + 1));
                    });
                });
            }

            $(document).on('click', '#addPaymentModeBtn', function() {
                $('#payment_mode_id').val('');
                $('#payment_mode_name').val('');
                $('#payment_mode_type').val('cash');
                if (paymentModeModal) {
                    paymentModeModal.show();
                }
            });

            $(document).on('click', '.editPaymentModeBtn', function() {
                $('#payment_mode_id').val($(this).data('id'));
                $('#payment_mode_name').val($(this).data('name'));
                $('#payment_mode_type').val($(this).data('type'));
                if (paymentModeModal) {
                    paymentModeModal.show();
                }
            });

            $(document).on('submit', '#paymentModeForm', function(event) {
                event.preventDefault();

                var modeId = $('#payment_mode_id').val();
                var url = modeId
                    ? '{{ url('admin/payment-modes') }}/' + modeId + '/update'
                    : '{{ route('admin.payment-modes.store') }}';

                $.post(url, {
                    _token: '{{ csrf_token() }}',
                    name: $('#payment_mode_name').val(),
                    type: $('#payment_mode_type').val()
                }, function(response) {
                    showNotification(response.message || 'Payment mode saved.', response.type || 'success');
                    refreshPaymentModeTable();
                    if (paymentModeModal) {
                        paymentModeModal.hide();
                    }
                }).fail(function(xhr) {
                    var response = xhr.responseJSON || {};
                    showNotification(response.message || 'Could not save payment mode.', 'error');
                });
            });

            $(document).on('click', '.togglePaymentModeBtn', function() {
                var modeId = $(this).data('id');
                var nextState = $(this).data('active') == 1 ? 0 : 1;

                $.post('{{ url('admin/payment-modes') }}/' + modeId + '/update', {
                    _token: '{{ csrf_token() }}',
                    is_active: nextState
                }, function(response) {
                    showNotification(response.message || 'Payment mode updated.', response.type || 'success');
                    refreshPaymentModeTable();
                }).fail(function(xhr) {
                    var response = xhr.responseJSON || {};
                    showNotification(response.message || 'Could not update payment mode.', 'error');
                });
            });

            $(document).on('click', '.deletePaymentModeBtn', function() {
                var modeId = $(this).data('id');

                $.post('{{ url('admin/payment-modes') }}/' + modeId + '/delete', {
                    _token: '{{ csrf_token() }}'
                }, function(response) {
                    showNotification(response.message || 'Payment mode deleted.', response.type || 'success');
                    refreshPaymentModeTable();
                }).fail(function(xhr) {
                    var response = xhr.responseJSON || {};
                    showNotification(response.message || 'Could not delete payment mode.', 'error');
                });
            });
        });
    </script>
@endsection
