@extends('backend.index')

@section('title')
    Thống kê doanh thu
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('admin.revenue')}}" class="tab-sort">Tổng quát</a></span></h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Doanh thu và lợi nhuận theo ngày</h5>
            <div class="px-4">
                <div class="dropdown mt-2">
                    <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <div class="dropdown-menu mb-0 pb-0" aria-labelledby="cardOpt1" id="myDropdown">
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
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">
                                STT
                            </th>
                            <th class="text-center">
                                Ngày
                            </th>
                            <th class="text-center">
                                Doanh thu
                            </th>
                            <th class="text-center">
                                Lợi nhuận
                            </th>
                            <th class="text-center">
                                Số đơn hàng
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = $statistic->firstItem();
                        @endphp
                        @if($statistic->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach ($statistic as $data)
                                <tr>
                                    <td class="text-center">
                                        {{$stt}}
                                    </td>
                                    <td class="text-center">
                                        {{ date('d/m/Y', strtotime($data->order_date))}}
                                    </td>
                                    <td class="text-center">{{number_format($data->sales,0,",",".").' VNĐ' }}</td>
                                    <td class="text-center">{{number_format($data->profit,0,",",".").' VNĐ' }}</td>
                                    <td class="text-center">
                                        {{$data->order_total}}
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
            {{$statistic->onEachSide(1)->links('backend.layouts.partials.pagination')}}
        </div>
        <div class="card-footer">
            <span>Doanh thu: <b><?php
                $total = 0;
                foreach ($statistic as $data) {
                    $total += $data->sales;
                }
                echo number_format($total,0,",",".").' VNĐ';
            ?></span> </b><br>
            <span>Lợi nhuận:
                <b><?php
                $total = 0;
                foreach ($statistic as $data) {
                    $total += $data->profit;
                }
                echo number_format($total,0,",",".").' VNĐ';
            ?></b>
            </span>
        </div>
    </div>
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Tổng kết</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">
                               Thời gian
                            </th>
                            <th class="text-center">
                                Doanh thu
                            </th>
                            <th class="text-center">
                                Lợi nhuận
                            </th>
                            <th class="text-center">
                               Xuất báo cáo
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">
                                Hôm nay
                            </td>
                            <td class="text-center">
                                <?php
                                    $totalday = 0;
                                    foreach ($revenueday as $dataday) {
                                        $totalday += $dataday->sales;
                                    }
                                    echo number_format($totalday,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $totalday = 0;
                                    foreach ($revenueday as $dataday) {
                                        $totalday += $dataday->profit;
                                    }
                                    echo number_format($totalday,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <form action="{{route('export.scvday')}}" method="POST" enctype="multipart/form-data" class="">
                                    @csrf
                                    <button type="submit" name="export_scv" class="btn btn-success btn-sm m-1" title="Doanh thu theo ngày" data-bs-toggle="modal" >
                                        <i class="fas fa-download"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center">
                                7 ngày qua
                            </td>
                            <td class="text-center">
                                <?php
                                    $totals = 0;
                                    foreach ($revenueweek as $datas) {
                                        $totals += $datas->sales;
                                    }
                                    echo number_format($totals,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $totals = 0;
                                    foreach ($revenueweek as $datas) {
                                        $totals += $datas->profit;
                                    }
                                    echo number_format($totals,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <form action="{{route('export.scvweek')}}" method="POST" enctype="multipart/form-data" class="">
                                    @csrf
                                    <button type="submit" name="export_scv" class="btn btn-success btn-sm m-1" title="Doanh thu 7 ngày" data-bs-toggle="modal" >
                                        <i class="fas fa-download"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center">
                                Tháng này
                            </td>
                            <td class="text-center">
                                <?php
                                    $totalmonth = 0;
                                    foreach ($revenuemonth as $datamonth) {
                                        $totalmonth += $datamonth->sales;
                                    }
                                    echo number_format($totalmonth,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $totalmonth = 0;
                                    foreach ($revenuemonth as $datamonth) {
                                        $totalmonth += $datamonth->profit;
                                    }
                                    echo number_format($totalmonth,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <form action="{{route('export.scvmonth')}}" method="POST" enctype="multipart/form-data" class="">
                                    @csrf
                                    <button type="submit" name="export_scv" class="btn btn-success btn-sm m-1" title="Doanh thu tháng trước" data-bs-toggle="modal" >
                                        <i class="fas fa-download"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center">
                                Tháng trước
                            </td>
                            <td class="text-center">
                                <?php
                                    $totalmonthp = 0;
                                    foreach ($revenuemonthprev as $datamonthp) {
                                        $totalmonthp += $datamonthp->sales;
                                    }
                                    echo number_format($totalmonthp,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $totalmonthp = 0;
                                    foreach ($revenuemonthprev as $datamonthp) {
                                        $totalmonthp += $datamonthp->profit;
                                    }
                                    echo number_format($totalmonthp,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <form action="{{route('export.scvmonthprev')}}" method="POST" enctype="multipart/form-data" class="">
                                    @csrf
                                    <button type="submit" name="export_scv" class="btn btn-success btn-sm m-1" title="Doanh thu tháng trước" data-bs-toggle="modal" >
                                        <i class="fas fa-download"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center">
                               Năm {{ now()->year }}
                            </td>
                            <td class="text-center">
                                <?php
                                    $totaly = 0;
                                    foreach ($revenueyear as $datay) {
                                        $totaly += $datay->sales;
                                    }
                                    echo number_format($totaly,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $totaly = 0;
                                    foreach ($revenueyear as $datay) {
                                        $totaly += $datay->profit;
                                    }
                                    echo number_format($totaly,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <form action="{{route('export.scvyear')}}" method="POST" enctype="multipart/form-data" class="">
                                    @csrf
                                    <button type="submit" name="export_scvyear" class="btn btn-success btn-sm m-1" title="Doanh thu năm" data-bs-toggle="modal" >
                                        <i class="fas fa-download"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center">
                                Tất cả thời gian
                            </td>
                            <td class="text-center">
                                <?php
                                    $total = 0;
                                    foreach ($revenueall as $data) {
                                        $total += $data->sales;
                                    }
                                    echo number_format($total,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $total = 0;
                                    foreach ($revenueall as $dataq) {
                                        $total += $dataq->profit;
                                    }
                                    echo number_format($total,0,",",".").' VNĐ';
                                ?>
                            </td>
                            <td class="text-center">
                                <form action="{{route('export.scv')}}" method="POST" enctype="multipart/form-data" class="">
                                    @csrf
                                    <button type="submit" name="export_scv" class="btn btn-success btn-sm m-1" title="Doanh thu 365 ngày" data-bs-toggle="modal" >
                                        <i class="fas fa-download"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
    
        </div>
    </div>
   
@endsection