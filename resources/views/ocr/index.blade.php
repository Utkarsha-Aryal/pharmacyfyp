@extends('layouts.main')

@section('title')
    OCR Purchase Helper
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    @php
        $ocrResult = session('ocr_result');
        $ocrDraft = session('ocr_draft');
    @endphp

    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">OCR Purchase Helper</h5>
                <p class="mb-0 text-muted">Upload a supplier invoice image, then I will try to detect whether it already matches an existing bill or should become a new purchase draft.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Purchase Bills
                </a>
            </div>
        </div>

        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">Upload Invoice Image</div>
            </div>
            <div class="card-body">
                <form id="ocrUploadForm" action="{{ route('admin.ocr.extract') }}" method="POST" enctype="multipart/form-data" data-extract-url="{{ route('admin.ocr.extract') }}">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-8">
                            <label class="form-label">Image or PDF</label>
                            <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required>
                            <div class="form-text">I can read JPG, PNG and PDF invoices.</div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                <button type="submit" class="btn btn-primary" id="ocrExtractBtn">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> Extract Text
                                </button>
                                <a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-outline-secondary">
                                    <i class="fa-solid fa-pen-to-square"></i> Fill Manually
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title mb-0">Extraction Result</div>
                <span class="badge bg-secondary" id="ocrResultState">Waiting for upload</span>
            </div>
            <div class="card-body">
                <div id="ocrResultPanel">
                    @if ($ocrResult)
                        <div class="ocr-result-shell">
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                Extracted from <strong>{{ $ocrResult['file_name'] }}</strong>.
                                @if (!empty($ocrResult['analysis']['next_action']))
                                    <span class="ms-1">Suggested action: <strong>{{ strtoupper(str_replace('_', ' ', $ocrResult['analysis']['next_action'])) }}</strong>.</span>
                                @endif
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            @include('ocr.partials.result', ['result' => $ocrResult])
                        </div>
                    @elseif ($ocrDraft)
                        <div class="alert alert-info">
                            OCR draft is ready for purchase entry. You can continue converting it into a bill or go back and scan again.
                        </div>
                        <div class="text-center text-muted py-5">
                            Upload another image to get a fresh OCR result.
                        </div>
                    @else
                        <div class="text-center text-muted py-5" id="ocrEmptyState">
                            Upload an invoice image to start OCR extraction.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(function () {
            var $form = $('#ocrUploadForm');
            var $panel = $('#ocrResultPanel');
            var $state = $('#ocrResultState');
            var $extractBtn = $('#ocrExtractBtn');

            function escapeHtml(text) {
                return String(text ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function money(value) {
                return (Number(value || 0)).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function badgeClassForAction(action) {
                if (action === 'open_existing') {
                    return 'bg-info';
                }

                if (action === 'create_new') {
                    return 'bg-success';
                }

                return 'bg-warning text-dark';
            }

            function renderResult(result, notice, noticeType) {
                var analysis = result.analysis || {};
                var matches = result.matches || [];
                var lines = result.lines || [];
                var summaryHtml = '';

                if (notice) {
                    summaryHtml += '<div class="alert ' + (noticeType === 'success' ? 'alert-success' : (noticeType === 'danger' ? 'alert-danger' : 'alert-warning')) + ' alert-dismissible fade show mb-3" role="alert">';
                    summaryHtml += escapeHtml(notice);
                    summaryHtml += '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    summaryHtml += '</div>';
                }

                summaryHtml += '<div class="row g-3 mb-3">';
                summaryHtml += '  <div class="col-md-3"><div class="border rounded-3 p-3 h-100"><small class="text-muted d-block">Document Type</small><strong>' + escapeHtml((analysis.document_type || 'unknown').replace(/_/g, ' ').toUpperCase()) + '</strong></div></div>';
                summaryHtml += '  <div class="col-md-3"><div class="border rounded-3 p-3 h-100"><small class="text-muted d-block">Supplier</small><strong>' + escapeHtml(analysis.supplier_name || 'Not detected') + '</strong></div></div>';
                summaryHtml += '  <div class="col-md-3"><div class="border rounded-3 p-3 h-100"><small class="text-muted d-block">Invoice No</small><strong>' + escapeHtml(analysis.invoice_no || 'Not detected') + '</strong></div></div>';
                summaryHtml += '  <div class="col-md-3"><div class="border rounded-3 p-3 h-100"><small class="text-muted d-block">Confidence</small><strong>' + escapeHtml(String(analysis.confidence || 0)) + '%</strong></div></div>';
                summaryHtml += '</div>';

                summaryHtml += '<div class="d-flex flex-wrap gap-2 mb-3">';
                summaryHtml += '<span class="badge ' + badgeClassForAction(analysis.next_action) + '">' + escapeHtml((analysis.next_action || 'fill_manually').replace(/_/g, ' ').toUpperCase()) + '</span>';
                if (result.extraction_status === 'failed') {
                    summaryHtml += '<span class="badge bg-danger">OCR FAILED</span>';
                }
                if (analysis.invoice_date) {
                    summaryHtml += '<span class="badge bg-light text-dark border">Invoice Date: ' + escapeHtml(analysis.invoice_date) + '</span>';
                }
                if (analysis.total_amount !== null && analysis.total_amount !== undefined) {
                    summaryHtml += '<span class="badge bg-light text-dark border">Total: NPR ' + escapeHtml(money(analysis.total_amount)) + '</span>';
                }
                summaryHtml += '</div>';

                if (matches.length) {
                    summaryHtml += '<div class="mb-3">';
                    summaryHtml += '  <label class="form-label">Choose Existing Bill</label>';
                    summaryHtml += '  <select id="ocrMatchedPurchaseSelect" class="form-select">';
                    matches.forEach(function (match) {
                        summaryHtml += '    <option value="' + escapeHtml(match.id) + '">' + escapeHtml(match.reference_no || '-') + ' | ' + escapeHtml(match.supplier_name || '-') + ' | ' + escapeHtml(match.purchase_date || '-') + ' | ' + escapeHtml(match.grand_total || '-') + '</option>';
                    });
                    summaryHtml += '  </select>';
                    summaryHtml += '  <small class="text-muted">Pick the bill you want to review before creating a new draft.</small>';
                    summaryHtml += '</div>';

                    summaryHtml += '<div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">';
                    summaryHtml += '  <div><strong>' + matches.length + ' matching bill(s)</strong> found in the system. Pick one if this invoice already exists.</div>';
                    summaryHtml += '  <div class="d-flex gap-2">';
                    summaryHtml += '    <button type="button" class="btn btn-sm btn-outline-primary" id="ocrLoadToPurchaseBtn"><i class="fa-solid fa-file-circle-plus"></i> Load Into Purchase Entry</button>';
                    summaryHtml += '    <a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen-to-square"></i> Fill Manually</a>';
                    summaryHtml += '  </div>';
                    summaryHtml += '</div>';
                } else {
                    summaryHtml += '<div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2">';
                    summaryHtml += '  <div>No existing bill matched this scan. You can create a new draft, fill manually, or redo the scan if the image is unclear.</div>';
                    summaryHtml += '  <div class="d-flex gap-2">';
                    summaryHtml += '    <button type="button" class="btn btn-sm btn-outline-primary" id="ocrLoadToPurchaseBtn"><i class="fa-solid fa-file-circle-plus"></i> Create Draft</button>';
                    summaryHtml += '    <a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen-to-square"></i> Fill Manually</a>';
                    summaryHtml += '    <button type="button" class="btn btn-sm btn-outline-secondary" id="ocrRedoBtn"><i class="fa-solid fa-rotate-right"></i> Redo OCR</button>';
                    summaryHtml += '  </div>';
                    summaryHtml += '</div>';
                }

                summaryHtml += '<form action="{{ route('admin.ocr.draft-purchase') }}" method="POST" class="mb-3 d-none" id="ocrDraftForm">';
                summaryHtml += '  @csrf';
                summaryHtml += '  <input type="hidden" name="ocr_text" id="ocrDraftText">';
                summaryHtml += '  <input type="hidden" name="ocr_summary" id="ocrDraftSummary">';
                summaryHtml += '  <input type="hidden" name="selected_purchase_id" id="ocrSelectedPurchaseId">';
                summaryHtml += '  <button type="submit" class="btn btn-outline-primary"><i class="fa-solid fa-file-circle-plus"></i> Load into Purchase Entry</button>';
                summaryHtml += '</form>';

                summaryHtml += '<div class="mb-3">';
                summaryHtml += '  <label class="form-label">Extracted Text</label>';
                summaryHtml += '  <textarea class="form-control" rows="10" readonly>' + escapeHtml(result.text || '') + '</textarea>';
                summaryHtml += '</div>';

                summaryHtml += '<div class="row g-3 mb-3">';
                summaryHtml += '  <div class="col-lg-6">';
                summaryHtml += '    <div class="border rounded-3 p-3 h-100">';
                summaryHtml += '      <div class="fw-semibold mb-2">Matching Bills</div>';

                if (matches.length) {
                    summaryHtml += '      <div class="table-responsive">';
                    summaryHtml += '        <table class="table table-bordered align-middle mb-0">';
                    summaryHtml += '          <thead><tr><th>Bill</th><th>Supplier</th><th>Date</th><th>Total</th></tr></thead><tbody>';
                    matches.forEach(function (match) {
                        summaryHtml += '          <tr>';
                        summaryHtml += '            <td>' + escapeHtml(match.reference_no || '-') + '</td>';
                        summaryHtml += '            <td>' + escapeHtml(match.supplier_name || '-') + '</td>';
                        summaryHtml += '            <td>' + escapeHtml(match.purchase_date || '-') + '</td>';
                        summaryHtml += '            <td>' + escapeHtml(match.grand_total || '-') + '</td>';
                        summaryHtml += '          </tr>';
                    });
                    summaryHtml += '        </tbody></table>';
                    summaryHtml += '      </div>';
                } else {
                    summaryHtml += '<div class="text-muted">No existing bill matched the scan.</div>';
                }

                summaryHtml += '    </div>';
                summaryHtml += '  </div>';
                summaryHtml += '  <div class="col-lg-6">';
                summaryHtml += '    <div class="border rounded-3 p-3 h-100">';
                summaryHtml += '      <div class="fw-semibold mb-2">Detected Lines</div>';
                summaryHtml += '      <div class="table-responsive" style="max-height: 280px; overflow: auto;">';
                summaryHtml += '        <table class="table table-bordered align-middle mb-0">';
                summaryHtml += '          <thead><tr><th style="width: 70px;">S.No</th><th>Line</th></tr></thead><tbody>';
                lines.forEach(function (line, index) {
                    summaryHtml += '          <tr><td>' + (index + 1) + '</td><td>' + escapeHtml(line) + '</td></tr>';
                });
                summaryHtml += '        </tbody></table>';
                summaryHtml += '      </div>';
                summaryHtml += '    </div>';
                summaryHtml += '  </div>';
                summaryHtml += '</div>';

                $state
                    .removeClass('bg-secondary bg-success bg-info bg-warning text-dark')
                    .addClass(
                        analysis.next_action === 'open_existing' ? 'bg-info' :
                        analysis.next_action === 'create_new' ? 'bg-success' :
                        'bg-warning text-dark'
                    )
                    .text(
                        analysis.next_action === 'open_existing' ? 'Existing bill found' :
                        analysis.next_action === 'create_new' ? 'Create new draft' :
                        'Needs review'
                    );

                $panel.html(summaryHtml);

                var selectedMatchId = matches.length ? matches[0].id : '';
                $('#ocrMatchedPurchaseSelect').val(String(selectedMatchId));

                $('#ocrDraftText').val(result.text || '');
                $('#ocrDraftSummary').val(JSON.stringify({
                    file_name: result.file_name || '',
                    analysis: analysis,
                    matches: matches
                }));
                $('#ocrSelectedPurchaseId').val(selectedMatchId || '');
            }

            function renderError(message) {
                $state.removeClass('bg-secondary bg-success bg-info bg-warning text-dark').addClass('bg-danger').text('OCR failed');
                $panel.html(
                    '<div class="alert alert-danger">' +
                        '<strong>OCR did not extract usable text.</strong> ' + escapeHtml(message || 'Please try another image or fill manually.') +
                    '</div>' +
                    '<div class="d-flex flex-wrap gap-2">' +
                        '<a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-outline-primary"><i class="fa-solid fa-pen-to-square"></i> Fill Manually</a>' +
                        '<button type="button" class="btn btn-outline-secondary" id="ocrRedoBtn"><i class="fa-solid fa-rotate-right"></i> Redo OCR</button>' +
                    '</div>'
                );
            }

            $(document).on('click', '#ocrRedoBtn', function () {
                $form[0].reset();
                $state.removeClass('bg-success bg-info bg-warning bg-danger text-dark').addClass('bg-secondary').text('Waiting for upload');
                $panel.html('<div class="text-center text-muted py-5" id="ocrEmptyState">Upload an invoice image to start OCR extraction.</div>');
            });

            $(document).on('click', '#ocrLoadToPurchaseBtn', function () {
                var selectedPurchaseId = $('#ocrMatchedPurchaseSelect').val() || '';
                $('#ocrSelectedPurchaseId').val(selectedPurchaseId);
                $('#ocrDraftForm').trigger('submit');
            });

            $(document).on('change', '#ocrMatchedPurchaseSelect', function () {
                $('#ocrSelectedPurchaseId').val($(this).val() || '');
            });

            $form.on('submit', function (event) {
                event.preventDefault();

                var formData = new FormData(this);
                $extractBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Extracting...');
                $state.removeClass('bg-success bg-info bg-warning bg-danger text-dark').addClass('bg-secondary').text('Working...');
                $panel.html('<div class="text-center text-muted py-5"><i class="fa-solid fa-spinner fa-spin me-2"></i> Reading the invoice and checking for matches...</div>');

                $.ajax({
                    url: $form.data('extract-url'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        if (response && response.data) {
                            renderResult(response.data, response.message || '', response.type || 'success');
                            return;
                        }

                        renderError('No OCR payload returned from the server. Please try a clearer image or continue manually.');
                    },
                    error: function (xhr) {
                        var response = xhr.responseJSON || {};
                        renderError(response.message || 'The file could not be read. Please try another invoice image.');
                    },
                    complete: function () {
                        $extractBtn.prop('disabled', false).html('<i class="fa-solid fa-wand-magic-sparkles"></i> Extract Text');
                    }
                });
            });
        });
    </script>
@endsection
