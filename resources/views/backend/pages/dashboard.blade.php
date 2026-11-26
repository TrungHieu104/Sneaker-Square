@extends('backend.index')

@section('title')
    Dashboard Admin
@endsection

@section('content')
@hasanyrole('Quản trị viên|Nhân viên|Cộng tác viên')
    <div class="row">
        <div class="col-lg-8 mb-4 order-0">
            <div class="card h-100">
                <div class="d-flex align-items-end row">
                    <div class="col-sm-7">
                        <div class="card-body">
                            <?php
                                $timenow = date('H'); 
                                
                                if ($timenow >= 1 && $timenow < 12) {
                                    $sayHi = 'Chào buổi sáng';
                                } elseif ($timenow >= 12 && $timenow < 15) {
                                    $sayHi = 'Chào buổi trưa';
                                } elseif ($timenow >= 15 && $timenow < 18) {
                                    $sayHi = 'Chào buổi chiều';
                                } else {
                                    $sayHi = 'Chào buổi tối';
                                }
                            ?>
                            
                            <h5 class="card-title text-primary"><?php echo $sayHi . ', ' . Auth::user()->name; ?>🎉</h5>
                            <p class="mb-4 fs-6">
                                Hôm nay cửa hàng có <span class="fw-bold">{{$totalOrderToday}}</span> đơn hàng, trong đó có <span class="fw-bold">{{$totalOrder}}</span> đơn hàng mới cần được xử lí.
                            </p>

                            <a href="{{route('order.index')}}" class="btn btn-sm btn-outline-primary">Xem ngay</a>
                        </div>
                    </div>
                    <div class="col-sm-5 text-center text-sm-left">
                        <div class="card-body pb-0 px-0 px-md-4">
                            <img src="/backend/assets/img/illustrations/man-with-laptop-light.png" height="140"
                                alt="View Badge User" data-app-dark-img="illustrations/man-with-laptop-dark.png"
                                data-app-light-img="illustrations/man-with-laptop-light.png" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-4 order-1 mb-4">
            <div class="row h-100">
                <div class="col-lg-6 col-md-12 col-6 mb-4 h-100">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar avatar-top-left flex-shrink-0 rounded">
                                    <a href="{{route('order.index')}}">
                                        <img src="/backend/assets/img/icons/unicons/account-list.png" alt="chart success"
                                        class="rounded h-100 w-100"/>
                                    </a>
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <span class="d-block mb-1">Lượt truy cập</span>
                                <h3 class="card-title text-nowrap mb-1 fs-4 title__truncate">
                                    {{$countVisitor}}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12 col-6 mb-4 h-100">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between">
                                <div class="avatar avatar-top-right flex-shrink-0 rounded">
                                    <img src="/backend/assets/img/icons/unicons/account-online.png" alt="Credit Card"
                                        class="rounded h-100 w-100"/>
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <span class="d-block mb-1">Đang online</span>
                                <h3 class="card-title text-nowrap mb-1 fs-4 title__truncate" id="visitorRealtime">{{$onlineVisitorCount}}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Total Revenue -->
        <div class="col-12 col-lg-8 order-2 order-md-3 order-lg-2 mb-4">
            <div class="card h-100">
                <div class="row row-bordered g-0">
                    <div class="col-md-12">
                        <h5 class="card-header m-0 me-2 pb-0">Tổng doanh thu</h5>
                        <form autocomplete="off">
                            @csrf
                            <div class="card-body">
                                <div class="row mb-1"> 
                                    <div class="col-md-3">
                                        <label for="">Từ ngày:</label>
                                        <input type="text" id="start_coupon" style="font-size: 14px" name="from_date" class="form-control datepicker btn-sm">
                                        <small class="error-text text-danger fst-italic">a</small>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="">Đến ngày:</label>
                                        <input type="text" id="end_coupon" style="font-size: 14px" name="to_date" class="form-control btn-sm datepicker">
                                        <small class="error-text text-danger fst-italic">a</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="color-product" style="">Lọc theo:</label>
                                        <select class="dashboard-filter form-select btn-sm" style="font-size: 14px" id="color-product" name="cate">
                                            <option value="7ngay">7 ngày qua</option>
                                            <option value="thangtruoc">Tháng trước</option>
                                            <option value="thangnay">Tháng này</option>
                                            <option value="365ngayqua">365 ngày qua</option>
                                        </select>
                                    </div>
                                </div>
                                <input type="button" id="btn-dashboard-filter" class="btn btn-primary btn-sm" value="Lọc kết quả">

                            </div>
                        </form>
                        {{-- <div id="chart" style="height: 250px;" class=""></div> --}}
                        <div id="totalRevenueChart" class="px-2"></div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Total Revenue -->
        <div class="col-12 col-md-8 col-lg-4 order-3 order-md-2">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title d-flex align-items-start justify-content-between mb-3">
                                <div class="card-title">
                                    <h5 class="text-nowrap mb-2">Báo cáo cửa hàng</h5>
                                    <span class="badge bg-label-warning rounded-pill">Năm 
                                        {{ now()->year }}
                                    </span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                                        aria-haspopup="true" aria-expanded="false">
                                        <i class="bx bx-dots-vertical-rounded"></i>
                                    </button>
                                    <div class="dropdown-menu mb-0 pb-0" aria-labelledby="cardOpt1">
                                        
                                        
                                        <div class="accordion mb-3" id="accordionExample2">
                                            <div class="accordion-item rounded-0 border-0">
                                                <div class="sidebar-categories">
                                                    <div class="accordion-header">
                                                        <button class="accordion-button head rounded-0" type="button"
                                                            data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true"
                                                            aria-controls="collapseThree">
                                                            Xuất báo cáo Excel
                                                            {{-- <i class="fas fa-chevron-down"></i> --}}
                                                        </button>
                                                    </div>
                                                    <div id="collapseThree" class="accordion-collapse collapse show"
                                                        data-bs-parent="#accordionExample2">
                                                        <ul class="p-0">
                                                            <form action="{{route('export.scvday')}}" method="POST" enctype="multipart/form-data" class="mx-0 mb-0">
                                                                @csrf
                                                                <input type="submit" name="export_scv_day" value="Theo ngày" class="dropdown-item">
                                                            </form>
                                                            <form action="{{route('export.scvweek')}}" method="POST" enctype="multipart/form-data" class="mb-0 mx-0">
                                                                @csrf
                                                                <input type="submit" name="export_week" value="7 ngày qua" class="dropdown-item">
                                                            </form>
                                                            <form action="{{route('export.scvmonth')}}" method="POST" enctype="multipart/form-data" class="mb-0 mx-0">
                                                                @csrf
                                                                <input type="submit" name="export_month" value="Tháng này" class="dropdown-item">
                                                            </form>
                                                            <form action="{{route('export.scvmonthprev')}}" method="POST" enctype="multipart/form-data" class="mb-0 mx-0">
                                                                @csrf
                                                                <input type="submit" name="export_monthprev" value="Tháng trước" class="dropdown-item">
                                                            </form>
                                                            <form action="{{route('export.scv')}}" method="POST" enctype="multipart/form-data" class="">
                                                                @csrf
                                                                <input type="submit" name="export_scv" value="Năm qua" class="dropdown-item">
                                                            </form>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-sm-auto">
                                <div class="row">
                                    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                                        <h6>Doanh thu: </h6>                                        
                                        <h6 class="fw-bold">
                                            <?php
                                                $total = 0;
                                                foreach ($revenue as $data) {
                                                    $total += $data->sales;
                                                }
                                                echo number_format($total,0,",",".").' VNĐ';
                                            ?>
                                        </h6>                                        
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
                                        <h6>Lợi nhuận: </h6>                                        
                                        <h4 class="fw-bold" style="color: #62d326;">
                                            <?php
                                                $total = 0;
                                                foreach ($revenue as $data) {
                                                    $total += $data->profit;
                                                }
                                                echo number_format($total,0,",",".").' VNĐ';
                                            ?>
                                        </h6>                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 col-lg-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title m-0 me-2"><a href="{{route('coupon.index')}}" style="color:#697a8d;">Mã giảm giá</a></h5>
                            <div class="dropdown">
                                <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu mb-0 pb-0" aria-labelledby="cardOpt1">
                                    <form action="{{route('exportcou.scv')}}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="submit" name="export_scv" value="Xuất báo cáo" class="dropdown-item">
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <ul class="p-0 m-0">
                                <li class="d-flex mb-4 pb-1">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <img src="/backend/assets/img/icons/unicons/order5.png" alt="User" class="rounded h-100 w-100" />
                                    </div>
                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <h6 class="mb-0">Tổng số mã</h6>
                                        </div>
                                        <div class="user-progress">
                                            <small class="fw-semibold">{{$dataTotalCoupon['totalCoupon']}}</small>
                                        </div>
                                    </div>
                                </li>
                                <li class="d-flex mb-4 pb-1">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <img src="/backend/assets/img/icons/unicons/order10.png" alt="User"
                                            class="rounded h-100 w-100" />
                                    </div>
                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <h6 class="mb-0">Phổ biến</h6>
                                        </div>
                                        <div class="user-progress">
                                            <a href="{{route('coupon.index')}}" style="color: #697a8d;">
                                            <small class="fw-semibold">{{$couponName}}</small></a>
                                        </div>
                                    </div>
                                </li>
                                <li class="d-flex mb-4 pb-1">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <img src="/backend/assets/img/icons/unicons/order6.png" alt="User" class="rounded h-100 w-100" />
                                    </div>
                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <h6 class="mb-0">Còn hạn</h6>
                                        </div>
                                        <div class="user-progress">
                                            <small class="fw-semibold">{{$dataTotalCoupon['stillValid']}}</small>
                                        </div>
                                    </div>
                                </li>
                                
                                <li class="d-flex">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <img src="/backend/assets/img/icons/unicons/order10.png" alt="User"
                                            class="rounded h-100 w-100" />
                                    </div>
                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="me-2">
                                            <h6 class="mb-0">Hết hạn</h6>
                                        </div>
                                        <div class="user-progress">
                                            <small class="fw-semibold">{{$dataTotalCoupon['expiredValid']}}</small>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <!-- Order Statistics -->
        <div class="col-md-6 col-lg-4 col-xl-4 order-0 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between pb-0">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2">Tổng đơn hàng tháng {{now()->month}} </h5>
                        @if ($dataOrder['revenueOrder'] == 0)
                            <small class="text-muted">Tháng này chưa có doanh thu</small> 
                        @else
                            <small class="text-muted">Doanh thu đơn hàng {{number_format($dataOrder['revenueOrder'], 0, ',', '.')}} VNĐ</small>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        @if ($dataOrder['orderMonth'] ==0)
                            <div class="d-flex flex-column align-items-center gap-1">
                                <h2 class="mb-2">0</h2>
                                <span>Tháng này chưa có đơn hàng</span>
                            </div>
                        @else
                            <div class="d-flex flex-column align-items-center gap-1">
                                <h2 class="mb-2">{{$dataOrder['orderMonth']}}</h2>
                                <span>Đơn hàng</span>
                            </div>
                            <div id="orderStatisticsChart"></div>
                        @endif
                    </div>
                    <ul class="p-0 m-0">
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class='bx bx-timer'></i>
                                </span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Chưa giao</h6>
                                </div>
                                <div class="user-progress">
                                    @if ($dataOrder['orderWaitDelivery'] ==0)
                                        <small class="fw-semibold">0</small>
                                    @else
                                        <small class="fw-semibold">{{$dataOrder['orderWaitDelivery']}}</small>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-secondary">
                                    <i class='bx bxs-truck'></i>
                                </span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Đang giao</h6>
                                </div>
                                <div class="user-progress">
                                    @if ($dataOrder['orderDelivering'] ==0)
                                        <small class="fw-semibold">0</small>
                                    @else
                                        <small class="fw-semibold">{{$dataOrder['orderDelivering']}}</small>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-success">                                    
                                    <i class='bx bx-package'></i>
                                </span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Đã giao</h6>
                                </div>
                                <div class="user-progress">
                                    @if ($dataOrder['orderDelivered']==0)
                                        <small class="fw-semibold">0</small>
                                    @else
                                        <small class="fw-semibold">{{$dataOrder['orderDelivered']}}</small>
                                    @endif
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class='bx bx-x-circle'></i>
                                </span>
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Đã hủy</h6>
                                </div>
                                <div class="user-progress">
                                    @if ($dataOrder['orderFail'] ==0)
                                        <small class="fw-semibold">0</small>
                                    @else
                                        <small class="fw-semibold">{{$dataOrder['orderFail']}}</small>
                                    @endif
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!--/ Order Statistics -->

        <!-- Expense Overview -->
        <div class="col-md-6 col-lg-4 order-1 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between pb-0">
                    <div class="card-title mb-0">
                        <h5 class="m-0 me-2">Người dùng mới</h5>
                        <small class="text-muted">Đăng ký tài khoản trên hệ thống</small>
                    </div>
                </div>
                <div class="card-body px-0">
                    <div class="tab-content p-0">
                        <div class="tab-pane fade show active" id="navs-tabs-line-card-income" role="tabpanel">
                            <div class="d-flex p-4 pt-3 justify-content-between">
                                <div class="d-flex">
                                    <div class="avatar flex-shrink-0 me-3">
                                        <img src="/backend/assets/img/icons/unicons/account-new.png" alt="User" class="h-100 w-100"/>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Khách hàng</small>
                                        <div class="d-flex align-items-center">
                                            <h6 class="mb-0 me-1">{{$dataTotal['totalAccountUser']}}</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <select class="form-select" aria-label="Default select example" id="chartAccountUser">
                                        <option value="7days" selected>7 ngày</option>
                                        <option value="lmonth">Tháng trước</option>
                                        <option value="tmonth">Tháng này</option>
                                    </select>
                                </div>
                            </div>
                            <div id="incomeChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/ Expense Overview -->

        <!-- Transactions -->
        <div class="col-md-6 col-lg-4 order-2 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title m-0 me-2">Tổng quan</h5>
                </div>
                <div class="card-body">
                    <ul class="p-0 m-0">
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <img src="/backend/assets/img/icons/unicons/order5.png" alt="User" class="rounded h-100 w-100" />
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Sản phẩm</h6>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{$dataTotal['totalPro']}}</small>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <img src="/backend/assets/img/icons/unicons/order6.png" alt="User" class="rounded h-100 w-100" />
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Bài viết</h6>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{$dataTotal['totalNews']}}</small>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <img src="/backend/assets/img/icons/unicons/order7.png" alt="User" class="rounded h-100 w-100" />
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Tài khoản</h6>
                                    <small class="text-muted">Khách hàng</small>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{$dataTotal['totalAccountUser']}}</small>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <img src="/backend/assets/img/icons/unicons/order8.png" alt="User"
                                    class="rounded h-100 w-100" />
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Tài khoản</h6>
                                    <small class="text-muted">Quản trị</small>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{$dataTotal['totalAccountAdmin']}}</small>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <img src="/backend/assets/img/icons/unicons/order9.png" alt="User" class="rounded h-100 w-100" />
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Đơn hàng</h6>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{$dataTotal['totalOrder']}}</small>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex mb-4 pb-1">
                            <div class="avatar flex-shrink-0 me-3">
                                <img src="/backend/assets/img/icons/unicons/order10.png" alt="User"
                                    class="rounded h-100 w-100" />
                            </div>
                            <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                <div class="me-2">
                                    <h6 class="mb-0">Mã giảm giá</h6>
                                </div>
                                <div class="user-progress">
                                    <small class="fw-semibold">{{$dataTotal['totalCoupon']}}</small>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!--/ Transactions -->
    </div>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-10 d-flex align-items-center mb-3">
                            <h5 class="mb-0">Thống kê truy cập</h5>
                        </div>
                        <div class="col-lg-2 d-flex align-items-center mb-3">
                            <select class="form-select" aria-label="Default select example" id="filter-visitor">
                                <option value="7">7 ngày</option>
                                <option value="10">10 ngày</option>
                                <option value="14">14 ngày</option>
                                <option value="20">20 ngày</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if ($trafficUnavailable)
                        <x-admin.traffic-unavailable class="mb-3" />
                    @endif
                    <div class="row">
                        <div class="col-12">
                            <div id="chartVisitor"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endhasanyrole
@endsection

@push('script-backend')
    <script type="text/javascript">
    
        $(document).ready(function() {

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }

            });

            function initializeChart(response) {
                var categories = response.map(item => item.period);
                var salesData = response.map(item => item.sales);
                var profitData = response.map(item => item.profit);

                var options = {
                    series: [{
                        name: 'Doanh thu',
                        data: salesData
                    }, {
                        name: 'Lợi nhuận',
                        data: profitData
                    }],
                    chart: {
                        type: 'bar',
                        height: 350
                    },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            columnWidth: '55%',
                            endingShape: 'rounded'
                        },
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        show: true,
                        width: 2,
                        colors: ['transparent']
                    },
                    xaxis: {
                        name: 'Ngày',
                        categories: categories,
                    },
                    yaxis: {
                        title: {
                            text: 'VNĐ (việt nam đồng)'
                        }
                    },
                    fill: {
                        opacity: 1
                    },
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + " VNĐ";
                            }
                        }
                    }
                };
                chart = new ApexCharts(document.querySelector("#totalRevenueChart"), options);
                chart.render();
            }
            var chart_data = {!! json_encode($chart_data) !!};
            initializeChart(chart_data);

            //count visitors realtime
            function visitorRealtime() {
                $.ajax({
                    url: "{{route('admin.dashboard.post')}}",
                    method: "POST",
                    success: function(response) {
                        // console.log(response.visitorTotal);
                        if(response) {
                            $("#visitorRealtime").text(response.visitorTotal);
                            setTimeout(visitorRealtime, 10000);
                        }
                    }, 
                    error: function(error) {
                        // console.log(error);
                        setTimeout(visitorRealtime, 10000);
                    },
                });
            }
            visitorRealtime();
            
            //chart account new
            $('#chartAccountUser').on('change', function(){
                var dateValue = $(this).val();

                $.ajax({
                    url: "{{route('dashboard.filter.account')}}",
                    method: "POST",
                    data: {
                        dateData: dateValue,
                    },
                    success: function(res) {
                        renderAccountChart(res);
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
                
            });

            function renderAccountChart(res) {
                var options = {
                    series: [{
                        name: "Tài khoản",
                        data: res.data_count
                    }],
                    chart: {
                        height: 350,
                        type: 'area',
                        zoom: {
                            enabled: false
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    grid: {
                        row: {
                            colors: ['#f3f3f3', 'transparent'], 
                            opacity: 0.5
                        },
                        strokeDashArray: 3,
                    },
                    xaxis: {
                        categories: res.date,
                    },
                    yaxis: {
                        labels: {
                            formatter: function (val) {
                                if (val === parseInt(val)) {
                                    return val.toString(); 
                                } else {
                                    return val.toFixed(1); 
                                }
                            }
                        }
                    }
                };
                var chartAccount = new ApexCharts(document.querySelector("#incomeChart"), options);
                chartAccount.render();
                chartAccount.updateSeries([
                    {
                        name: "Tài khoản",
                        data: res.data_count
                    }, 
                ]);
            }
            var dataAccount = {!! json_encode($datatAccountCount) !!};
            renderAccountChart(dataAccount);

            //total order
            var options = {
                series: [{{$dataOrder['orderWaitDelivery']}}, {{$dataOrder['orderDelivering']}}, {{$dataOrder['orderDelivered']}}, {{$dataOrder['orderFail']}}],
                labels: ['Chưa giao', 'Đang giao', 'Đã giao', 'Đã hủy'],
                chart: {
                    type: 'donut',
                },
                colors: [config.colors.primary, config.colors.secondary, config.colors.success, config.colors.danger],
                responsive: [{
                    breakpoint: 480,
                    options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                    },
                }],
                dataLabels: {
                    enabled: true,
                    formatter: function (val, opt) {
                        return parseInt(val) + '%';
                    }
                },
                legend: {
                    show: false
                },
                grid: {
                    padding: {
                        top: 0,
                        bottom: 0,
                        right: 15
                    }
                },
            };
            var chart = new ApexCharts(document.querySelector("#orderStatisticsChart"), options);
            chart.render();

            // chart data
            $('#filter-visitor').on('change', function(){
                var dateFilter = $(this).val();
               
                $.ajax({
                    url: "{{route('dashboard.filter.visitor')}}",
                    method: "GET",
                    data: {
                        dataDate : dateFilter
                    },
                    success: function(response) {
                        filterVisitorDate(response);
                    },
                    error: function (error) {
                        console.log(error);
                    }
                });
            });

            //chart data visitor
            function filterVisitorDate(response) {
                var options = {
                    series: [
                        {
                            name: "Người dùng",
                            data: response.activeUsers
                        },
                        {
                            name: "Xem trang",
                            data: response.screenPageViews
                        }
                    ],
                    chart: {
                        height: 350,
                        type: 'area',
                        zoom: {
                            enabled: false
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    grid: {
                        row: {
                            colors: ['#f3f3f3', 'transparent'], 
                            opacity: 0.5
                        },
                    },
                    xaxis: {
                        categories: response.date,
                    }
                };
                var chart = new ApexCharts(document.querySelector("#chartVisitor"), options);
                chart.render();
                chart.updateSeries([
                    {
                        name: "Người dùng",
                        data: response.activeUsers
                    },
                    {
                        name: "Xem trang",
                        data: response.screenPageViews
                    }
                ]);
            }
            var dataVisitor = {!! json_encode($dataVisitor) !!};
            filterVisitorDate(dataVisitor);
        });
    </script>
@endpush