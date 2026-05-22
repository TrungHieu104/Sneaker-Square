@extends('backend.index')

@section('title')
    Nội dung Slide
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Slide /</span> <a href="{{ route('promotion.index') }}"
            class="tab-sort">Nội dung</a></h4>
    <!-- BcateSlideed Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý nội dung Slide</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{ route('promotion.create') }}" class="btn btn-success mt-1">Thêm mới &nbsp;<i
                        class="bi bi-plus-circle"></i></a>
                <a href="{{ route('promotion.trashed') }}" class="btn btn-danger mt-1">Thùng rác &nbsp;<i
                        class='bx bxs-trash'></i></a>
            </div>

        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">
                                {{-- <a class="tab-sort" href="?sort-by=promotion_id&sort-type={{ $orderBy === 'promotion_id' ? $orderType : 'asc' }}"> --}}
                                Hình ảnh
                                {{-- </a> --}}
                            </th>
                            <th class="text-center">
                                <a class="tab-sort"
                                    href="?sort-by=promotion_name&sort-type={{ $orderBy === 'promotion_name' ? $orderType : 'desc' }}">
                                    Tiêu đề
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort"
                                    href="?sort-by=promotion_sort&sort-type={{ $orderBy === 'promotion_sort' ? $orderType : 'desc' }}">
                                    Thứ tự
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort"
                                    href="?sort-by=promotion_hidden&sort-type={{ $orderBy === 'promotion_hidden' ? $orderType : 'desc' }}">
                                    Hiển thị
                                </a>
                            </th>
                            <th class="text-center">Tình trạng</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>

                        @if ($promotion->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach ($promotion as $data)
                                <tr>
                                    <td class="text-center">
                                        <div class="img-container_admin mx-auto border p-1 rounded">
                                            <img onerror="this.src='/uploads/img_error.jpg'"
                                                src="{{ asset($data->promotion_img) }}" class="img-list_admin rounded"
                                                alt="">

                                        </div>
                                    </td>
                                    <td class="px-3 pro-name">
                                        {{ $data->promotion_name }}
                                    </td>
                                    <td class="px-3 text-center">
                                        {{ $data->promotion_sort }}
                                    </td>
                                    <td class="text-center">
                                        <input name="p-status" class="form-check-input" type="checkbox" value="1"
                                            id="checkbox_{{ $data->promotion_id }}"
                                            {{ $data->promotion_hidden == 1 ? 'checked' : '' }}
                                            data-id="{{ $data->promotion_id }}" />
                                    </td>
                                    <td class="px-3 text-center">
                                        @if (date('Y-m-d', strtotime($data->promotion_end)) >= date('Y-m-d', strtotime($today)))
                                            <span style="color: green">Còn hạn</span>
                                        @else
                                            <span style="color: red">Hết hạn</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            <button type="button" class="btn btn-success btn-sm ms-1 p-2" title="Chi tiết" data-bs-toggle="modal" data-bs-target="#faqModal{{ $data->promotion_id }}" data-faq-id="{{ $data->faq_id }}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a class="btn btn-primary btn-sm ms-1 p-2" href="{{ route('promotion.edit', $data->promotion_id) }}" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form class="d-inline" action="{{ route('promotion.softDelete', $data->promotion_id) }}" method="post">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm ms-1 p-2" onclick="return confirm('Bạn có chắc muốn xóa?')" title="Xóa">
                                                    <i class="fas fa-trash"></i> 
                                                </button>
                                            </form>
                                                
                                        </div>
                                    </td>
                                </tr>
                                <!--Modal-->
                                <div class="modal fade" id="faqModal{{ $data->promotion_id }}" tabindex="-1"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h1 class="modal-title fs-5 text-center" id="exampleModalLabel">
                                                    #{{ $data->promotion_sort }}</h1>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body view_faq_data">
                                                <div class="row">
                                                    <h4 class="text-center pro-name w-100">{{ $data->promotion_name }}</h4>
                                                
                                                    <div class="col-6 mx-auto">
                                                        <img src="{{ asset($data->promotion_img) }}" alt="" class="img-fluid border rounded">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="id1" class="form-label">Slug</label>
                                                    <input type="text" id="id1" value="{{$data->promotion_link}}" class="form-control" readonly>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id2" class="form-label">Hiển thị</label>
                                                        <input type="text" id="id2" value="{{ $data->promotion_hidden == 0 ? 'Ẩn' : 'Hiện' }}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id3" class="form-label">Ngày tạo</label>
                                                        <input type="text" id="id3" value="{{ date('d/m/Y', strtotime($data->promotion_date)) }}" class="form-control" readonly>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id4" class="form-label">Bắt đầu</label>
                                                        <input type="text" id="id4" value="{{ date('d/m/Y', strtotime($data->promotion_start)) }}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id5" class="form-label">Kết thúc</label>
                                                        <input type="text" id="id5" value="{{ date('d/m/Y', strtotime($data->promotion_end)) }}" class="form-control" readonly>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id6" class="form-label">Danh mục</label>
                                                        <input type="text" id="id6" value="{{ app()->call('App\Http\Controllers\Backend\PromotionAdminController@cateSlide', ['id' => $data->cate_slide_id]) }}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="id7" class="form-label">Tình trạng</label>
                                                        <input type="text" id="id7" value="{{ date('d/m/Y', strtotime($data->promotion_end)) >= date('d/m/Y', strtotime($today)) ? 'Hết hạn' : 'Còn hạn' }}" class="form-control text-start" readonly>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="id8" class="form-label">Ghi chú</label>
                                                    <input type="text" id="id8" value="{{ htmlspecialchars($data->promotion_note ?? 'Không') }}" class="form-control text-start" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="id9" class="form-label">Nội dung</label>
                                                    <textarea name="" class="form-control" id="id9" cols="" rows="8" readonly>{!! strip_tags($data->promotion_content) !!}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Đóng
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
            {{ $promotion->onEachSide(1)->links('backend.layouts.partials.pagination') }}
        </div>
    </div>
@endsection