@extends('backend.layouts.main')

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
                                    <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $settings['currency_symbol']) }}" placeholder="NPR">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Low Stock Threshold</label>
                                    <input type="number" name="low_stock_threshold" class="form-control" min="1" value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}" placeholder="10">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Default Tax Rate (%)</label>
                                    <input type="number" step="0.01" min="0" max="100" name="tax_rate" class="form-control" value="{{ old('tax_rate', $settings['tax_rate']) }}" placeholder="13">
                                    <small class="text-muted">This default tax goes into new billing rows automatically.</small>
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
                                    <input type="file" name="favicon" class="form-control" accept=".png,.jpg,.jpeg" data-image-preview-input="#faviconPreview">
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
                                    <input type="text" name="smtp_encryption" class="form-control" value="{{ old('smtp_encryption', $settings['smtp_encryption']) }}" placeholder="tls / ssl">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mail From Name</label>
                                    <input type="text" name="mail_from_name" class="form-control" value="{{ old('mail_from_name', $settings['mail_from_name']) }}" placeholder="Display name">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Mail From Address</label>
                                    <input type="email" name="mail_from_address" class="form-control" value="{{ old('mail_from_address', $settings['mail_from_address']) }}" placeholder="from@example.com">
                                </div>
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
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var editorElement = document.getElementById('companyAddressEditor');
            var hiddenInput = document.getElementById('company_address');
            var settingsForm = document.getElementById('settingsForm');

            if (!editorElement || !hiddenInput || !settingsForm || typeof Quill === 'undefined') {
                return;
            }

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
        });
    </script>
@endsection
