<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="light" data-toggled="close">

@php
    $adminCustomCssVersion = file_exists(public_path('backpanel/assets/css/custom.css'))
        ? filemtime(public_path('backpanel/assets/css/custom.css'))
        : time();
    $adminCustomJsVersion = file_exists(public_path('backpanel/assets/js/custom.js'))
        ? filemtime(public_path('backpanel/assets/js/custom.js'))
        : time();
@endphp

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title') | {{ setting('app_name', 'Pharmacy Management System') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ app_favicon_url() }}" type="image/x-icon">
    <!-- Choices JS -->
    <script src="{{ asset('backpanel/assets/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>

    <!-- Main Theme Js -->
    <script src="{{ asset('backpanel/assets/js/main.js') }}"></script>

    <!-- Bootstrap Css -->
    <link id="style" href="{{ asset('backpanel/assets/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Style Css -->
    <link href="{{ asset('backpanel/assets/css/styles.min.css') }}" rel="stylesheet">
    <link href="{{ asset('backpanel/assets/css/custom.css') }}?v={{ $adminCustomCssVersion }}" rel="stylesheet">

    <!-- Icons Css -->
    <link href="{{ asset('backpanel/assets/css/icons.css') }}" rel="stylesheet">

    <!-- Node Waves Css -->
    <link href="{{ asset('backpanel/assets/libs/node-waves/waves.min.css') }}" rel="stylesheet">

    <!-- Simplebar Css -->
    <link href="{{ asset('backpanel/assets/libs/simplebar/simplebar.min.css') }}" rel="stylesheet">

    <!-- Color Picker Css -->
    <link rel="stylesheet" href="{{ asset('backpanel/assets/libs/flatpickr/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backpanel/assets/libs/@simonwep/pickr/themes/nano.min.css') }}">

    <!-- Choices Css -->
    <link rel="stylesheet" href="{{ asset('backpanel/assets/libs/choices.js/public/assets/styles/choices.min.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Jsvector Maps -->
    <link rel="stylesheet" href="{{ asset('backpanel/assets/libs/jsvectormap/css/jsvectormap.min.css') }}">

    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.0-rc.2/dist/quill.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.0-rc.2/dist/quill.snow.css" rel="stylesheet">
    <!-- Nepali date picker -->
    <link href="https://nepalidatepicker.sajanmaharjan.com.np/nepali.datepicker/css/nepali.datepicker.v4.0.1.min.css"
        rel="stylesheet" type="text/css" />

    <link rel="stylesheet" href="{{ asset('backpanel/assets/css/fontawesome-iconpicker.min.css') }}">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- cropper -->
    <link href="{{ asset('backpanel/assets/css/cropper/cropper.min.css') }}" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker.css"
        rel="stylesheet" />

    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{ asset('backpanel/assets/libs/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backpanel/assets/libs/toastr/toastr.min.css') }}">

    @yield('styles')

</head>

<body class="@yield('body-class')" data-page="@yield('title')">

    <!-- Loader with Background Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-overlay-inner">
            <div class="spinner-border spinner-border-lg  text-danger" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    <!-- Loader with Background Overlay -->


    <div class="page">

        <!-- app-header -->
        @include('layouts.header')
        <!-- /app-header -->
        <!-- Start::app-sidebar -->
        @include('layouts.sidebar')
        <!-- End::app-sidebar -->

        <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">
                @yield('main-content')
            </div>
        </div>
        <!-- End::app-content -->

        <!-- Footer Start -->
        @include('layouts.footer')
        <!-- Footer End -->

    </div>


    <!-- Scroll To Top -->
    <div class="scrollToTop">
        <span class="arrow"><i class="las la-angle-double-up"></i></span>
    </div>
    <div id="responsive-overlay"></div>
    <!-- Scroll To Top -->

    <!-- Popper JS -->
    <script src="{{ asset('backpanel/assets/libs/@popperjs/core/umd/popper.min.js') }}"></script>

    <!-- Bootstrap JS -->
    <script src="{{ asset('backpanel/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Defaultmenu JS -->
    <script src="{{ asset('backpanel/assets/js/defaultmenu.min.js') }}"></script>

    <!-- Node Waves JS-->
    <script src="{{ asset('backpanel/assets/libs/node-waves/waves.min.js') }}"></script>

    <!-- Sticky JS -->
    <script src="{{ asset('backpanel/assets/js/sticky.js') }}"></script>

    <!-- Simplebar JS -->
    <script src="{{ asset('backpanel/assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('backpanel/assets/js/simplebar.js') }}"></script>

    <!-- Color Picker JS -->
    <script src="{{ asset('backpanel/assets/libs/@simonwep/pickr/pickr.es5.min.js') }}"></script>


    <!-- Apex Charts JS -->
    <script src="{{ asset('backpanel/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="https://nepalidatepicker.sajanmaharjan.com.np/nepali.datepicker/js/nepali.datepicker.v4.0.1.min.js"
        type="text/javascript"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.3.0/jquery.form.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <script src="{{ asset('backpanel/assets/libs/chart.js/chart.min.js') }}"></script>
    <script src="{{ asset('backpanel/assets/libs/jsvectormap/js/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('backpanel/assets/libs/jsvectormap/maps/world-merc.js') }}"></script>

    <script type="text/javascript" src="{{ asset('backpanel/assets/js/fontawesome-iconpicker.min.js') }}"></script>

    <!-- Date & Time Picker JS -->
    <script src="{{ asset('backpanel/assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('backpanel/assets/js/date&time_pickers.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.js"></script>
    <script src="{{ asset('backpanel/assets/js/jquery-validate.js') }}"></script>

    <!-- cropper js-->
    <script src="{{ asset('backpanel/assets/js/cropper/cropper.min.js') }}"></script>

    <!-- Sweetalerts JS -->
    <script src="{{ asset('backpanel/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('backpanel/assets/libs/toastr/toastr.min.js') }}"></script>

    <!-- Custom JS -->
    <script src="{{ asset('backpanel/assets/js/custom.js') }}?v={{ $adminCustomJsVersion }}"></script>

    <script>
        window.showLoader = window.showLoader || function() {
            var loader = document.getElementById('loadingOverlay');
            if (loader) {
                loader.style.display = 'block';
            }
        };

        window.hideLoader = window.hideLoader || function() {
            var loader = document.getElementById('loadingOverlay');
            if (loader) {
                loader.style.display = 'none';
            }
        };

        window.showNotification = window.showNotification || function(message, type) {
            if (!window.toastr) {
                window.alert(message || 'Action completed.');
                return;
            }

            // Keep toastr settings in one place so flash and ajax messages feel the same everywhere.
            toastr.options = {
                closeButton: true,
                progressBar: true,
                newestOnTop: true,
                positionClass: 'toast-top-right',
                timeOut: 3200,
                extendedTimeOut: 900,
                preventDuplicates: true,
                showDuration: 180,
                hideDuration: 180
            };

            var toastType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'success';
            toastr[toastType](message || 'Action completed.');
        };

        window.fireFlashToastr = function(type, message) {
            if (!message) {
                return;
            }

            window.showNotification(message, type || 'success');
        };

        var baseurl = '{{ url('/') }}';
        var token = "<?= csrf_token() ?>";
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        @if (session('success'))
            window.setTimeout(function() {
                window.fireFlashToastr('success', @json(session('success')));
            }, 120);
        @endif

        @if (session('error'))
            window.setTimeout(function() {
                window.fireFlashToastr('error', @json(session('error')));
            }, 120);
        @endif

        @if (session('warning'))
            window.setTimeout(function() {
                window.fireFlashToastr('warning', @json(session('warning')));
            }, 120);
        @endif

        @if (session('info'))
            window.setTimeout(function() {
                window.fireFlashToastr('info', @json(session('info')));
            }, 120);
        @endif
    </script>
    @yield('script')

</body>

</html>
