@extends('frontend.layouts.main')
@section('main-content')
   
<style>
.form-select {
    padding: 12px;
    border-radius: 5px;
    width: 100%;
    font-size: 16px; /* Increase font size here */
}
    .form-control {
        padding: 19px;
        border-radius: 5%;
        font-size:16px;

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
    @foreach ($categories as $category)
        <label class="d-block">
            <input type="checkbox" name="categories[]" value="{{ strtoupper(str_replace(' ', '_', $category->id)) }}" class="category-checkbox">
            {{ strtoupper($category->name) }}
        </label>
    @endforeach
</div>

        </div>

        <!-- Product Listing -->
        <div class="col-lg-9 col-md-8">
            <div class="search-filters mb-4 p-3 bg-light rounded">
                <input type="text" id="product_name" class="form-control" placeholder="Product Name">
              <select id="company_name" class="form-select">
    <option value="">Select Any Company</option>
    @foreach($manufacturers as $manufacturer)
        <option value="{{ $manufacturer }}">{{ $manufacturer }}</option>
    @endforeach
</select>

                <select id="composition" class="form-select">
                        <option value="">Select any compostion</option>

                       @foreach($compositions as $composition)
        <option value="{{ $composition }}">{{ $composition }}</option>
    @endforeach
                </select>
                <span class="search-icon-advanced">Search</span>
            </div>
            
            <div id="product-listing" class="row">
                <!-- Product cards -->
               
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        // Function to fetch products via AJAX
        function fetchProducts() {
            let selectedCategories = [];

            // Gather all checked categories
            $('.category-checkbox:checked').each(function () {
                selectedCategories.push($(this).val());
            });

            let productName = $('#product_name').val();
            let companyName = $('#company_name').val();
            let composition = $('#composition').val();

            // AJAX request to fetch filtered products
            $.ajax({
                url: "{{ route('advanced.selection') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    categories: selectedCategories,
                    product_name: productName,
                    company_name: companyName,
                    composition: composition
                },
                success: function (response) {
                    $('#product-listing').html(response.html);
                },
                error: function (xhr) {
                    console.error(xhr.responseText);
                }
            });
        }

        // 🔁 1. Trigger when checkbox is changed
        $('.category-checkbox').on('change', function () {
            fetchProducts();
        });

        // 🔁 2. Trigger when search button is clicked
        $('.search-icon-advanced').on('click', function () {
            fetchProducts();
        });

        // 🔁 3. Trigger when dropdowns are changed
        $('#company_name, #composition').on('change', function () {
            fetchProducts();
        });

        // 🔁 4. Trigger on typing with debounce
        let typingTimer;
        $('#product_name').on('input', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(function () {
                fetchProducts();
            }, 200); // 300ms delay after last keystroke
        });

        // 🔁 5. Fire on initial page load
        fetchProducts();
    });
</script>



@endsection