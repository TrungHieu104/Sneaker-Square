@extends('backend.index')

@section('title')
    Giới thiệu
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Cấu hình chung /</span> <a href="{{route('about.index')}}" class="tab-sort">Giới thiệu</a></h4>
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Giới thiệu</h5>
        </div> 
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center" style="max-width: 200px;">
                                    Thông tin
                            </th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $stt = $about->firstItem();
                        @endphp
                        @if($about->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($about as $data)
                                <tr>
                                    <td class="text-center">
                                        {{$stt}}
                                    </td>
                                    <td class="px-3">
                                        - Tên: <b>{{$data->faq_name}}</b><br>
                                        - Tác giả: <b>{{$data->faq_created_by}}</b><br>
                                        - Chỉnh sửa bởi: <b>{{$data->faq_updated_by}}</b><br>
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            <button type="button" class="p-2 btn btn-success btn-sm m-1" title="Xem" data-bs-toggle="modal" data-bs-target="#faqModal{{$data->faq_id}}" data-faq-id="{{$data->faq_id}}">
                                                <i class="fas fa-eye"></i>
                                            </button>                                        
                                            <a class="btn btn-primary btn-sm m-1 p-2" href="{{route('abouts.edit', ['encryptedAboutId' => encrypt($data->faq_id)])}}" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                           
                                <!--Modal-->
                                <div class="modal fade" id="faqModal{{$data->faq_id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                            <input type="hidden" value="{{$data->faq_id}}">    
                                            <h1 class="modal-title fs-5 text-center" id="exampleModalLabel">#{{$stt}}</h1>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body view_faq_data">
                                                <h3 class="text-center">{{$data->faq_name}}</h3>
                                                <div class="row">
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="author" class="form-label">Tác giả</label>
                                                        <input type="text" id="author" value="{{$data->faq_created_by}}" class="form-control" readonly>
                                                    </div>
                                                    <div class="col-lg-6 mb-3">
                                                        <label for="author-edit" class="form-label">Chỉnh sửa bởi</label>
                                                        <input type="text" id="author-edit" value="{{$data->faq_updated_by}}" class="form-control" readonly>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="seo-keyword" class="form-label">SEO Keywords</label>
                                                    <input type="text" id="seo-keyword" value="{{$data->faq_meta_keywords}}" class="form-control" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="seo-description" class="form-label">SEO Description</label>
                                                    <textarea type="text" id="seo-description" class="form-control" readonly col="8">{{$data->faq_meta_description}}</textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="about-content" class="form-label">Nội dung</label>
                                                    <textarea name="" class="form-control" id="about-content" cols="" rows="20" readonly>{!! strip_tags($data->faq_content) !!}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                            </div>
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
        </div>
    </div>
    
@endsection