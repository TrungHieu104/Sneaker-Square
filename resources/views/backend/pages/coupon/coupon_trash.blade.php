@extends('backend.index')

@section('title')
    Mã giảm giá
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('coupon.index')}}" class='tab-back'>Mã khuyến mãi /</a></span> Thùng rác</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý mã khuyến mãi</h5>
            <div class="px-4">
                <a href="{{route('coupon.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            <div class="d-flex gap-3 mb-3">
                <a href="{{route('coupon.restoreAll')}}" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></a>
                <a href="{{ route('coupon.delete.all') }}" title="Xóa" class="btn btn-danger mt-1 mb-2" onclick="return confirm('Bạn có chắc muốn xóa tất cả mã giảm giá vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center">Tên</th>
                            <th class="text-center">Mã code</th>
                            <th class="text-center">Số lượng</th>
                            <th class="text-center">Giá trị</th>
                            {{-- <th class="text-center">Hết hạn</th> --}}
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = 1;
                        @endphp
                        @if($couponTrash->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($couponTrash as $data)
                                <tr>
                                    <td class="text-center dx-num">
                                        {{$stt}}
                                    </td>
                                    <td class="px-3 pro-name">
                                        {{$data->coupon_name}}
                                    </td>
                                    <td class="dx-info text-center">
                                        {{$data->coupon_code}}
                                    </td>
                                    <td class="dx-info text-center">
                                        {{$data->coupon_quantity}}
                                    </td>
                                    <td class="text-center">
                                        {{($data->coupon_condition == 0) ? $data->coupon_value.'%' : number_format($data->coupon_value,0,",",".").'đ' }}
                                    </td>
                                    <td class="text-center dx-res">
                                        <div>
                                            <a class="btn btn-success btn-sm mx-1"
                                                href="{{route('coupon.restore',['id'=>$data->coupon_id])}}" title="Sao lưu">
                                                <i class="fas fa-trash-restore"></i>
                                            </a>
                                            <a href="{{route('coupon.delete',['id'=>$data->coupon_id])}}"
                                                onclick="return confirm('Bạn có chắc muốn xóa danh mục vĩnh viễn?')"
                                                class="btn btn-danger btn-sm ms-1"
                                                title="Xóa">
                                                <i class="fas fa-trash"></i>
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
                {{$couponTrash->onEachSide(1)->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
