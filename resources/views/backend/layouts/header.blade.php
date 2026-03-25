    @php
        $currentUser = auth()->user();
    @endphp

    <!-- app-header -->
    <header class="app-header">

        <!-- Start::main-header-container -->
        <div class="main-header-container container-fluid" style="height: 4rem;">

            <!-- Start::header-content-left -->
            <div class="header-content-left">

                <!-- Start::header-element -->
                <div class="header-element">
                    <div class="horizontal-logo">
                        <a href="{{ route('admin.dashboard') }}" class="header-logo">
                            <img src="{{ asset('assets/img/logo/pharmacy.png') }}" alt="logo" class="desktop-logo">
                            <img src="{{ asset('assets/img/logo/pharmacy.png') }}" alt="logo" class="toggle-logo">
                            <img src="{{ asset('assets/img/logo/pharmacy.png') }}" alt="logo" class="desktop-white">
                            <img src="{{ asset('assets/img/logo/pharmacy.png') }}" alt="logo" class="toggle-white">
                        </a>
                    </div>
                </div>
                <!-- End::header-element -->

                <!-- Start::header-element -->
                <div class="header-element">
                    <!-- Start::header-link -->
                    <a aria-label="Hide Sidebar"
                        class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle"
                        data-bs-toggle="sidebar" href="javascript:void(0);">
                        <i class="header-icon fe fe-align-left"></i>
                    </a>
                    <!-- End::header-link -->
                </div>


            </div>


            <!-- End::header-link|dropdown-toggle -->

            <div class="header-element headerProfile-dropdown">
                <div class="me-3">
                    <span>Welcome! </span><a href="{{ route('admin.dashboard') }}"><label>{{ $currentUser?->name }}</label></a>
                </div>
                <!-- Start::header-link|dropdown-toggle -->
                <div>
                    <a href="{{ route('admin.dashboard') }}"> <img
                            src="{{ asset('images/no-user.jpg') }}"
                            class="rounded-circle" alt="User" width="37" height="37"></a>
                </div>
                <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i class="fa-solid fa-angle-down"></i>
                </a>
                <!-- End::header-link|dropdown-toggle -->
                <ul class="main-header-dropdown dropdown-menu pt-0 header-profile-dropdown dropdown-menu-end main-profile-menu"
                    aria-labelledby="mainHeaderProfile">
                    <li>
                        <div class="main-header-profile bg-primary menu-header-content text-fixed-white">
                            <div class="my-auto">
                                <h6 class="mb-0 lh-1 text-fixed-white">{{ $currentUser?->name }}</h6><span
                                    class="fs-11 op-7 lh-1">{{ $currentUser?->role }}</span>
                            </div>
                        </div>
                    </li>
                    <li><a class="dropdown-item d-flex" href="{{ route('admin.dashboard') }}"><i
                                class="bx bx-user-circle fs-18 me-2 op-7"></i>Dashboard</a></li>

                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item d-flex border-0 bg-transparent w-100 text-start">
                                <i class="bx bx-log-out fs-18 me-2 op-7"></i>Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
        <!-- End::main-header-container -->

    </header>
    <!-- /app-header -->
