<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="light"
    data-menu-styles="light" data-toggled="close">

<head>

    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (!empty($siteSettings->img_favicon))
        <link rel="icon" href="{{ asset('storage/setting') . '/' . $siteSettings->img_favicon }}" type="image/png">
    @endif
    <!-- Choices JS -->
    <script src="{{ asset('backpanel/assets/libs/choices.js/public/assets/scripts/choices.min.js') }}"></script>

    <!-- Main Theme Js -->
    <script src="{{ asset('backpanel/assets/js/main.js') }}"></script>

    <!-- Bootstrap Css -->
    <link id="style" href="{{ asset('backpanel/assets/libs/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Style Css -->
    <link href="{{ asset('backpanel/assets/css/styles.min.css') }}" rel="stylesheet">

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

    <!-- Jsvector Maps -->
    <link rel="stylesheet" href="{{ asset('backpanel/assets/libs/jsvectormap/css/jsvectormap.min.css') }}">

    <link rel="stylesheet" href="{{ asset('backpanel/assets/js/quill-editor.js') }}">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.0-rc.2/dist/quill.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.0-rc.2/dist/quill.snow.css" rel="stylesheet">
    <!-- Nepali date picker -->
    <link href="https://nepalidatepicker.sajanmaharjan.com.np/nepali.datepicker/css/nepali.datepicker.v4.0.1.min.css"
        rel="stylesheet" type="text/css" />

    <link rel="stylesheet" href="{{ asset('backpanel/assets/css/fontawesome-iconpicker.min.css') }}">

    <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/fontawesome.min.css"
        integrity="sha512-d0olNN35C6VLiulAobxYHZiXJmq+vl+BGIgAxQtD5+kqudro/xNMvv2yIHAciGHpExsIbKX3iLg+0B6d0k4+ZA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- cropper -->
    <link href="{{ asset('backpanel/assets/css/cropper/cropper.css') }}" rel="stylesheet">
    <link href="{{ asset('backpanel/assets/css/cropper/cropper.min.css') }}" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/css/bootstrap-datepicker.css"
        rel="stylesheet" />

    <!-- Sweetalerts CSS -->
    <link rel="stylesheet" href="{{ asset('backpanel/assets/libs/sweetalert2/sweetalert2.min.css') }}">
    <style>
        /*error message-start*/

        label {
            font-weight: 500;
            color: #454545;
        }

        .error {
            margin-top: 0.2rem !important;
            color: red !important;
        }

        /*error message-end*/

        /*error trash-start*/
        input#trashed_file {
            border: 1px solid rgb(0, 99, 198) !important
        }

        /*error trash-end*/

        .required-field {
            color: red;
        }

        #loading {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: none;
            z-index: 999;
        }

        .custom-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            background-color: #28A745;
            /* Green for success */
            color: #fff;
            border-radius: 5px;
            display: none;
            z-index: 9999;
        }

        .swal2-container .swal2-styled.swal2-confirm {
            background-color: rgb(222, 12, 12) !important;
            color: #fff;
        }

        .required-field {
            color: rgb(246, 12, 12);
            font-size: 15px;
        }

        .form-group {

            font-weight: 500 !important;
        }

        .form-label {
            font-weight: 500 !important;
        }

        .label {
            font-weight: 500 !important;
        }

        .image-wrapper {
            width: 100px;
            height: 100px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .datepick {
            position: relative;
        }

        .ql-container {
            height: 200px;
        }

        .ql-editor {
            min-height: 100% !important;
        }

        /* for form image upload icon start*/
        .relative {
            position: relative;
            width: 150px !important;
            padding-right: 0 !important;
        }

        .absolute {
            position: absolute;
            right: 0 !important;
        }

        .modal {
            z-index: 999 !important;
        }

        .image {
            min-height: 0;
        }

        /* for form image upload icon end*/


        /* table pagination-start */
        .dataTables_paginate .paginate_button {
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
        }

        .dataTables_paginate .paginate_button:hover {
            background-color: #e9ecef;
            cursor: pointer;
        }

        /* table pagination-end */

        .text-align {
            text-align: start
        }

        .fe-camera:hover {
            cursor: pointer !important;
        }

        #profileImageInput:hover {
            cursor: pointer;
        }
    </style>

    @yield('styles')

</head>

<body>

    <!-- Loader with Background Overlay -->
    <div id="loadingOverlay"
        style="display: none; position: fixed;top: 0px;left: 0px;width: 100%;height: 100%;background: rgb(10 10 10 / 64%);z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <div class="spinner-border spinner-border-lg  text-danger" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    <!-- Loader with Background Overlay -->


    <div class="page">

        <!-- app-header -->
        @include('backend.layouts.header')
        <!-- /app-header -->
        <div id="customNotification" class="custom-notification"></div>

        <!-- Start::app-sidebar -->
        @include('backend.layouts.sidebar')
        <!-- End::app-sidebar -->

        <!-- Start::app-content -->
        <div class="main-content app-content">
            <div class="container-fluid">
                @yield('main-content')
            </div>
        </div>
        <!-- End::app-content -->

        <!-- Footer Start -->
        @include('backend.layouts.footer')
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

    <!-- JSVector Maps JS -->
    <script src="{{ asset('backpanel/assets/libs/jsvectormap/js/jsvectormap.min.js') }}"></script>

    <!-- JSVector Maps MapsJS -->
    <script src="{{ asset('backpanel/assets/libs/jsvectormap/maps/world-merc.js') }}"></script>
    <script src="{{ asset('backpanel/assets/js/us-merc-en.js') }}"></script>

    <!-- Chartjs Chart JS -->
    <script src="{{ asset('backpanel/assets/js/index.js') }}"></script>


    <!-- Custom-Switcher JS -->
    <script src="{{ asset('backpanel/assets/js/custom-switcher.min.js') }}"></script>
    <!-- Custom JS -->
    <script src="{{ asset('backpanel/assets/js/custom.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script src="https://nepalidatepicker.sajanmaharjan.com.np/nepali.datepicker/js/nepali.datepicker.v4.0.1.min.js"
        type="text/javascript"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.form/4.3.0/jquery.form.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script type="text/javascript" src="{{ asset('backpanel/assets/js/fontawesome-iconpicker.min.js') }}"></script>

    <!-- Date & Time Picker JS -->
    <script src="{{ asset('backpanel/assets/libs/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('backpanel/assets/js/date&time_pickers.js') }}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.6.4/js/bootstrap-datepicker.js"></script>
    <script src="{{ asset('backpanel/assets/js/jquery-validate.js') }}"></script>

    <!-- cropper js-->
    <script src="{{ asset('backpanel/assets/js/cropper/cropper.common.js') }}"></script>
    <script src="{{ asset('backpanel/assets/js/cropper/cropper.esm.js') }}"></script>
    <script src="{{ asset('backpanel/assets/js/cropper/cropper.js') }}"></script>
    <script src="{{ asset('backpanel/assets/js/cropper/cropper.min.js') }}"></script>

    <!-- Sweetalerts JS -->
    <script src="{{ asset('backpanel/assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('backpanel/assets/js/sweet-alerts.js') }}"></script>

    <script>
        var baseurl = '{{ url(' / ') }}';
        var token = "<?= csrf_token() ?>";
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    <script>
        function showDatePicker() {
            window.onload = function() {
                var mainInput = document.getElementById("nepali-datepicker");
                mainInput.nepaliDatePicker();
            };

            $("#nepali-datepicker").nepaliDatePicker({
                container: ".datepick",
            });
        }

        function showLoader() {
            $('#loadingOverlay').show();
        }

        function hideLoader() {
            $('#loadingOverlay').hide();
        }

        function showNotification(message, type) {
            var notification = document.getElementById('customNotification');
            notification.textContent = message;

            if (type === 'success') {
                notification.style.backgroundColor = '#28a745'; // Green for success
            } else if (type === 'error') {
                notification.style.backgroundColor = '#dc3545'; // Red for error
            }

            // Show the notification
            notification.style.display = 'block';

            // Hide the notification after 3 seconds (adjust as needed)
            setTimeout(function() {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
    @yield('script')

</body>

</html>
