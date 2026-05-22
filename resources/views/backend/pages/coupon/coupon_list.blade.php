@extends('backend.index')

@section('title')
    Mã giảm giá
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Quản trị /</span> <a href="{{route('coupon.index')}}" class="tab-sort">Mã giảm giá</a></h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý mã giảm giá</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{route('coupon.create')}}" class="btn btn-success mt-1">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
                <a href="{{route('coupon.trashed')}}" class="btn btn-danger mt-1">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a>       
                <div class="dropdown mt-2">
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
        </div> 
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100" id="result">
                    <thead> 
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=coupon_name&sort-type={{ $orderBy === 'coupon_name' ? $orderType : 'desc' }}">
                                    Tiêu đề
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=coupon_code&sort-type={{ $orderBy === 'coupon_code' ? $orderType : 'desc' }}">
                                    Mã CODE
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=coupon_quantity&sort-type={{ $orderBy === 'coupon_quantity' ? $orderType : 'desc' }}">
                                    Số lượng
                                </a>
                            </th>
                            <th class="text-center">                                
                                <a class="tab-sort" href="?sort-by=coupon_value&sort-type={{ $orderBy === 'coupon_value' ? $orderType : 'desc' }}">
                                    Giá trị
                                </a>
                            </th>
                            <th class="text-center">Tình trạng</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead> 
                    <tbody>
                        @php
                            $stt = $coupon->firstItem();
                        @endphp
                        @if($coupon->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($coupon as $data)
                                <tr>
                                    <td class="text-center dx-num">
                                        {{$stt}}
                                    </td>
                                    <td class="px-3 pro-name">
                                        {{$data->coupon_name}}
                                    </td>
                                    <td class="text-center">
                                        @if($data->coupon_used == 0)
                                            <span class="pro-name">{{$data->coupon_code}}</span>
                                        @elseif($data->coupon_used == $max_used)
                                            <span class="pro-name" style="position: relative;">
                                                {{$data->coupon_code}}
                                                <img src="/backend/assets/img/giphy.gif" alt="" class="position-absolute" width="40px" height="40px" style="right: -18px; bottom: 7px;">
                                            </span>
                                        @else
                                            <span class="pro-name">{{$data->coupon_code}}</span>
                                        @endif
                                    </td>
                                    <style></style>
                                    <td class="dx-info text-center">
                                        {{$data->coupon_quantity}}
                                    </td> 
                                    <td class="text-center">
                                        {{($data->coupon_condition == 0) ? $data->coupon_value.'%' : number_format($data->coupon_value,0,",",".").'đ' }}
                                    </td>
                                    <td class="text-center">
                                        @if(date('Y-m-d', strtotime($data->coupon_end)) >= date('Y-m-d', strtotime($today)))
                                            <span style="color: green">Còn hạn</span>
                                        @else
                                            <span style="color: red">Hết hạn</span>
                                        @endif
                                    </td>
                                    <td class="text-center dx-res">
                                        <div>
                                            <button type="button" class="p-2 btn btn-success btn-sm m-1" title="Xem" data-bs-toggle="modal" data-bs-target="#couponModal{{$data->coupon_id}}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a class="p-2 btn btn-warning btn-sm m-1" href="{{route('coupon.edit', ['coupon' => $data->coupon_id])}}" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($data->coupon_quantity <= 0)
                                                <button disabled type="button" class="p-2 btn btn-secondary btn-sm m-1" title="Gửi" data-bs-toggle="modal" data-bs-target="#coupon{{$data->coupon_id}}">
                                                    <i class="fas fa-share"></i>
                                                </button>
                                            @elseif(date('Y-m-d', strtotime($data->coupon_end)) >= date('Y-m-d', strtotime($today)))
                                                <button type="button" class="p-2 btn btn-secondary btn-sm m-1" title="Gửi" data-bs-toggle="modal" data-bs-target="#coupon{{$data->coupon_id}}">
                                                    <i class="fas fa-share"></i>
                                                </button>
                                            @else
                                                <button disabled type="button" class="p-2 btn btn-secondary btn-sm m-1" title="Gửi" data-bs-toggle="modal" data-bs-target="#coupon{{$data->coupon_id}}">
                                                    <i class="fas fa-share"></i>
                                                </button>
                                            @endif
                                            <form class="d-inline" action="{{ route('coupon.softDelete', ['id' => $data->coupon_id]) }}" method="post">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa?')" class="btn btn-danger btn-sm m-1 p-2">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>                                                                                
                                        </div>
                                    </td> 
                                </tr>
                                <div class="modal fade" id="couponModal{{$data->coupon_id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header text-center align-middle">  
                                                <div class="ml-5">
                                                    <h5>#{{$stt}}</h5>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body view_faq_data">
                                                <h4 class="text-center">{{$data->coupon_name}}</h4>
                                                <div class="row">
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id1" class="form-label">Mã CODE</label>
                                                        <input type="text" id="id1" value="{{$data->coupon_code}}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id2" class="form-label">Số lượng mã giảm</label>
                                                        <input type="text" id="id2" value="{{$data->coupon_quantity}}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id3" class="form-label">Đã sử dụng</label>
                                                        <input type="text" id="id3" value="{{$data->coupon_used}}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id4" class="form-label">Ngày khởi tạo</label>
                                                        <input type="text" id="id4" value="{{ date('d-m-Y', strtotime($data->coupon_date))}}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id5" class="form-label">Ngày bắt đầu</label>
                                                        <input type="text" id="id5" value="{{ date('d-m-Y', strtotime($data->coupon_start))}}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id6" class="form-label">Ngày kết thúc</label>
                                                        <input type="text" id="id6" value="{{ date('d-m-Y', strtotime($data->coupon_end))}}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id7" class="form-label">Phương thức giảm giá</label>
                                                        <input type="text" id="id7" value="{{($data->coupon_condition == 0) ? 'Giảm giá theo %' : 'Giảm giá theo tiền' }}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id8" class="form-label">Số giảm</label>
                                                        <input type="text" id="id8" value="{{($data->coupon_condition == 0) ? $data->coupon_value.'%' : number_format($data->coupon_value,0,",",".").'VNĐ' }}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id9" class="form-label">Tình trạng</label>
                                                        <input type="text" id="id9" value="{{ date('d/m/Y', strtotime($data->coupon_end)) >= date('d/m/Y', strtotime($today)) ? 'Còn hạn' : 'Hết hạn' }}" class="form-control text-start" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal fade" id="coupon{{$data->coupon_id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header text-center align-middle">  
                                                <div class="ml-5">
                                                    <h5>#{{$stt}}</h5>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body view_faq_data">
                                                <div class="row">
                                                    <h4 class="text-center pro-name w-100">{{$data->coupon_name}} - {{$data->coupon_code}}</h4>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <label for="id1" class="form-label">Bạn muốn gửi mã giảm cho những đối tượng nào?</label>
                                                    </div>    
                                                </div>
                                            <form action="{{route('sendCoupon')}}" method="POST">
                                                @csrf
                                                <input type="hidden" value="{{$data->coupon_id}}" name="couid">
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-check form-check-inline form-label">
                                                            <input class="form-check-input" type="radio" name="coupon" id="defaultCheck1" value="1" checked>
                                                            <label class="form-check-label form-label" for="defaultCheck1">Tất cả khách hàng</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-check form-check-inline form-label">
                                                            <input class="form-check-input" type="radio" name="coupon" id="defaultCheck2" value="2">
                                                            <label class="form-check-label form-label" for="defaultCheck2">Khách hàng mới (0-1 đơn hàng)</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-check form-check-inline form-label">
                                                            <input class="form-check-input" type="radio" name="coupon" id="defaultCheck3" value="3">
                                                            <label class="form-check-label form-label" for="defaultCheck3">Khách hàng thân thiết (2-5 đơn hàng)</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6">
                                                        <div class="form-check form-check-inline form-label">
                                                            <input class="form-check-input" type="radio" name="coupon" id="defaultCheck4" value="4">
                                                            <label class="form-check-label form-label" for="defaultCheck4">Khách hàng VIP (Trên 5 đơn hàng)</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>
                                            <div class="modal-footer">
                                                <button type="submit" class="btn btn-success">Xác nhận</button>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                            </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @php
                                    $stt++;
                                @endphp
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            {{$coupon->onEachSide(1)->links('backend.layouts.partials.pagination')}}
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
