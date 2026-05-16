@extends('backend.index')

@section('title')
    Hình ảnh sản phẩm
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Sản phẩm /</span> Hình ảnh</h4>
    <!-- Bordered Table -->
    <div class="card mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý hình ảnh</h5>
            <div class="px-4">
                @if (!$allImages->isEmpty())
                    <a href="{{ route('image.trashed', $allImages[0]->getProduct->pro_slug) }}" class="btn btn-danger" title="Thùng rác">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a>
                @endif
            </div>
        </div>

        @include('backend.pages.product.image.product_img_create')

        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">STT</th>
                            <th class="text-center" style="width: 60px;">Hình ảnh</th>
                            <th class="text-center" style="width: 60px;">Hiển thị</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($allImages->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center">
                                    Sản phẩm không có hình ảnh.
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $allImages->firstItem()
                            @endphp
                            @foreach ($allImages as $images)
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

                                    <td class="text-center">
                                        <input name="img_hidden" 
                                                    class="form-check-input"  
                                                    type="checkbox" value="1" id="hidden-{{ $images->img_id }}" 
                                                    {{ $images->img_hidden == 1 ? 'checked' : '' }} 
                                                    data-id="{{ $images->img_id }}" 
                                        /> 
                                    </td>
                                    
                                    <td class="text-center">
                                        <div>
                                            <form class="d-inline" action="{{ route('image.destroy', $images->img_id) }}" method="POST">
                                                @csrf 
                                                @method('DELETE')
                                                <button type='submit' onclick="return confirm('Bạn muốn đưa hình ảnh này vào thùng rác?')"
                                                    class="btn btn-danger btn-sm ms-1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
                {{ $allImages->onEachSide(1)->links('backend.layouts.partials.pagination') }}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
