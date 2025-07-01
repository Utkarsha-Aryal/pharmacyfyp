    <!-- app-header -->
    <header class="app-header">

        <!-- Start::main-header-container -->
        <div class="main-header-container container-fluid" style="height: 4rem;">

            <!-- Start::header-content-left -->
            <div class="header-content-left">

                <!-- Start::header-element -->
                <div class="header-element">
                    <div class="horizontal-logo">
                        <a href="index.html" class="header-logo">
                            <img src="{{ asset('backpanel/assets/images/brand-logos/desktop-logo.png') }}" alt="logo"
                                class="desktop-logo">
                            <img src="{{ asset('backpanel/assets/images/brand-logos/toggle-logo.png') }}" alt="logo"
                                class="toggle-logo">
                            <img src="{{ asset('backpanel/assets/images/brand-logos/desktop-white.png') }}"
                                alt="logo" class="desktop-white">
                            <img src="{{ asset('backpanel/assets/images/brand-logos/toggle-white.png') }}"
                                alt="logo" class="toggle-white">
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
                    <span>Welcome! </span><a
                        href=""><label></label></a>
                </div>
                <!-- Start::header-link|dropdown-toggle -->
                <div>
                    <a href=""> <img
                            src="@if (!empty($userProfile->image)) {{ asset('storage/profile') . '/' . $userProfile->image }} @else {{ asset('/no-user.jpg') }} @endif"
                            class="rounded-circle" alt="School Logo "width="37" height="37"></a>
{{-- 
                    @if (!empty($userData['image']))
                        <img alt="User Image"class='profile_image'
                            src="{{ asset('/storage/profile') . '/' . $userData['image'] }}"
                            style="width: 37; height: 37; border-radius: 50%">
                    @else
                        <img alt="No User"class='profile_image' src="{{ asset('/no-user.jpg') }}  style="width: 37;
                            height: 37; border-radius: 50%"">
                    @endif --}}
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
                                <h6 class="mb-0 lh-1 text-fixed-white"></h6><span
                                    class="fs-11 op-7 lh-1"></span>
                            </div>
                        </div>
                    </li>
                    <li><a class="dropdown-item d-flex" href=""><i
                                class="bx bx-user-circle fs-18 me-2 op-7"></i>Profile</a></li>

                    <li><a class="dropdown-item d-flex" href=""><i
                                class="bx bx-log-out fs-18 me-2 op-7"></i>Sign Out</a></li>
                </ul>
            </div>
        </div>
        <!-- End::main-header-container -->

    </header>
    <!-- /app-header -->
