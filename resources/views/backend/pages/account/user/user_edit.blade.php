@extends('backend.index')

@section('title')
    Chỉnh sửa tài khoản
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('account.user')}}" class="tab-back">Danh sách / </a></span> Chỉnh sửa</h4>
    <!-- Bordered Table --> 
    <form action="/admin/accounts/{{$accountUser->user_id}}" method="post">
        @csrf {{method_field('PUT')}}
        <input type="hidden" name="id" value="{{$accountUser->user_id}}">
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Quản lý tài khoản</h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <button type="submit" class="btn btn-success px-5">Lưu</button>
                        <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="image-product" class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" name="name" value="{{$accountUser->name}}" readonly/>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="cate-product" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" autocomplete="off" value="{{$accountUser->username}}" readonly/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="cate-product" class="form-label">Email</label>
                        <input type="text" class="form-control" id="email" name="email" autocomplete="off" value="{{$accountUser->email}}" readonly/>
                    </div> 
                    <div class="col-lg-6 mb-3 pl-3">
                        <label for="title" class="form-label">Trạng thái</label><br>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" name="hid" type="radio" value="1" id="defaultCheck3" {{$accountUser->user_status == 1? "checked":""}} />
                                <label class="form-check-label" for="defaultCheck3"> Kích hoạt </label>
                            </div>
                            <div class="form-check">
                                <input onclick="return confirm('Bạn có chắc muốn vô hiệu hóa user này?')" class="form-check-input" name="hid" type="radio" value="0" id="defaultCheck4" {{$accountUser->user_status == 0? "checked":""}}/>
                                <label class="form-check-label" for="defaultCheck4"> Vô hiệu </label>
                            </div>
                        </div>
                    </div>   
                </div>
                
            </div>
        </div>
    </form>
    <div class="card mb-4">
        @php 
            $total = $Order->total();
        @endphp
       
        <div class="card-header card-border-top">
            <div class="row">
                <div class="col-lg-6 mb-3 d-flex align-items-center">
                    <h6 class="fs-4 text-primary my-0">Số đơn hàng đã đặt: {{$total}}</h6>
                </div>
            </div>
        </div>
        <hr class="m-0">
        <div class="card-body">
            <div class="row">
                <div class="table-responsive text-nowrap">
                    <table class="table table-bordered w-100">
                        <thead class="text-center">
                            <tr>
                                <th>STT</th>
                                <th>Mã đơn hàng</th>
                                <th>Người nhận</th>
                                <th>Thành tiền</th>
                                <th>Ngày đặt</th>
                                <th>Trạng thái</th >
                                <th>Thao tác</th>
                               
                                {{-- <th>Thành tiền</th> --}}
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $stt = $Order->firstItem();
                            @endphp
                            @if($Order->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center">
                                        Không có dữ liệu để hiển thị.
                                    </td>
                                </tr>
                            @else
                                @foreach($Order as $data)
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
                                        <td class="pro-name" style="text-align: right">{{number_format($data->order_total,0,',','.')}}đ</td>
                                        <td>
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
                                                <form class="d-inline" action="" method="POST">
                                                    @csrf 
                                                    <a class="btn btn-success btn-sm mx-1"
                                                        href="{{ route('orders.edit', ['encryptedOrderId' => encrypt($data->order_id)]) }}"
                                                        title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </form>
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
                    {{$Order->links('backend.layouts.partials.pagination')}}
                
                        
                    
                </div>
            </div>
    
    </div>
@endsection
