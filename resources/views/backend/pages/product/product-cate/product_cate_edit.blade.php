<div class="modal fade" id="cate-modal-{{$cates->cate_id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-center align-middle">  
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body view_faq_data">
                <h3 class="text-center"> {{ $cates->cate_name }} </h3>
                <form action="{{ route('product-category.update', $cates->cate_id) }}" id="modal-form-{{ $cates->cate_id }}" method="POST" enctype="multipart/form-data">
                    @csrf {{ method_field('PUT') }}
                    <input type="hidden" name="cate_id" value="{{ $cates->cate_id }}">
                    <div class="card mb-4 card-border-top">
                        <div class="card-body"> 
                            <div class="row">
                                <div class="mb-3">
                                    <label for="path" class="form-label">Đường dẫn</label>
                                    <input type="text" class="form-control" id="path-update-{{$cates->cate_id}}" value="{{ $cates->cate_slug }}" name="cate_slug" readonly />
                                    <div class="mb-1">
                                        <small class="text-lowercase">{{route('product.by.cate', '')}}/<small class="fw-bold" id="url-slug-update-{{$cates->cate_id}}">{{ $cates->cate_slug }}</small> </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Tên danh mục</label>
                                    <input type="text" class="form-control" onkeyup="ChangeToSlugUpdate({{$cates->cate_id}});" name="cate_name" id="title-update-{{$cates->cate_id}}" placeholder="Tên danh mục" value="{{ $cates->cate_name }}" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Số thứ tự</label>
                                    <input type="number" class="form-control w-100" id="quantity" name="cate_sort" value="{{ $cates->cate_sort }}"/>
                                </div>   
                            </div>

                            <div class="row">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Hình ảnh</label>
                                    <input type="file" class="form-control w-100 mb-3" name="cate_img" value="{{ $cates->cate_img }}"/>
                                    <img onerror="this.src='/uploads/img_error.jpg'" src="{{ asset($cates->cate_img) }}" class="rounded form-control w-25"
                                            alt="{{ $cates->cate_name }}">
                                </div>   
                            </div>

                            <div class="row">
                                <div class="col mb-3">
                                    <label for="cate-product" class="form-label">Danh mục gốc</label>
                                    <div class="box">
                                        <select class="form-select" id="cate-product" name="cate_parent_id">
                                            <option value="">---Danh mục gốc---</option>
                                            @foreach($cate as $data)
                                                @php
                                                $indentation = str_repeat('---', $data->level);
                                                $selected = $cates->cate_parent_id == $data->cate_id ? 'selected' : '';
                                                @endphp
                                                @if (is_null($data->cate_parent_id))
                                                    @if ($cates->cate_id != $data->cate_id)
                                                        <option value="{{ $data->cate_id }}" {{ $selected }}>
                                                            {{ $indentation . $data->cate_name }}
                                                        </option>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>  
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header card-border-top">
                            Nội dung SEO
                        </div>
            
                        <hr class="m-0">
            
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="seo-keywords" class="form-label">SEO Keywords (vi):</label>
                                <input type="text" class="form-control" value="{{ $cates->cate_meta_keywords }}" name="cate_meta_keywords" id="seo-keywords" placeholder="SEO Keywords (vi)&#58;" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                @if (!count($cates->getChildCate))
                    <button class="btn btn-primary" form="modal-form-{{ $cates->cate_id }}" data-bs-dismiss="modal" title="Cập nhật">Cập nhật</button>
                @endif
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" title="Đóng">Đóng</button>
            </div>
        </div>
    </div>
</div>