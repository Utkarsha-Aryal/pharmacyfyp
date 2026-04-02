@php
    $analysis = $result['analysis'] ?? [];
    $matches = $result['matches'] ?? [];
    $lines = $result['lines'] ?? [];
    $failed = ($result['extraction_status'] ?? '') === 'failed';
@endphp

@if ($failed)
    <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
        OCR could not read this image clearly. You can still continue manually or try another scan.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block">Document Type</small>
            <strong>{{ strtoupper(str_replace('_', ' ', $analysis['document_type'] ?? 'unknown')) }}</strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block">Supplier</small>
            <strong>{{ $analysis['supplier_name'] ?? 'Not detected' }}</strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block">Invoice No</small>
            <strong>{{ $analysis['invoice_no'] ?? 'Not detected' }}</strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="border rounded-3 p-3 h-100">
            <small class="text-muted d-block">Confidence</small>
            <strong>{{ (int) ($analysis['confidence'] ?? 0) }}%</strong>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <span class="badge {{ ($analysis['next_action'] ?? '') === 'open_existing' ? 'bg-info' : (($analysis['next_action'] ?? '') === 'create_new' ? 'bg-success' : 'bg-warning text-dark') }}">
        {{ strtoupper(str_replace('_', ' ', $analysis['next_action'] ?? 'fill_manually')) }}
    </span>
    @if (!empty($analysis['invoice_date']))
        <span class="badge bg-light text-dark border">Invoice Date: {{ $analysis['invoice_date'] }}</span>
    @endif
    @if (isset($analysis['total_amount']) && $analysis['total_amount'] !== null)
        <span class="badge bg-light text-dark border">Total: NPR {{ number_format((float) $analysis['total_amount'], 2) }}</span>
    @endif
</div>

@if (!empty($matches))
    <div class="mb-3">
        <label class="form-label">Choose Existing Bill</label>
        <select id="ocrMatchedPurchaseSelect" class="form-select">
            @foreach ($matches as $match)
                <option value="{{ $match['id'] }}">
                    {{ $match['reference_no'] }} | {{ $match['supplier_name'] }} | {{ $match['purchase_date'] }} | {{ $match['grand_total'] }}
                </option>
            @endforeach
        </select>
        <small class="text-muted">Pick the bill you want to review before creating a new draft.</small>
    </div>

    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div><strong>{{ count($matches) }} matching bill(s)</strong> found in the system. Pick one if this invoice already exists.</div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="ocrLoadToPurchaseBtn"><i class="fa-solid fa-file-circle-plus"></i> Load Into Purchase Entry</button>
            <a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen-to-square"></i> Fill Manually</a>
        </div>
    </div>
@else
    <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>{{ $failed ? 'OCR could not extract usable text, but you can still create a draft or fill manually.' : 'No existing bill matched this scan. You can create a new draft, fill manually, or redo the scan if the image is unclear.' }}</div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="ocrLoadToPurchaseBtn"><i class="fa-solid fa-file-circle-plus"></i> Create Draft</button>
            <a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-pen-to-square"></i> Fill Manually</a>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="ocrRedoBtn"><i class="fa-solid fa-rotate-right"></i> Redo OCR</button>
        </div>
    </div>
@endif

<form action="{{ route('admin.ocr.draft-purchase') }}" method="POST" class="mb-3 d-none" id="ocrDraftForm">
    @csrf
    <input type="hidden" name="ocr_text" id="ocrDraftText" value="{{ $result['text'] ?? '' }}">
    <input type="hidden" name="ocr_summary" id="ocrDraftSummary" value='@json(["file_name" => $result["file_name"] ?? "", "analysis" => $analysis, "matches" => $matches])'>
    <input type="hidden" name="selected_purchase_id" id="ocrSelectedPurchaseId" value="{{ $analysis['selected_purchase_id'] ?? '' }}">
    <button type="submit" class="btn btn-outline-primary"><i class="fa-solid fa-file-circle-plus"></i> Load into Purchase Entry</button>
</form>

<div class="mb-3">
    <label class="form-label">Extracted Text</label>
    <textarea class="form-control" rows="10" readonly>{{ $result['text'] ?? '' }}</textarea>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="border rounded-3 p-3 h-100">
            <div class="fw-semibold mb-2">Matching Bills</div>

            @if (!empty($matches))
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Bill</th>
                                <th>Supplier</th>
                                <th>Date</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($matches as $match)
                                <tr>
                                    <td>{{ $match['reference_no'] ?? '-' }}</td>
                                    <td>{{ $match['supplier_name'] ?? '-' }}</td>
                                    <td>{{ $match['purchase_date'] ?? '-' }}</td>
                                    <td>{{ $match['grand_total'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-muted">{{ $failed ? 'No readable bill data was detected from this scan.' : 'No existing bill matched the scan.' }}</div>
            @endif
        </div>
    </div>
    <div class="col-lg-6">
        <div class="border rounded-3 p-3 h-100">
            <div class="fw-semibold mb-2">Detected Lines</div>
            <div class="table-responsive" style="max-height: 280px; overflow: auto;">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">S.No</th>
                            <th>Line</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $index => $line)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $line }}</td>
                            </tr>
                        @endforeach
                        @if (empty($lines))
                            <tr>
                                <td colspan="2" class="text-muted text-center">No readable lines were detected from this scan.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
