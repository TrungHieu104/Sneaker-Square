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
                <form action="{{route('coupon.restoreAll')}}" method="post"> @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></button>
                </form>
                <form action="{{ route('coupon.delete.all') }}" method="post" data-confirm="Bạn có chắc muốn xóa tất cả mã giảm giá vĩnh viễn?"> @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger mt-1 mb-2" title="Xóa">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></button>
                </form>
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
                                        <div class="row-actions">
                                            <form action="{{route('coupon.restore',['id'=>$data->coupon_id])}}" method="post"> @csrf @method('PATCH')
                                                <x-admin.row-action action="restore" title="Khôi phục" type="submit" />
                                            </form>
                                            <form action="{{route('coupon.delete',['id'=>$data->coupon_id])}}" method="post" data-confirm="Bạn có chắc muốn xóa mã giảm giá này vĩnh viễn?"> @csrf @method('DELETE')
                                                <x-admin.row-action action="delete" title="Xóa vĩnh viễn" type="submit" />
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
                {{$couponTrash->onEachSide(1)->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
