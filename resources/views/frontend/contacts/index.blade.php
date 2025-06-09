@extends('frontend.layouts.main')
@section('main-content')
<main>
    <!-- Hero Start -->
    <div class="slider-area2">
        <div class="slider-height2 d-flex align-items-center">
            <div class="container">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="hero-cap hero-cap2 pt-70">
                            <h2>Contact me</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Hero End -->

    <!-- Contact Area Start -->
    <section class="contact-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <!-- Left Column - Contact Form -->
                <div class="col-lg-7 mb-4">
                    <div class="p-4 shadow bg-white rounded">
                        <h1 class="contact-title mb-4 text-success">Contact Us</h1>
                        <form>
                            <div id="customNotification" class="custom-notification" style="display:none; padding:10px; margin-bottom:15px; border-radius:5px;"></div>

                            <div class="form-group mb-3">
                                <label for="name">Name <span class="text-danger">*</span></label>
                                <input class="form-control dark-input" placeholder="Enter your name" type="text" name="name" id="name">
                            </div>

                            <div class="form-group mb-3">
                                <label for="email">Email <span class="text-danger">*</span></label>
                                <input class="form-control dark-input" placeholder="Email" type="email" name="email" id="email">
                            </div>

                            <div class="form-group mb-3">
                                <label for="subject">Subject <span class="text-danger">*</span></label>
                                <input class="form-control dark-input" placeholder="Enter Subject" type="text" name="subject" id="subject">
                            </div>

                            <div class="form-group mb-4">
                                <label for="message">Message <span class="text-danger">*</span></label>
                                <textarea class="form-control dark-input" name="message" id="message" rows="5" placeholder="Enter Message"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn dark-btn w-100">Send Message</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column - Redesigned Contact Info -->
                <div class="col-lg-5">
                    <div class="p-4 shadow bg-white rounded h-100 contact-box">
                        <h2 class="text-success mb-4">Contact Info</h2>

                        <div class="contact-block mb-4">
                            <h5>Address</h5>
                            <div class="contact-row">
                                <div class="icon-box"><i class="ti-home"></i></div>
                                <div>
                                    <div class="contact-title">Dhumrabaraha Kathmandu</div>
                                    <div class="contact-subtext">Under ring road</div>
                                </div>
                            </div>
                        </div>

                        <div class="contact-block mb-4">
                            <h5>Phone</h5>
                            <div class="contact-row">
                                <div class="icon-box"><i class="ti-tablet"></i></div>
                                <div>
                                    <div class="contact-title">98496790859</div>
                                    <div class="contact-subtext">Everyday 5am to 7pm</div>
                                </div>
                            </div>
                        </div>

                        <div class="contact-block">
                            <h5>Email</h5>
                            <div class="contact-row">
                                <div class="icon-box"><i class="ti-email"></i></div>
                                <div>
                                    <div class="contact-title">pharmapharmacy@gmail.com</div>
                                    <div class="contact-subtext">Send us your query anytime!</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Right Column -->
            </div>
        </div>
    </section>
    <!-- Contact Area End -->


</main>

<style>
   
</style>
@endsection
