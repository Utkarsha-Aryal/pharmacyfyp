@extends('layouts.main')

@section('title')
    Purchase Returns
@endsection

@section('main-content')
    <div class="admin-page-wrap">
        <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
            <div class="my-auto">
                <h5 class="page-title fs-21 mb-1">Purchase Returns</h5>
                <p class="mb-0 text-muted">Batch-level return to supplier with strict quantity check.</p>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="{{ route('admin.purchase-returns.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> New Purchase Return
                </a>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">Return List</div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th>Return Date</th>
                                <th>Supplier</th>
                                <th>Purchase Bill</th>
                                <th>Items</th>
                                <th>Created By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($returns as $index => $return)
                                <tr>
                                    <td>{{ $returns->firstItem() + $index }}</td>
                                    <td>{{ $return->return_date_show }}</td>
                                    <td>{{ $return->supplier?->supplier_name ?? '-' }}</td>
                                    <td>{{ $return->purchase?->reference?->reference_no ?: ('PUR-' . $return->purchase_id) }}</td>
                                    <td>{{ $return->items()->count() }}</td>
                                    <td>{{ $return->returnedBy?->name ?? '-' }}</td>
                                    <td>
                                        <div class="table-action-group">
                                            <a href="{{ route('admin.purchase-returns.show', $return) }}" class="btn btn-sm btn-outline-primary table-action-btn" title="View">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.purchase-returns.print', $return) }}" target="_blank" class="btn btn-sm btn-outline-dark table-action-btn" title="Print / PDF">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No purchase return records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $returns->links() }}
            </div>
        </div>
    </div>
@endsection
