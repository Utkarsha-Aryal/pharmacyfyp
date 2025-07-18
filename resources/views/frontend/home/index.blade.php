@extends('frontend.layouts.main')

@section('main-content')
<main>
    <!--? Slider Area Start -->
    <div id="slider-area" class="slider-area position-relative overflow-hidden slider-height">
    <div class="slider-wrapper">
        <img src="{{ asset('assets/img/pharmacy/pharmacy.jpg') }}" class="slider-img active" />
        <img src="{{ asset('assets/img/pharmacy/pharmacy2.jpg') }}" class="slider-img" />
        <img src="{{ asset('assets/img/pharmacy/pharmacy3.jpg') }}" class="slider-img" />
        <img src="{{ asset('assets/img/pharmacy/pharmacy2.jpg') }}" class="slider-img" />
        <img src="{{ asset('assets/img/pharmacy/pharmacy3.jpg') }}" class="slider-img" />
    </div>
    <!-- ⬅️ Prev / ➡️ Next buttons -->
    <button class="slider-btn prev-btn">&#10094;</button>
    <button class="slider-btn next-btn">&#10095;</button>
</div>

    <!-- Slider Area End -->
</main>

<!-- 🔁 Auto Looping Background Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const images = document.querySelectorAll('.slider-img');
        let current = 0;

        setInterval(() => {
            const next = (current + 1) % images.length;

            // Remove classes
            images.forEach((img, i) => {
                img.classList.remove('active', 'previous');
                if (i === current) img.classList.add('previous');
            });

            // Slide in next image
            images[next].classList.add('active');

            current = next;
        }, 5000);
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const images = document.querySelectorAll('.slider-img');
        const prevBtn = document.querySelector('.prev-btn');
        const nextBtn = document.querySelector('.next-btn');

        let current = 0;
        let autoSlide = setInterval(nextSlide, 5000); // Auto-loop every 5 sec

        function showSlide(next) {
            images.forEach((img, i) => {
                img.classList.remove('active', 'previous');
                if (i === current) img.classList.add('previous');
            });
            images[next].classList.add('active');
            current = next;
        }

        function nextSlide() {
            let next = (current + 1) % images.length;
            showSlide(next);
        }

        function prevSlide() {
            let prev = (current - 1 + images.length) % images.length;
            showSlide(prev);
        }

        nextBtn.addEventListener('click', () => {
            nextSlide();
            resetAutoSlide();
        });

        prevBtn.addEventListener('click', () => {
            prevSlide();
            resetAutoSlide();
        });

        function resetAutoSlide() {
            clearInterval(autoSlide);
            autoSlide = setInterval(nextSlide, 5000);
        }
    });
</script>

<hr class="header-divider">
    <!-- Products section start -->
    <section class="product-section white-bg section-padding30">
    <div class="container">
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0" style="color: black;">Trending</h1>
    <a href="#" class="view-more-link">View More &raquo;</a>
</div>
        <div class="row mb-5">
            <!-- Card structure -->
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="product-card">
                    <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Product" class="product-img">

                    <h4 class="product-title">HOT BAG</h4>
                    <p class="product-description">Heating Bag Electric, Heating Gel Pad Heat Pouch Hot Water Bottle Bag.</p>
                    <div class="price-section">
                        <span class="old-price">Rs 399.00</span>
                        <span class="new-price">Rs 300.00</span>
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="product-card">
                    <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Product" class="product-img">

                    <h4 class="product-title">HOT BAG</h4>
                    <p class="product-description">Heating Bag Electric, Heating Gel Pad Heat Pouch Hot Water Bottle Bag.</p>
                    <div class="price-section">
                        <span class="old-price">Rs 399.00</span>
                        <span class="new-price">Rs 300.00</span>
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="product-card">
                    <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Product" class="product-img">

                    <h4 class="product-title">HOT BAG</h4>
                    <p class="product-description">Heating Bag Electric, Heating Gel Pad Heat Pouch Hot Water Bottle Bag.</p>
                    <div class="price-section">
                        <span class="old-price">Rs 399.00</span>
                        <span class="new-price">Rs 300.00</span>
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="product-card">
                    <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Product" class="product-img">

                    <h4 class="product-title">HOT BAG</h4>
                    <p class="product-description">Heating Bag Electric, Heating Gel Pad Heat Pouch Hot Water Bottle Bag.</p>
                    <div class="price-section">
                        <span class="old-price">Rs 399.00</span>
                        <span class="new-price">Rs 300.00</span>
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            
            <!-- Repeat other cards -->
        </div>
        <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0" style="color: black;">Special Offer Products</h1>
    <a href="#" class="view-more-link">View More &raquo;</a>
</div>
        <div class="row mb-5">
            <!-- Card structure -->
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="product-card">
                    <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Product" class="product-img">

                    <h4 class="product-title">HOT BAG</h4>
                    <p class="product-description">Heating Bag Electric, Heating Gel Pad Heat Pouch Hot Water Bottle Bag.</p>
                    <div class="price-section">
                        <span class="old-price">Rs 399.00</span>
                        <span class="new-price">Rs 300.00</span>
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="product-card">
                    <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Product" class="product-img">

                    <h4 class="product-title">HOT BAG</h4>
                    <p class="product-description">Heating Bag Electric, Heating Gel Pad Heat Pouch Hot Water Bottle Bag.</p>
                    <div class="price-section">
                        <span class="old-price">Rs 399.00</span>
                        <span class="new-price">Rs 300.00</span>
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="product-card">
                    <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Product" class="product-img">

                    <h4 class="product-title">HOT BAG</h4>
                    <p class="product-description">Heating Bag Electric, Heating Gel Pad Heat Pouch Hot Water Bottle Bag.</p>
                    <div class="price-section">
                        <span class="old-price">Rs 399.00</span>
                        <span class="new-price">Rs 300.00</span>
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="product-card">
                    <img src="{{ asset('assets/img/product/hotbag.png') }}" alt="Product" class="product-img">

                    <h4 class="product-title">HOT BAG</h4>
                    <p class="product-description">Heating Bag Electric, Heating Gel Pad Heat Pouch Hot Water Bottle Bag.</p>
                    <div class="price-section">
                        <span class="old-price">Rs 399.00</span>
                        <span class="new-price">Rs 300.00</span>
                    </div>
                    <button class="add-to-cart-btn">Add to Cart</button>
                </div>
            </div>
            
            <!-- Repeat other cards -->
        </div>
@foreach($categories as $category)
    @if($category->products->count())
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0" style="color: black;">{{ strtoupper($category->name) }}</h1>
            <a href="#" class="view-more-link">View More &raquo;</a>
        </div>        

        <div class="row mb-5">
            @foreach($category->products as $product)
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="product-card">

                        <img src="{{ asset('/storage/product/' . $product->image) }}" alt="{{ $product->product_name }}" class="product-img">

                        <h4 class="product-title">{{ $product->product_name }}</h4>
                        <p class="product-description">{!! $product->description !!}</p>

                        <div class="price-section">
                            @if($product->previous_price)
                                <span class="old-price">Rs {{ number_format($product->previous_price, 2) }}</span>
                            @endif
                            <span class="new-price">Rs {{ number_format($product->final_price , 2) }}</span>
                        </div>

                        <button class="add-to-cart-btn" data-id="{{ $product->id }}">Add to Cart</button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endforeach


    </div>
    
</section>


</main>
@endsection