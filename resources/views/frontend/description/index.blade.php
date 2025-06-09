@extends('frontend.layouts.main')

@section('main-content')
<style>
  /* Make sure product description is visible only here */
  .product-description {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    max-height: none !important;
  }

  
</style>

<section class="product-detail-section py-5">
    <div class="container">
        <div class="row">
            <!-- Left: Product Image and Description -->
            <div class="col-lg-8">
                <div class="product-card p-4 shadow-sm rounded bg-white mb-4">
                    <div class="row">
                        <div class="col-md-5 text-center">
                            <img src="{{ asset('assets/img/product/hotbag.png') }}" class="img-fluid rounded" alt="Hot Water Bag">
                        </div>
                        <div class="col-md-7">
                            <h3 class="product-title">HOT WATER BAG</h3>
                            <p><strong>Composition:</strong> HOT WATER BAG</p>
                            <p><strong>Group Name:</strong> HOT AND COLD PACK</p>
                            <p><strong>Manufacturer:</strong> CORONATION</p>
                            <p class="product-price">
                                <span class="old-price">Rs 399.00</span>
                                <span class="text-success fs-5 fw-bold ms-2">Rs 300.00</span>
                            </p>
                            <button class="add-to-cart-btn">Add to Cart</button>

                        </div>
                    </div>
                    <div class="mt-4 product-description">
                        <h3 class="text-dark">Product Description:</h3>
                        <ul>
                            <li>Runs on electricity – no hassles of heating water on gas or heaters.</li>
                            <li>You can keep it inside the quilt to heat up the quilt or use it for massaging body parts.</li>
                            <li>Very useful product for the winters.</li>
                            <li>Sustainable insulation charge: 6-10 minutes, 2-5 hours of warmth.</li>
                            <li>Electric hot water bag contains pre-filled solution, no need to refill.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right: Substitute Suggestions -->
            <div class="col-lg-4">
    <div class="substitutes-section p-0 shadow-sm rounded bg-white">
        <h3 class="mb-0 text-center text-white py-2" style="background-color: #009688;">SUBSTITUTES</h3>

        <!-- Start of product card -->
        <div class="substitute-item d-flex border-bottom p-3 align-items-center">
            <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Cast Cover Arm"
                class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover;">
            <div class="flex-grow-1">
                <p class="mb-1 fw-bold">CAST COVER ARM</p>
                <p class="mb-2 text-muted small">Heating Bag Electric, Heating Gel Pad-Heat Pouch Hot Water Bottle Bag.</p>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="old-price">Rs 399.00</span>
                        <span class="text-success fw-bold">Rs 300.00</span>
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
        </div>
        <!-- Repeat the product card -->
        <div class="substitute-item d-flex border-bottom p-3 align-items-center">
            <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Cast Cover Arm"
                class="img-thumbnail me-3" style="width: 80px; height: 80px; object-fit: cover;">
            <div class="flex-grow-1">
                <p class="mb-1 fw-bold">CAST COVER ARM</p>
                <p class="mb-2 text-muted small">Heating Bag Electric, Heating Gel Pad-Heat Pouch Hot Water Bottle Bag.</p>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="old-price">Rs 399.00</span>
                        <span class="text-success fw-bold">Rs 300.00</span>
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
        </div>
        <!-- Add more product cards here if needed -->

        <div class="text-center py-3">
            
            <button class="add-to-cart-btn">View More</button>
        </div>
    </div>
</div>

        </div>
    </div>
</section>

@endsection
