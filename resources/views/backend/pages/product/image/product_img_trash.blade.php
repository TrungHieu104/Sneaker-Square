@extends('backend.index')

@section('title')
    Danh sách hình ảnh sản phẩm | Thùng rác
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm / Hình ảnh /</span> Thùng rác</h4>
    <!-- Bordered Table -->
    <div class="card mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý hình ảnh sản phẩm</h5>
            <div class="px-4">
                <a href="{{ route('image.show', ($imgTrash[0]? $imgTrash[0]->getProduct->pro_slug:$proSlug)) }}" title="Hình ảnh sản phẩm" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            @if($imgTrash->isNotEmpty())
                <a href="{{ route('image.restore.all', $imgTrash[0]->getProduct->pro_slug) }}" title="Khôi phục" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-trash-restore"></i></a>
                <a href="{{ route('image.delete.all', $imgTrash[0]->getProduct->pro_slug) }}" title="Xóa" class="btn btn-danger mt-1 mb-2" onclick="return confirm('Bạn có chắc muốn xóa tất cả sản phẩm vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
            @endif
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">STT</th>
                            <th class="text-center" style="width: 60px;">Hình ảnh</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($imgTrash->isEmpty())
                            <tr>
                                <td colspan="3" class="text-center">
                                    Không có hình ảnh trong thùng rác.
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $imgTrash->firstItem()
                            @endphp
                            @foreach ($imgTrash as $images)
                                <tr>
                                    <td class="text-center">
                                        {{ $stt }}
                                    </td>

                                    <td class="px-3">
                                        <div class="img-container_admin mx-auto border p-1 rounded">
                                            <img onerror="this.src='/uploads/img_error.jpg'" src="{{ asset($images->img_name) }}" class="img-list_admin rounded"
                                                alt="{{ $images->getProduct->pro_name }}">
                                        </div>
                                    </td>
                                    
                                    <td class="text-center dx-res">
                                        <div>
                                            <a class="btn btn-success btn-sm mx-1" title="Khôi phục"
                                                href="{{route('image.restore', $images->img_id)}}">
                                                <i class="fas fa-trash-restore"></i>
                                            </a>

                                            <a href="{{route('image.delete', $images->img_id)}}"
                                                onclick="return confirm('Bạn có chắc muốn xóa hình ảnh này vĩnh viễn?')"
                                                class="btn btn-danger btn-sm ms-1"
                                                title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </a>                                        
                                        </div>
                                    </td>
                                </tr>
                                @php
                                    $stt++
                                @endphp
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            <div>
                {{ $imgTrash->links('backend.layouts.partials.pagination') }}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
