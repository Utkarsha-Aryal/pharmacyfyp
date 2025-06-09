<header>
    <!-- Header Start -->
    <div class="header-area header-transparent">
        <div class="main-header header-sticky">
            <div class="container-fluid">
                <!-- Top Section: Logo, Search, Buttons -->
                <div class="top-header d-flex align-items-center justify-content-between flex-wrap">
                    
                    <!-- LEFT SIDE: Logo + Search Bar + Buttons -->
                    <div class="left-section d-flex align-items-center flex-wrap">
                        <!-- Logo -->
                        <div class="logo me-3">
                            <a href="{{ route('home') }}">
                                <img src="{{ asset('assets/img/logo/pharmacy.png') }}" alt="Logo">
                            </a>
                        </div>

                        <!-- Search Bar Section -->
                        <div class="search-bar-wrapper">
                            <form class="search-bar-form">
                                <!-- Search Input -->
                                <input
                                    type="text"
                                    name="query"
                                    class="search-input"
                                    placeholder="Search for medicine / Beauty products"
                                />

                                <!-- Combined Advanced + Search Icon Button -->
                                <button type="submit" class="combined-search-btn">
                                    <span class="advanced-label">Advanced Search</span>
                                    <span class="search-icon">🔍</span>
                                </button>
                            </form>
                        </div>

                        <!-- Upload Prescription Button -->
                        <div class="upload-prescription ms-3">
                            <button class="upload-btn">
                                <i class="fa fa-camera"></i> Upload Prescription
                            </button>
                        </div>

                        <!-- Account and Cart Icons -->
                        <div class="icon-buttons d-flex align-items-center gap-3 ms-3">
                            <!-- Account -->
                            <a href="#" class="icon-btn" aria-label="User Account">
                                <i class="fas fa-user"></i>
                            </a>

                            <!-- Cart -->
                            <a href="{{route('carts')}}" class="icon-btn" aria-label="Shopping Cart">
                                <i class="fas fa-shopping-cart"></i>
                            </a>
                        </div>
                    </div>
                </div>
<hr class="header-divider">
                <!-- Navigation Section -->
                <div class="main-menu mt-3 d-none d-lg-block text-center">
                    <nav>
                        <ul id="navigation" class="d-flex justify-content-center flex-wrap gap-4">
                            <li>
                                <a href="{{ route('home') }}" class="nav-img-item text-center">
                                    <img src="{{ asset('assets/img/Navigation/surgical.png') }}" alt="Surgical Appliances" class="nav-icon-img">
                                    <div class="nav-label">SURGICAL APPLIANCES</div>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('home') }}" class="nav-img-item text-center">
                                    <img src="{{ asset('assets/img/Navigation/surgical.png') }}" alt="Vaccine" class="nav-icon-img">
                                    <div class="nav-label">VACCINE, ANTISERA, & IMMUNOLOGICALS</div>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('home') }}" class="nav-img-item text-center">
                                    <img src="{{ asset('assets/img/Navigation/insulin.png') }}" alt="Insulins" class="nav-icon-img">
                                    <div class="nav-label">INSULINS</div>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('home') }}" class="nav-img-item text-center">
                                    <img src="{{ asset('assets/img/Navigation/OTC.png') }}" alt="OTC" class="nav-icon-img">
                                    <div class="nav-label">OTC</div>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('home') }}" class="nav-img-item text-center">
                                    <img src="{{ asset('assets/img/Navigation/Medicaldevice.png') }}" alt="Medical Devices" class="nav-icon-img">
                                    <div class="nav-label">MEDICAL DEVICES</div>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('home') }}" class="nav-img-item text-center">
                                    <img src="{{ asset('assets/img/Navigation/Cosmetic.png') }}" alt="Cosmetics" class="nav-icon-img">
                                    <div class="nav-label">COSMETICS</div>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('home') }}" class="nav-img-item text-center">
                                    <img src="{{ asset('assets/img/Navigation/Ayurbedic.png') }}" alt="Ayurvedic" class="nav-icon-img">
                                    <div class="nav-label">AYURVEDIC & HERBALS</div>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('home') }}" class="nav-img-item text-center">
                                    <img src="{{ asset('assets/img/Navigation/surgical.png') }}" alt="More" class="nav-icon-img">
                                    <div class="nav-label">MORE +</div>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>

                

                <!-- Mobile Menu -->
                <div class="col-12">
                    <div class="mobile_menu d-block d-lg-none"></div>
                </div>

            </div>
        </div>
        <hr class="header-divider">
    </div>
</header>
