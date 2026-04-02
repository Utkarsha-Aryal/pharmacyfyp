@extends('layouts.main')

@section('title')
    OCR Purchase Helper
@endsection

@section('body-class', 'workspace-form-page')

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">OCR Purchase Helper</h5>
                <p class="mb-0 text-muted">Upload a supplier invoice image and extract text before turning it into a purchase bill.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase.addpurchase') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Purchase Bills
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card custom-card h-100">
                    <div class="card-header">
                        <div class="card-title">Upload Invoice Image</div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.ocr.extract') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Image or PDF</label>
                                <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                                <div class="form-text">I can read JPG, PNG and PDF invoices.</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Extract Text
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card custom-card h-100">
                    <div class="card-header">
                        <div class="card-title">Extraction Result</div>
                    </div>
                    <div class="card-body">
                        @if (session('ocr_result'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                Extracted from <strong>{{ session('ocr_result.file_name') }}</strong>.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <form action="{{ route('admin.ocr.draft-purchase') }}" method="POST" class="mb-3">
                                @csrf
                                <input type="hidden" name="ocr_text" value="{{ session('ocr_result.text') }}">
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fa-solid fa-file-circle-plus"></i> Load into Purchase Entry
                                </button>
                            </form>
                            <div class="mb-3">
                                <label class="form-label">Extracted Text</label>
                                <textarea class="form-control" rows="14" readonly>{{ session('ocr_result.text') }}</textarea>
                            </div>
                            <div>
                                <label class="form-label">Detected Lines</label>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle">
                                        <thead>
                                            <tr>
                                                <th style="width: 80px;">S.No</th>
                                                <th>Line</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach (session('ocr_result.lines', []) as $index => $line)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $line }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                Upload an invoice image to start OCR extraction.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
