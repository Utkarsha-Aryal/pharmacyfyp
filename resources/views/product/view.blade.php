@if ($type == 'error')
    <div class="modal-header">
        <h1 class="modal-title fs-5" id="staticBackdropLabel">
            Error
        </h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        {{ $message }}
    </div>
@else
    <div class="modal-header">
        <h5 class="modal-title">View Info</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <em class="icon ni ni-cross"></em>
        </a>
    </div>
    <!-- start -->
     <div class="modal-body">
    {{-- Section 1: Basic Info --}}
    <h6 class="border-bottom pb-2 mb-3">Basic Information</h6>
    <div class="row g-3">
        <div class="col-md-4">
            <strong>Category:</strong>
            <p>{{ $category->name ?? 'N/A' }}</p>
        </div>

        <div class="col-md-4">
            <strong>Product Name:</strong>
            <p>{{ $prevPost->product_name ?? 'N/A' }}</p>
        </div>

        <div class="col-md-4">
            <strong>Generic Name:</strong>
            <p>{{ $prevPost->generic_name ?? 'N/A' }}</p>
        </div>

        <div class="col-md-4">
            <strong>Composition:</strong>
            <p>{{ $prevPost->composition ?? 'N/A' }}</p>
        </div>

        <div class="col-md-4">
            <strong>Group Name:</strong>
            <p>{{ $prevPost->group_name ?? 'N/A' }}</p>
        </div>

        <div class="col-md-4">
            <strong>Manufacturer:</strong>
            <p>{{ $prevPost->manufacturer ?? 'N/A' }}</p>
        </div>

        <div class="col-md-4">
            <strong>Product Status:</strong>
            <p class="text-capitalize">{{ $prevPost->product_status ?? 'N/A' }}</p>
        </div>

        <div class="col-md-4">
            <strong>Order Number:</strong>
            <p>{{ $prevPost->order_number ?? 'N/A' }}</p>
        </div>

        <div class="col-md-4">
            <strong>Alert Quantity:</strong>
            <p>{{ $prevPost->alert_quantity ?? 'N/A' }}</p>
        </div>
    </div>

    {{-- Section 2: Pricing --}}
    <h6 class="border-bottom pb-2 mt-4 mb-3">Pricing</h6>
    <div class="row g-3">
        <div class="col-md-3">
            <strong>Previous Price:</strong>
            <p>Rs. {{ number_format($prevPost->previous_price, 2) ?? 'N/A' }}</p>
        </div>

        <div class="col-md-3">
            <strong>MRP:</strong>
            <p>Rs. {{ number_format($prevPost->mrp, 2) ?? 'N/A' }}</p>
        </div>

        <div class="col-md-3">
            <strong>Discount (%):</strong>
            <p>{{ $prevPost->discount ?? 0 }}%</p>
        </div>

        <div class="col-md-3">
            <strong>Display Price:</strong>
            <p>
                Rs. {{ number_format($prevPost->mrp - ($prevPost->mrp * $prevPost->discount / 100), 2) }}
            </p>
        </div>
    </div>

    {{-- Section 3: SEO --}}
    <h6 class="border-bottom pb-2 mt-4 mb-3">SEO Settings</h6>
    <div class="row">
        <div class="col-12">
            <strong>Meta Keywords:</strong>
            <p>{{ $prevPost->keywords ?? 'N/A' }}</p>
        </div>
    </div>

    {{-- Section 4: Description --}}
    <h6 class="border-bottom pb-2 mt-4 mb-3">Product Description</h6>
    <div class="mb-3">
        <div class="border rounded p-2" style="min-height:200px;">
            {!! $prevPost->description ?? '<em>No description available.</em>' !!}
        </div>
    </div>

    {{-- Section 5: Thumbnail --}}
    <h6 class="border-bottom pb-2 mt-4 mb-3">Thumbnail Image</h6>
    <div class="row">
        <div class="col-md-6">
            <div class="img-rectangle mt-2">
                @if(!empty($prevPost->image))
                    <img src="{{ asset('storage/product/'.$prevPost->image) }}" class="img-thumbnail" style="max-width: 200px;" alt="Thumbnail">
                @else
                    <img src="{{ asset('images/no-image.jpg') }}" class="img-thumbnail" style="max-width: 200px;" alt="Default">
                @endif
            </div>
            <small class="text-muted d-block mt-1">Suggested size: 300×475 px</small>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

@endif
