@extends('backend.index')

@section('title')
    Đơn hàng
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Quản trị /</span> <a href="{{route('order.index')}}" class="tab-sort">Đơn hàng</a></h4>
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý đơn hàng</h5>
            <div class="px-4">
                <div class="dropdown mt-2">
                    <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu mb-0 pb-0" aria-labelledby="cardOpt1" id="myDropdown">
                        <form action="{{route('exportorder.scv')}}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="submit" name="export_scv" value="Xuất báo cáo" class="dropdown-item">
                        </form>
                        <div class="accordion mb-3" id="accordionExample2">
                            <div class="accordion-item rounded-0 border-0">
                                <div class="sidebar-categories">
                                    <div class="accordion-header">
                                        <button class="accordion-button head rounded-0" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="true"
                                            aria-controls="collapseThree">
                                            Lọc theo
                                            {{-- <i class="fas fa-chevron-down"></i> --}}
                                        </button>
                                    </div>
                                    <div id="collapseThree" class="accordion-collapse collapse show"
                                        data-bs-parent="#accordionExample2">
                                        <ul class="p-0">
                                            <a class="dropdown-item {{ request('sort') === 'today' ? 'active' : '' }}" href="?sort=today">Hôm nay</a>
                                            <a class="dropdown-item {{ request('sort') === 'week' ? 'active' : '' }}" href="?sort=week">7 ngày qua</a>
                                            <a class="dropdown-item {{ request('sort') === 'month' ? 'active' : '' }}" href="?sort=month">Tháng này</a>
                                            <a class="dropdown-item {{ request('sort') === 'pmonth' ? 'active' : '' }}" href="?sort=pmonth">Tháng trước</a>
                                            <a class="dropdown-item {{ request('sort') === 'year' ? 'active' : '' }}" href="?sort=year">365 ngày qua</a>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>  
        <div class="card-body">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="nav-align-top mb-4">
                            {{-- <ul class="nav nav-tabs" role="tablist"> --}}
                            <ul class="nav nav-pills mb-2 nav-fill" role="tablist">
                                @php 
                                    $total = $order->total();
                                    $totalNew = $orderNew->total();
                                    $totalConfirm = $orderConfirm->total();
                                    $totalCancel = $orderCancel->total();
                                    $totalDelivery = $orderDeli->total();
                                    $totalReturn = $orderReturn->total();
                                    $totalSuccess = $orderSuccess->total();
                                @endphp
                                <li class="nav-item">
                                    <button
                                    type="button"
                                    class="nav-link active p-2"
                                    role="tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-top-home"
                                    aria-controls="navs-pills-top-home"
                                    aria-selected="true"
                                    id="order-tab"
                                    >
                                        Tất cả
                                        <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-info">{{$total}}</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                    type="button"
                                    class="nav-link p-2"
                                    role="tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-top-profile"
                                    aria-controls="navs-pills-top-profile"
                                    aria-selected="false"
                                    id="ordernew-tab"
                                    >
                                        Đơn hàng mới
                                        <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-primary">{{$totalNew}}</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                    type="button"
                                    class="nav-link p-2"
                                    role="tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-top-messages"
                                    aria-controls="navs-pills-top-messages"
                                    aria-selected="false"
                                    id="orderconfirm-tab"
                                    >
                                        Đã xác nhận
                                        <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-success">{{$totalConfirm}}</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                    type="button"
                                    class="nav-link p-2"
                                    role="tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-top-cancel"
                                    aria-controls="navs-pills-top-cancel"
                                    aria-selected="false"
                                    id="ordercancel-tab"
                                    >
                                        Đã hủy
                                        <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-danger">{{$totalCancel}}</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                    type="button"
                                    class="nav-link p-2"
                                    role="tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-top-deli"
                                    aria-controls="navs-pills-top-deli"
                                    aria-selected="false"
                                    id="orderdili-tab"
                                    >
                                        Vận chuyển
                                        <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-secondary">{{$totalDelivery}}</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                    type="button"
                                    class="nav-link p-2"
                                    role="tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-top-return"
                                    aria-controls="navs-pills-top-return"
                                    aria-selected="false"
                                    id="orderreturn-tab"
                                    >
                                        Hoàn hàng
                                        <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-warning">{{$totalReturn}}</span>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button
                                    type="button"
                                    class="nav-link p-2"
                                    role="tab"
                                    data-bs-toggle="tab"
                                    data-bs-target="#navs-pills-top-success"
                                    aria-controls="navs-pills-top-success"
                                    aria-selected="false"
                                    id="ordersuccess-tab"
                                    >
                                        Thành công
                                        <span class="badge rounded-pill badge-center h-px-20 w-px-20 bg-label-dark">{{$totalSuccess}}</span>
                                    </button>
                                </li>
                            </ul>
                            <div class="tab-content p-0 mt-3" id="tab-order">
                                <div class="tab-pane fade show active" id="navs-pills-top-home" role="tabpanel">
                                    <div class="table-responsive text-nowrap" id="all-orders">
                                        <table class="table table-bordered w-100" id="myTable">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">
                                                        {{-- <a class="tab-sort" href="?sort-by=order_id&sort-type={{ $orderBy === 'order_id' ? $orderType : 'asc' }}"> --}}
                                                            STT
                                                        {{-- </a> --}}
                                                    </th>
                                                    <th class="text-center">
                                                        <a class="tab-sort" href="?sort-by=order_code&sort-type={{ $orderBy === 'order_code' ? $orderType : 'desc' }}">
                                                            Mã đơn hàng
                                                        </a>
                                                    </th>                            
                                                    <th class="text-center">
                                                        <a class="tab-sort" href="?sort-by=order_name&sort-type={{ $orderBy === 'order_name' ? $orderType : 'desc' }}">
                                                            Tên người đặt
                                                        </a>
                                                    </th>
                                                    <th class="text-center">
                                                        <a class="tab-sort" href="?sort-by=order_total&sort-type={{ $orderBy === 'order_total' ? $orderType : 'desc' }}">
                                                            Tổng tiền
                                                        </a>
                                                    </th>
                                                    <th class="text-center">
                                                        <a class="tab-sort" href="?sort-by=order_date&sort-type={{ $orderBy === 'order_date' ? $orderType : 'desc' }}">
                                                            Ngày đặt
                                                        </a>
                                                    </th>
                                                    <th class="text-center">
                                                        <a class="tab-sort" href="?sort-by=order_status&sort-type={{ $orderBy === 'order_status' ? $orderType : 'desc' }}">
                                                            Trạng thái
                                                        </a>
                                                    </th>
                                                    <th class="text-center" style="width: 100px;">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $stt = $order->firstItem();
                                                @endphp
                                                @if($order->isEmpty())
                                                    <tr>
                                                        <td colspan="7" class="text-center">
                                                            Không có dữ liệu để hiển thị.
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach($order as $data)
                                                    <tr>
                                                        <td class="text-center">
                                                            {{$stt}}
                                                        </td>
                                                        <td>
                                                            {{$data->order_code}}
                                                        </td>
                                                        <td class="pro-name">
                                                            {{$data->order_name}}
                                                        </td>
                                                        <td class="pro-name" style="text-align:right">{{number_format($data->order_total,0,',','.')}} VNĐ</td>
                                                        <td class="text-center">
                                                            {{date('d-m-Y', strtotime($data->order_date))}}
                                                        </td>
                                                        <td class="text-center">
                                                            @if($data->order_status==0)
                                                                <span style="color:darkblue">Đơn hàng mới</span>
                                                            @elseif($data->order_status==1)
                                                                @if ($data->order_delivery_status == 1)
                                                                    <span>Vận chuyển</span>
                                                                @else
                                                                    <span style="color: green">Đã xác nhận</span>
                                                                @endif
                                                            @elseif($data->order_status==2)
                                                                <span style="color: red">Đã hủy</span>
                                                            @elseif($data->order_status==3)
                                                                <span style="color: orange">Hoàn hàng</span>
                                                            @elseif($data->order_status==10)
                                                                <span style="color:chocolate">Thành công</span>
                                                            @endif
                                                        </td>
                                                        
                                                        <td class="text-center">
                                                            <div>
                                                                <a class="btn btn-success btn-sm mx-1"
                                                                    href="{{ route('orders.edit', ['encryptedOrderId' => encrypt($data->order_id)]) }}"
                                                                    title="Xem chi tiết">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $stt++; 
                                                    @endphp
                                                    @endforeach
                                                @endif  
                                            </tbody>
                                        </table>
                                    </div>
                                    {{$order->onEachSide(1)->links('backend.layouts.partials.pagination'),['page' => 'order_page']}}
                                </div>
                                <div class="tab-pane fade" id="navs-pills-top-profile" role="tabpanel">
                                    <div class="table-responsive text-nowrap" id="new-orders">
                                        <table class="table table-bordered w-100" id="myTable">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">
                                                        STT
                                                    </th>
                                                    <th class="text-center">
                                                       <a class="tab-sort" href="?sort-by=order_code&sort-type={{ $orderBy === 'order_code' ? $orderType : 'desc' }}">
                                                            Mã đơn hàng
                                                        </a>
                                                    </th>                            
                                                    <th class="text-center">
                                                        Tên người đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Tổng tiền
                                                    </th>
                                                    <th class="text-center">
                                                        Ngày đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Trạng thái
                                                    </th>
                                                    <th class="text-center" style="width: 100px;">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $stt = $orderNew->firstItem();
                                                @endphp
                                                @if($orderNew->isEmpty())
                                                    <tr>
                                                        <td colspan="7" class="text-center">
                                                            Không có dữ liệu để hiển thị.
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach($orderNew as $data)
                                                    <tr>
                                                        <td class="text-center">
                                                            {{$stt}}
                                                        </td>
                                                        <td>
                                                            {{$data->order_code}}
                                                        </td>
                                                        <td class="pro-name">
                                                            {{$data->order_name}}
                                                        </td>
                                                        <td class="pro-name" style="text-align:right">{{number_format($data->order_total,0,',','.')}} VNĐ</td>
                                                        <td class="text-center">
                                                            {{date('d-m-Y', strtotime($data->order_date))}}
                                                        </td>
                                                        <td class="text-center">
                                                            @if($data->order_status==0)
                                                                <span style="color:darkblue">Đơn hàng mới</span>
                                                            @elseif($data->order_status==1)
                                                                @if ($data->order_delivery_status == 1)
                                                                    <span>Vận chuyển</span>
                                                                @else
                                                                    <span style="color: green">Đã xác nhận</span>
                                                                @endif
                                                            @elseif($data->order_status==2)
                                                                <span style="color: red">Đã hủy</span>
                                                            @elseif($data->order_status==3)
                                                                <span style="color: orange">Hoàn hàng</span>
                                                            @elseif($data->order_status==10)
                                                                <span style="color:chocolate">Thành công</span>
                                                            @endif
                                                        </td>
                                                        
                                                        <td class="text-center">
                                                            <div>
                                                                <a class="btn btn-success btn-sm mx-1"
                                                                    href="{{ route('orders.edit', ['encryptedOrderId' => encrypt($data->order_id)]) }}"
                                                                    title="Xem chi tiết">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $stt++; 
                                                    @endphp
                                                    @endforeach
                                                @endif  
                                            </tbody>
                                        </table>
                                    </div>
                                    {{$orderNew->onEachSide(1)->links('backend.layouts.partials.pagination'),['page' => 'order_new_page']}}
                                </div>
                                <div class="tab-pane fade" id="navs-pills-top-messages" role="tabpanel">
                                    <div class="table-responsive text-nowrap">
                                        <table class="table table-bordered w-100" id="myTable">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">
                                                        STT
                                                    </th>
                                                    <th class="text-center">
                                                       <a class="tab-sort" href="?sort-by=order_code&sort-type={{ $orderBy === 'order_code' ? $orderType : 'desc' }}">
                                                            Mã đơn hàng
                                                        </a>
                                                    </th>                            
                                                    <th class="text-center">
                                                        Tên người đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Tổng tiền
                                                    </th>
                                                    <th class="text-center">
                                                        Ngày đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Trạng thái
                                                    </th>
                                                    <th class="text-center" style="width: 100px;">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $stt = $orderConfirm->firstItem();
                                                @endphp
                                                @if($orderConfirm->isEmpty())
                                                    <tr>
                                                        <td colspan="7" class="text-center">
                                                            Không có dữ liệu để hiển thị.
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach($orderConfirm as $data)
                                                    <tr>
                                                        <td class="text-center">
                                                            {{$stt}}
                                                        </td>
                                                        <td>
                                                            {{$data->order_code}}
                                                        </td>
                                                        <td class="pro-name">
                                                            {{$data->order_name}}
                                                        </td>
                                                        <td class="pro-name" style="text-align:right">{{number_format($data->order_total,0,',','.')}} VNĐ</td>
                                                        <td class="text-center">
                                                            {{date('d-m-Y', strtotime($data->order_date))}}
                                                        </td>
                                                        <td class="text-center">
                                                            @if($data->order_status==0)
                                                                <span style="color:darkblue">Đơn hàng mới</span>
                                                            @elseif($data->order_status==1)
                                                                @if ($data->order_delivery_status == 1)
                                                                    <span>Vận chuyển</span>
                                                                @else
                                                                    <span style="color: green">Đã xác nhận</span>
                                                                @endif
                                                            @elseif($data->order_status==2)
                                                                <span style="color: red">Đã hủy</span>
                                                            @elseif($data->order_status==3)
                                                                <span style="color: orange">Hoàn hàng</span>
                                                            @elseif($data->order_status==10)
                                                                <span style="color:chocolate">Thành công</span>
                                                            @endif
                                                        </td>
                                                        
                                                        <td class="text-center">
                                                            <div>
                                                                <a class="btn btn-success btn-sm mx-1"
                                                                    href="{{ route('orders.edit', ['encryptedOrderId' => encrypt($data->order_id)]) }}"
                                                                    title="Xem chi tiết">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $stt++; 
                                                    @endphp
                                                    @endforeach
                                                @endif  
                                            </tbody>
                                        </table>
                                    </div>
                                    {{$orderConfirm->onEachSide(1)->links('backend.layouts.partials.pagination'),['page' => 'order_confirm']}}
                                </div>
                                <div class="tab-pane fade" id="navs-pills-top-cancel" role="tabpanel">
                                    <div class="table-responsive text-nowrap">
                                        <table class="table table-bordered w-100" id="myTable">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">
                                                        STT
                                                    </th>
                                                    <th class="text-center">
                                                       <a class="tab-sort" href="?sort-by=order_code&sort-type={{ $orderBy === 'order_code' ? $orderType : 'desc' }}">
                                                            Mã đơn hàng
                                                        </a>
                                                    </th>                            
                                                    <th class="text-center">
                                                        Tên người đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Tổng tiền
                                                    </th>
                                                    <th class="text-center">
                                                        Ngày đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Trạng thái
                                                    </th>
                                                    <th class="text-center" style="width: 100px;">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $stt = $orderCancel->firstItem();
                                                @endphp
                                                @if($orderCancel->isEmpty())
                                                    <tr>
                                                        <td colspan="7" class="text-center">
                                                            Không có dữ liệu để hiển thị.
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach($orderCancel as $data)
                                                    <tr>
                                                        <td class="text-center">
                                                            {{$stt}}
                                                        </td>
                                                        <td>
                                                            {{$data->order_code}}
                                                        </td>
                                                        <td class="pro-name">
                                                            {{$data->order_name}}
                                                        </td>
                                                        <td class="pro-name" style="text-align:right">{{number_format($data->order_total,0,',','.')}} VNĐ</td>
                                                        <td class="text-center">
                                                            {{date('d-m-Y', strtotime($data->order_date))}}
                                                        </td>
                                                        <td class="text-center">
                                                            @if($data->order_status==0)
                                                                <span style="color:darkblue">Đơn hàng mới</span>
                                                            @elseif($data->order_status==1)
                                                                @if ($data->order_delivery_status == 1)
                                                                    <span>Vận chuyển</span>
                                                                @else
                                                                    <span style="color: green">Đã xác nhận</span>
                                                                @endif
                                                            @elseif($data->order_status==2)
                                                                <span style="color: red">Đã hủy</span>
                                                            @elseif($data->order_status==3)
                                                                <span style="color: orange">Hoàn hàng</span>
                                                            @elseif($data->order_status==10)
                                                                <span style="color:chocolate">Thành công</span>
                                                            @endif
                                                        </td>
                                                        
                                                        <td class="text-center">
                                                            <div>
                                                                <a class="btn btn-success btn-sm mx-1"
                                                                    href="{{ route('orders.edit', ['encryptedOrderId' => encrypt($data->order_id)]) }}"
                                                                    title="Xem chi tiết">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $stt++; 
                                                    @endphp
                                                    @endforeach
                                                @endif  
                                            </tbody>
                                        </table>
                                    </div>
                                    {{$orderCancel->onEachSide(1)->links('backend.layouts.partials.pagination'),['page' => 'order_cancel']}}
                                </div>
                                <div class="tab-pane fade" id="navs-pills-top-return" role="tabpanel">
                                    <div class="table-responsive text-nowrap">
                                        <table class="table table-bordered w-100" id="myTable">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">
                                                        STT
                                                    </th>
                                                    <th class="text-center">
                                                       <a class="tab-sort" href="?sort-by=order_code&sort-type={{ $orderBy === 'order_code' ? $orderType : 'desc' }}">
                                                            Mã đơn hàng
                                                        </a>
                                                    </th>                            
                                                    <th class="text-center">
                                                        Tên người đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Tổng tiền
                                                    </th>
                                                    <th class="text-center">
                                                        Ngày đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Trạng thái
                                                    </th>
                                                    <th class="text-center" style="width: 100px;">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $stt = $orderReturn->firstItem();
                                                @endphp
                                                @if($orderReturn->isEmpty())
                                                    <tr>
                                                        <td colspan="7" class="text-center">
                                                            Không có dữ liệu để hiển thị.
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach($orderReturn as $data)
                                                    <tr>
                                                        <td class="text-center">
                                                            {{$stt}}
                                                        </td>
                                                        <td>
                                                            {{$data->order_code}}
                                                        </td>
                                                        <td class="pro-name">
                                                            {{$data->order_name}}
                                                        </td>
                                                        <td class="pro-name" style="text-align:right">{{number_format($data->order_total,0,',','.')}} VNĐ</td>
                                                        <td class="text-center">
                                                            {{date('d-m-Y', strtotime($data->order_date))}}
                                                        </td>
                                                        <td class="text-center">
                                                            @if($data->order_status==0)
                                                                <span style="color:darkblue">Đơn hàng mới</span>
                                                            @elseif($data->order_status==1)
                                                                @if ($data->order_delivery_status == 1)
                                                                    <span>Vận chuyển</span>
                                                                @else
                                                                    <span style="color: green">Đã xác nhận</span>
                                                                @endif
                                                            @elseif($data->order_status==2)
                                                                <span style="color: red">Đã hủy</span>
                                                            @elseif($data->order_status==3)
                                                                <span style="color: orange">Hoàn hàng</span>
                                                            @elseif($data->order_status==10)
                                                                <span style="color:chocolate">Thành công</span>
                                                            @endif
                                                        </td>
                                                        
                                                        <td class="text-center">
                                                            <div>
                                                                <a class="btn btn-success btn-sm mx-1"
                                                                    href="{{ route('orders.edit', ['encryptedOrderId' => encrypt($data->order_id)]) }}"
                                                                    title="Xem chi tiết">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $stt++; 
                                                    @endphp
                                                    @endforeach
                                                @endif  
                                            </tbody>
                                        </table>
                                    </div>
                                    {{$orderReturn->onEachSide(1)->links('backend.layouts.partials.pagination'),['page' => 'order_return']}}
                                </div>
                                <div class="tab-pane fade" id="navs-pills-top-success" role="tabpanel">
                                    <div class="table-responsive text-nowrap">
                                        <table class="table table-bordered w-100" id="myTable">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">
                                                        STT
                                                    </th>
                                                    <th class="text-center">
                                                       <a class="tab-sort" href="?sort-by=order_code&sort-type={{ $orderBy === 'order_code' ? $orderType : 'desc' }}">
                                                            Mã đơn hàng
                                                        </a>
                                                    </th>                            
                                                    <th class="text-center">
                                                        Tên người đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Tổng tiền
                                                    </th>
                                                    <th class="text-center">
                                                        Ngày đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Trạng thái
                                                    </th>
                                                    <th class="text-center" style="width: 100px;">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $stt = $orderSuccess->firstItem();
                                                @endphp
                                                @if($orderSuccess->isEmpty())
                                                    <tr>
                                                        <td colspan="7" class="text-center">
                                                            Không có dữ liệu để hiển thị.
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach($orderSuccess as $data)
                                                    <tr>
                                                        <td class="text-center">
                                                            {{$stt}}
                                                        </td>
                                                        <td>
                                                            {{$data->order_code}}
                                                        </td>
                                                        <td class="pro-name">
                                                            {{$data->order_name}}
                                                        </td>
                                                        <td class="pro-name" style="text-align:right">{{number_format($data->order_total,0,',','.')}} VNĐ</td>
                                                        <td class="text-center">
                                                            {{date('d-m-Y', strtotime($data->order_date))}}
                                                        </td>
                                                        <td class="text-center">
                                                            @if($data->order_status==0)
                                                                <span style="color:darkblue">Đơn hàng mới</span>
                                                            @elseif($data->order_status==1)
                                                                @if ($data->order_delivery_status == 1)
                                                                    <span>Vận chuyển</span>
                                                                @else
                                                                    <span style="color: green">Đã xác nhận</span>
                                                                @endif
                                                            @elseif($data->order_status==2)
                                                                <span style="color: red">Đã hủy</span>
                                                            @elseif($data->order_status==3)
                                                                <span style="color: orange">Hoàn hàng</span>
                                                            @elseif($data->order_status==10)
                                                                <span style="color:chocolate">Thành công</span>
                                                            @endif
                                                        </td>
                                                        
                                                        <td class="text-center">
                                                            <div>
                                                                <a class="btn btn-success btn-sm mx-1"
                                                                    href="{{ route('orders.edit', ['encryptedOrderId' => encrypt($data->order_id)]) }}"
                                                                    title="Xem chi tiết">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $stt++; 
                                                    @endphp
                                                    @endforeach
                                                @endif  
                                            </tbody>
                                        </table>
                                    </div>
                                    {{$orderSuccess->onEachSide(1)->links('backend.layouts.partials.pagination'),['page' => 'order_success']}}
                                </div>
                                <div class="tab-pane fade" id="navs-pills-top-deli" role="tabpanel">
                                    <div class="table-responsive text-nowrap">
                                        <table class="table table-bordered w-100" id="myTable">
                                            <thead>
                                                <tr>
                                                    <th class="text-center">
                                                        STT
                                                    </th>
                                                    <th class="text-center">
                                                       <a class="tab-sort" href="?sort-by=order_code&sort-type={{ $orderBy === 'order_code' ? $orderType : 'desc' }}">
                                                            Mã đơn hàng
                                                        </a>
                                                    </th>                            
                                                    <th class="text-center">
                                                        Tên người đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Tổng tiền
                                                    </th>
                                                    <th class="text-center">
                                                        Ngày đặt
                                                    </th>
                                                    <th class="text-center">
                                                        Trạng thái
                                                    </th>
                                                    <th class="text-center" style="width: 100px;">Thao tác</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $stt = $orderDeli->firstItem();
                                                @endphp
                                                @if($orderDeli->isEmpty())
                                                    <tr>
                                                        <td colspan="7" class="text-center">
                                                            Không có dữ liệu để hiển thị.
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach($orderDeli as $data)
                                                    <tr>
                                                        <td class="text-center">
                                                            {{$stt}}
                                                        </td>
                                                        <td>
                                                            {{$data->order_code}}
                                                        </td>
                                                        <td class="pro-name">
                                                            {{$data->order_name}}
                                                        </td>
                                                        <td class="pro-name" style="text-align:right">{{number_format($data->order_total,0,',','.')}} VNĐ</td>
                                                        <td class="text-center">
                                                            {{date('d-m-Y', strtotime($data->order_date))}}
                                                        </td>
                                                        <td class="text-center">
                                                            @if($data->order_status==0)
                                                                <span style="color:darkblue">Đơn hàng mới</span>
                                                            @elseif($data->order_status==1)
                                                                @if ($data->order_delivery_status == 1)
                                                                    <span>Vận chuyển</span>
                                                                @else
                                                                    <span style="color: green">Đã xác nhận</span>
                                                                @endif
                                                            @elseif($data->order_status==2)
                                                                <span style="color: red">Đã hủy</span>
                                                            @elseif($data->order_status==3)
                                                                <span style="color: orange">Hoàn hàng</span>
                                                            @elseif($data->order_status==10)
                                                                <span style="color:chocolate">Thành công</span>
                                                            @endif
                                                        </td>
                                                        
                                                        <td class="text-center">
                                                            <div>
                                                                <a class="btn btn-success btn-sm mx-1"
                                                                    href="{{ route('orders.edit', ['encryptedOrderId' => encrypt($data->order_id)]) }}"
                                                                    title="Xem chi tiết">
                                                                    <i class="fas fa-eye"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @php
                                                        $stt++; 
                                                    @endphp
                                                    @endforeach
                                                @endif  
                                            </tbody>
                                        </table>
                                    </div>
                                    {{$orderDeli->onEachSide(1)->links('backend.layouts.partials.pagination'),['page' => 'order_deli']}}
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        
@endsection
