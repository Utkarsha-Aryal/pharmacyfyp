@extends('frontend.layouts.main')
@section('main-content')
   
<style>
    .form-select {
        padding: 12px;
        border-radius: 5px;
        width: 100%;
        font-size:10px;
    }
    .form-control {
        padding: 19px;
        border-radius: 5%;
        font-size:10px;

    }
        .category-title {
            color: white;
            font-weight: bold;
            padding: 10px;
            margin-bottom: 0;
            text-align: center;
            background: teal;
        }

    .product-img {
        width: 100%;
        height: 200px;
        object-fit: contain;
        margin-bottom: 15px;
    }

    .product-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        background: white;
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 15px;
        transition: transform 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .search-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
        font-size:20px;
    }

    .search-filters > * {
        flex: 1 1 200px;
        min-width: 150px;
    }

    .category-list {
        max-height: 500px;
        overflow-y: auto;
        padding-right: 10px;
    }

    .category-list label {
        display: block;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }

    .product-title {
        font-size: 1.1rem;
        margin-bottom: 10px;
        min-height: 50px;
    }

    .product-description {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 15px;
        flex-grow: 1;
    }

    .price-section {
        margin-bottom: 15px;
    }

    .old-price {
        text-decoration: line-through;
        color: #999;
        margin-right: 10px;
    }

    .new-price {
        font-weight: bold;
        color: teal;
    }

    .add-to-cart-btn {
        background-color:teal;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s;
        width: 100%;
    }

    .add-to-cart-btn:hover {
        background-color: #teal;
    }

    .search-icon-advanced {
        display: flex;
        align-items: center;
        justify-content: center;
        background: teal;
        color: white;
        padding: 0 15px;
        border-radius: 5px;
        cursor: pointer;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .col-md-3 {
            margin-bottom: 30px;
        }
        
        .product-img {
            height: 180px;
        }
    }

    @media (max-width: 768px) {
        .search-filters > * {
            flex: 1 1 100%;
        }
        
        .product-img {
            height: 160px;
        }
        
        .product-title {
            font-size: 1rem;
            min-height: auto;
        }
        
        .category-title {
            font-size: 1.3rem;
        }
    }

    @media (max-width: 576px) {
        .product-card {
            padding: 10px;
        }
        
        .product-img {
            height: 140px;
        }
        
        .product-description {
            font-size: 0.8rem;
        }
        
        .add-to-cart-btn {
            padding: 8px;
            font-size: 0.9rem;
        }
    }
</style>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar with Categories -->
        <div class="col-lg-3 col-md-4">
            <h3 class="category-title">Categories</h3>
            <div class="category-list">
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="SURGICAL_APPLIANCES" class="category-checkbox"> SURGICAL/APPLIANCES
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="LV_FLUIDS" class="category-checkbox"> LV. FLUIDS
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="CARDIAC_DRUGS" class="category-checkbox"> CARDIAC DRUGS
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="ANTI_MICROBIALS" class="category-checkbox"> ANTI-MICROBIALS
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="VACCINES_ANTISERA" class="category-checkbox"> VACCINES, ANTISERA & IMMUNOLOGICALS
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="INSULIN" class="category-checkbox"> INSULIN
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="GTC" class="category-checkbox"> GTC
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="MEDICAL_DEVICES" class="category-checkbox"> MEDICAL DEVICES
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="COSMETICS" class="category-checkbox"> COSMETICS
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="AVURVEIUC_HERBAL" class="category-checkbox"> AVURVEIUC & HERBAL ITEMS
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="NEUTRACEUTICALS" class="category-checkbox"> NEUTRACEUTICALS
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="TOILETRIES" class="category-checkbox"> TOILETRIES
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="ANAESTHETICS" class="category-checkbox"> ANAESTHETICS
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="PSYCHOTROPIC" class="category-checkbox"> PSYCHOTROPIC SCHEDULE III/V
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="ONCO_MEDICINE" class="category-checkbox"> ONCO MEDICINE
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="OPTHALMICS" class="category-checkbox"> OPTHALMICS
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="HORMONES" class="category-checkbox"> HORMONES
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="ANTIDOTE" class="category-checkbox"> ANTIDOTE
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="NEURO_MEDICINE" class="category-checkbox"> NEURO MEDICINE
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="CONTRACT_MEDIA" class="category-checkbox"> CONTRACT MEDIA
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="ANTI_DIABETIC_ANTI_VIRAL" class="category-checkbox"> ANTI-DIABETIC ANTI-VIRAL
                </label>
                <label class="d-block">
                    <input type="checkbox" name="categories[]" value="VITAMINS_MINERALS" class="category-checkbox"> VITAMINS AND MINERALS
                </label>
            </div>
        </div>

        <!-- Product Listing -->
        <div class="col-lg-9 col-md-8">
            <div class="search-filters mb-4 p-3 bg-light rounded">
                <input type="text" id="product_name" class="form-control" placeholder="Product Name">
                <select id="company_name" class="form-select">
                    <option value="">Select Company</option>
                    <option value="W">W</option>
                    <option value="Pfizer">Pfizer</option>
                    <option value="Cipla">Cipla</option>
                </select>
                <select id="composition" class="form-select">
                    <option value="">Select Composition</option>
                    <option value="Paracetamol">Paracetamol</option>
                    <option value="Amoxicillin">Amoxicillin</option>
                    <option value="Ibuprofen">Ibuprofen</option>
                </select>
                <span class="search-icon-advanced">🔍</span>
            </div>
            
            <div id="product-listing" class="row">
                <!-- Product cards -->
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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
                
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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

                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
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
            </div>
        </div>
    </div>
</div>
@endsection