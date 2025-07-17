<!-- resources/views/frontend/advanced/product_card.blade.php -->
@forelse($products as $product)
    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
        <div class="product-card">
            <img src="{{ asset('/storage/product/' . $product->image) }}" alt="{{ $product->product_name }}" class="product-img">
            <h4 class="product-title">{{ strtoupper($product->product_name) }}</h4>
            <p class="product-description">{!! $product->description ?? 'No description available.' !!}</p>

            @php
                $mrp = $product->mrp;
                $discount = $product->discount;
                $price = $mrp - ($mrp * $discount / 100);
            @endphp

            <div class="price-section">
                <span class="old-price">Rs {{ number_format($mrp, 2) }}</span>
                <span class="new-price">Rs {{ number_format($price, 2) }}</span>
            </div>

            <button class="add-to-cart-btn">Add to Cart</button>
        </div>
    </div>
@empty
    <div class="col-12">
        <p>No products found for the selected category.</p>
    </div>
@endforelse
