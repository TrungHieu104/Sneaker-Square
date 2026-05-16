<div class="modal fade" id="product-modal-{{$product->pro_id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-center align-middle">  
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body view_faq_data">
                <h3 class="text-center text-wrap"> {{ $product->pro_name }} </h3>
                <form action="{{ route('product.update', $product->pro_id) }}" id="modal-form-{{ $product->pro_id }}" method="POST" enctype="multipart/form-data">
                    @csrf {{ method_field('PUT') }}
                    <input type="hidden" name="pro_id" value="{{ $product->pro_id }}">
                    <div class="card mb-4 card-border-top">
                        <div class="card-body"> 
                            <div class="row">
                                <div class="mb-3">
                                    <label for="path" class="form-label">Đường dẫn</label>
                                    <input type="text" class="form-control" id="path-update-{{$product->pro_id}}" value="{{ $product->pro_slug }}" name="pro_slug" readonly />
                                    <div class="mb-1">
                                        <small class="text-lowercase">{{route('product.detail', '')}}/<small class="fw-bold" id="url-slug-update-{{$product->pro_id}}">{{ $product->pro_slug }}</small> </small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Tên sản phẩm</label>
                                    <input type="text" class="form-control" onkeyup="ChangeToSlugUpdate({{$product->pro_id}});" name="pro_name" id="title-update-{{$product->pro_id}}" value="{{ $product->pro_name }}" />
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label for="title" class="form-label">Mã sản phẩm</label>
                                    <input type="text" class="form-control" name="pro_code" value="{{ $product->pro_code }}"/>
                                </div>  

                                <div class="col">
                                    <label for="pro_date" class="form-label">Ngày bán</label>
                                    <input class="form-control datepicker update-pro-date" autocomplete="off" type="text" name="pro_date" value="{{ date('d-m-Y', strtotime($product->pro_date)) }}" {{ $product->soldProduct ? 'readonly':'' }}/>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label for="title" class="form-label">Giá vốn</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="capital_price" value="{{ $product->capital_price }}"/>
                                        <span class="input-group-text">VNĐ</span>
                                    </div>
                                </div>   
            
                                <div class="col">
                                    <label for="title" class="form-label">Giá bán</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="pro_price" value="{{ $product->pro_price }}"/>
                                        <span class="input-group-text">VNĐ</span>
                                    </div>
                                </div>
            
                                <div class="col">
                                    <label for="title" class="form-label">Giá giảm</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="pro_price_sale" value="{{ $product->pro_price_sale }}"/>
                                        <span class="input-group-text">VNĐ</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col">
                                    <label for="cate-product" class="form-label">Danh mục</label>
                                    <div class="box">
                                        <select class="form-select" id="cate-product" name="cate_id">
                                            <option value="">---Danh mục---</option>
                                            @foreach($allCates as $c)
                                                @if (!is_null($c->cate_parent_id))
                                                    <option value="{{ $c->cate_id }}" {{ $product->cate_id == $c->cate_id ? 'selected' : '' }}>
                                                        {{ $c->cate_name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label for="pro_img" class="form-label">Hình ảnh</label>
                                    <input type="file" class="form-control w-100 mb-3" name="pro_img"/>
                                    <img onerror="this.src='/uploads/img_error.jpg'" src="{{ asset($product->pro_img) }}" class="rounded form-control w-25"
                                            alt="{{ $product->pro_name }}">
                                </div> 
                            </div>

                            <div class="row">
                                <div class="mb-3">
                                    <label for="content" class="form-label">Mô tả</label>
                                    <textarea class="ckeditor" id="content" name="pro_description" rows="30" cols="10">
                                        {{ $product->pro_description }}
                                    </textarea>
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
                                <label for="seo-title" class="form-label">SEO Title (vi):</label>
                                <input type="text" class="form-control" id="seo-title" name="pro_SEO_title" value="{{ $product->pro_SEO_title }}" placeholder="SEO Title (vi)&#58;" />
                            </div>
                            <div class="mb-3">
                                <label for="seo-keywords" class="form-label">SEO Keywords (vi):</label>
                                <input type="text" class="form-control" id="seo-keywords" name="pro_meta_keywords" value="{{ $product->pro_meta_keywords }}" placeholder="SEO Keywords (vi)&#58;" />
                            </div>
                            <div class="mb-3">
                                <label for="seo-description" class="form-label">SEO Description (vi):</label>
                                <textarea class="form-control" id="seo-description" name="pro_meta_description" value="{{ $product->pro_meta_description }}" rows="6" placeholder="SEO Description (vi)&#58;"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" form="modal-form-{{ $product->pro_id }}" data-bs-dismiss="modal" title="Cập nhật">Cập nhật</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" title="Đóng">Đóng</button>
            </div>
        </div>
    </div>
</div>