<header class="header-area">
    <!-- Wrapper -->
    <div class="header-area header-transparent">
        <div class="main-header header-sticky">
            <div class="container-fluid">

<!-- 🔹 Mobile Top Bar with Icons -->
<div class="d-flex justify-content-between align-items-center d-lg-none py-1">
    <!-- Logo -->
    <div class="logo d-flex align-items-center">
        <a href="{{ route('home') }}">
            <img src="{{ asset('assets/img/logo/pharmacy.png') }}" alt="Logo">
        </a>
    </div>

    <!-- Action Icons: Login, Register, Account, Cart -->
    <div class="mobile-icons d-flex align-items-center gap-2">
        <div class="auth-links d-flex align-items-center gap-1">
            <a href="{{ route('login') }}" class="simple-link">Login</a>
            <span>/</span>
            <a href="#" class="simple-link">Register</a>
        </div>
        <a href="#" class="icon-btn" aria-label="User Account"><i class="fas fa-user"></i></a>
        <a href="{{route('carts')}}" class="icon-btn" aria-label="Shopping Cart"><i class="fas fa-shopping-cart"></i></a>
    </div>

    <!-- Mobile Menu Toggle -->
    <button class="mobile-toggle-btn d-lg-none" id="mobileToggleBtn" aria-label="Toggle Menu">
        <i class="fas fa-bars fa-2x"></i>
    </button>
</div>

                <!-- 🔹 Desktop Header (left-section) -->
                <div class="top-header d-none d-lg-flex align-items-center justify-content-between flex-wrap" id="topHeader">
                    <div class="left-section d-flex align-items-center flex-wrap">
                        <!-- Logo -->
                        <div class="logo me-3">
                            <a href="{{ route('home') }}">
                                <img src="{{ asset('assets/img/logo/pharmacy.png') }}" alt="Logo">
                            </a>
                        </div>


                        <!-- Search Bar -->
                        <div class="search-bar-wrapper">
                            <form class="search-bar-form">
                                <input type="text" name="query" id="global-search-input" class="search-input" placeholder="Search for medicine / Beauty products" />
                                    <div id="global-search-results" class="list-group position-absolute w-100" style="z-index: 9999; display: none;"></div>

                                <a href="{{route('advanced')}}" class="combined-search-btn">
                                    <span class="advanced-label">Advanced Search</span>
                                    <span class="search-icon">🔍</span>
                                </a>
                            </form>
                        </div>

                        <!-- Upload Button -->
                        <div class="upload-prescription ms-3">
                            <button class="upload-btn">
                                <i class="fa fa-camera"></i> Upload Prescription
                            </button>
                        </div>

                        <!-- Icons -->
                        <div class="icon-buttons d-flex align-items-center gap-3 ms-3">
                            <div class="auth-links d-flex align-items-center gap-2">
                                <a href="{{ route('login') }}" class="simple-link">Login</a>
                                <h2>/</h2>
                                <a href="#" class="simple-link">Register</a>
                            </div>
                            <a href="#" class="icon-btn" aria-label="User Account"><i class="fas fa-user"></i></a>
                            <a href="{{route('carts')}}" class="icon-btn" aria-label="Shopping Cart"><i class="fas fa-shopping-cart"></i></a>
                        </div>
                    </div>
                </div>

                <!-- 🔹 Mobile Expandable Section -->
                <div class="mobile-expand-area d-lg-none mt-3" id="mobileExpandArea">
                    <div class="left-section flex-column">
                        <!-- Search Bar -->
                        <div class="search-bar-wrapper w-100 mb-3">
                            <form class="search-bar-form d-flex">
                                <input type="text" name="query" class="search-input" placeholder="Search for medicine / Beauty products" />
                                <a href="{{route('advanced')}}" class="combined-search-btn">
                                    <span class="advanced-label">Advanced Search</span>
                                    <span class="search-icon">🔍</span>
                                </a>
                            </form>
                        </div>

                        <!-- Upload Button -->
                        <div class="upload-prescription w-100 mb-3">
                            <button class="upload-btn w-100">
                                <i class="fa fa-camera"></i> Upload Prescription
                            </button>
                        </div>


                    </div>
                </div>
<!-- 🔹 Navigation Section -->
<hr class="header-divider d-none d-lg-block">
<div class="main-menu mt-3 text-center" id="mobileNavigation">
    <nav>
        <!-- ✅ Scrollable container wrapper -->
        @php
    $visibleCategories = $categories->take(7);
    $extraCategories = $categories->slice(7);
@endphp

<div class="scrollable-nav-wrapper">
    <ul id="navigation" class="d-flex justify-content-start flex-nowrap">
        @foreach($visibleCategories as $category)
            <li>
                <a href="" class="nav-img-item">
                    <img src="{{ asset('/storage/category/' . $category->image) }}" alt="{{ $category->category_name }}" class="nav-icon-img">
                    <div class="nav-label">{{ strtoupper($category->name) }}</div>
                </a>
            </li>
        @endforeach

        @if($extraCategories->count())
            <li class="dropdown">
                <a href="#" class="nav-img-item dropdown-toggle" data-bs-toggle="dropdown">
                    <img src="{{ asset('assets/img/Navigation/more.png') }}" alt="More" class="nav-icon-img">
                    <div class="nav-label">MORE +</div>
                </a>
                <ul class="dropdown-menu">
                    @foreach($extraCategories as $category)
                        <li>
                            <a class="dropdown-item" href="">
                                {{ $category->category_name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>
        @endif
    </ul>
</div>

    </nav>
                <hr class="header-divider">

            </div>
        </div>
    </div>
    <script>
    const toggleBtn = document.getElementById("mobileToggleBtn");
    const expandArea = document.getElementById("mobileExpandArea");
    const mobileNav = document.getElementById("mobileNavigation");

    toggleBtn.addEventListener("click", function () {
        const isShown = expandArea.style.display === "block";
        expandArea.style.display = isShown ? "none" : "block";
        mobileNav.style.display = isShown ? "block" : "none";
    });
</script>
<style>
@media (max-width: 991.98px) {
  .scrollable-nav-wrapper {
    overflow-x: auto;
    white-space: nowrap;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 6px;
  }

  .nav-img-item {
    display: inline-block;
    text-align: center;
    width: 80px; /* Adjust width for mobile */
  }

  .nav-icon-img {
    width: 40px;
    height: 40px;
    object-fit: contain;
  }

  .nav-label {
    display: block;
    font-size: 10px;
    line-height: 1.2;
    margin-top: 4px;

    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%; /* essential */
  }
}
</style>

</header>
