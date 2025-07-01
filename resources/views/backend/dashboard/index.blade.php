@extends('backend.layouts.main')

@section('title')
    Dashboard
@endsection

@section('styles')
    <style>
        .sales-card {
            margin: 10px;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: #ffffff;
            transition: all 0.3s ease;
        }

        .sales-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #505050;
        }

        .card-text {
            font-size: 1.2rem;
            color: #666;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
        }

        .card-text strong {
            color: #5050509e;
            font-size: 1.5rem;
        }

        .card-icon {
            font-size: 2rem;
            color: #08c;
            margin-bottom: 15px;
        }

        .total-sales {
            color: #007bff;
        }

        .year-sales {
            color: #ffc107;
        }

        .month-sales {
            color: #28a745;
        }

        .daily-sales {
            color: #dc3545;
        }

        .destination {
            color: #007bff;
        }

        .package {
            color: #ffc107;
        }

        .trekking {
            color: #28a745;
        }

        .activity {
            color: #dc3545;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #f9f9f9;
        }

        .table-striped tbody tr:hover {
            background-color: #f1f1f1;
        }

        .badge {
            font-size: 0.8rem;
            padding: 0.5rem 0.8rem;
            border-radius: 0.5rem;
            font-weight: bold;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-warning {
            background-color: #c2940a;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .text-gradient {
            font-weight: 700;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(45deg, #7f7b7a, #3e349a);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
    </style>
@endsection

@section('main-content')
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h4 class="mb-0">Welcome To Dashboard </h4>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-md-4">
            <div class="sales-card year-sales">
                <div class="card-body">
                    <i class="fa-solid fa-list card-icon"></i>
                    <h5 class="card-title">Total Category</h5>
                    <p class="card-text"><span style="flex-grow: 1;"></span><strong>
                            {{ !empty(@$totalCategory) ? @$totalCategory : '0' }} </strong></p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="sales-card daily-sales">
                <div class="card-body">
                    <i class="fa-brands fa-product-hunt card-icon"></i>
                    <h5 class="card-title">Total Prodcut</h5>
                    <p class="card-text"><span style="flex-grow: 1;"></span><strong>
                            {{ !empty(@$totalProducts) ? @$totalProducts : '0' }} </strong></p>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="sales-card daily-sales">
                <div class="card-body">
                    <i class="fa-solid fa-users-line card-icon"></i>
                    <h5 class="card-title">Total Customer</h5>
                    <p class="card-text"><span style="flex-grow: 1;"></span><strong>
                            {{ !empty(@$totalUser) ? @$totalUser : '0' }} </strong></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-md-3">
            <a href="">
                <div class="sales-card daily-sales">
                    <div class="card-body">
                        <i class="bi bi-handbag card-icon"></i>
                        <h5 class="card-title">Total Order</h5>
                        <p class="card-text"><span style="flex-grow: 1;"></span><strong>
                                {{ !empty(@$totalOrder) ? @$totalOrder : '0' }} </strong></p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-md-3">
            <a href="">
                <div class="sales-card daily-sales">
                    <div class="card-body">
                        <i class="bi bi-shop-window  card-icon"></i>
                        <h5 class="card-title">Order Delivered</h5>
                        <p class="card-text"><span style="flex-grow: 1;"></span><strong>
                                {{ !empty(@$orderDelivered) ? @$orderDelivered : '0' }} </strong></p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-md-3">
            <a href="">
                <div class="sales-card daily-sales">
                    <div class="card-body">
                        <i class="bi bi-truck card-icon"></i>
                        <h5 class="card-title">Order on delivery</h5>
                        <p class="card-text"><span style="flex-grow: 1;"></span><strong>
                                {{ !empty(@$orderOndelivery) ? @$orderOndelivery : '0' }} </strong></p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-md-3">
            <a href="">

                <div class="sales-card daily-sales">
                    <div class="card-body">
                        <i class="bi bi-sticky card-icon""></i>
                        <h5 class="card-title">Order left to be delivered</h5>
                        <p class="card-text"><span style="flex-grow: 1;"></span><strong>
                                {{ !empty(@$orderOrdered) ? @$orderOrdered : '0' }} </strong></p>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <div class="row">
        <div class="col-sm-6 col-md-3">
            <a href="">

                <div class="sales-card daily-sales">
                    <div class="card-body">
                        <i class="bi bi-x card-icon""></i>
                        <h5 class="card-title">Cancel Order</h5>
                        <p class="card-text"><span style="flex-grow: 1;"></span><strong>
                                {{ !empty(@$orderCancel) ? @$orderCancel : '0' }} </strong></p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-md-3">
            <div class="sales-card daily-sales">
                <div class="card-body">
                    <i class="fa-solid fa-money-bill card-icon"></i>
                    <h5 class="card-title">Revenue</h5>
                    <p class="card-text"><span style="flex-grow: 1;"></span><strong>
                            {{ !empty(@$totalRevenue) ? @$totalRevenue : '0' }} </strong></p>
                </div>
            </div>
        </div>

    </div>
@endsection
