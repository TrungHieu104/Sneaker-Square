<div class="modal fade" id="color-modal-{{$color->color_id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-center align-middle">  
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body view_faq_data">
                <h3 class="text-center"> Màu {{ $color->color_vn }} </h3>
                <form action="{{ route('stock.update.color', $color->color_id) }}" id="modal-form-{{ $color->color_id }}" method="POST" enctype="multipart/form-data">
                    @csrf {{ method_field('PUT') }}
                    <input type="hidden" name="color_id" value="{{ $color->color_id}}">
                    <div class="card mb-4 card-border-top">
                        <div class="card-body"> 
                            <div class="row">
                                <div class="mb-3">
                                    <label for="path" class="form-label">Tên màu bằng tiếng Anh</label>
                                    <input type="text" class="form-control" value="{{ $color->color }}" name="color"/>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Tên màu bằng tiếng Việt</label>
                                    <input type="text" class="form-control" name="color_vn" placeholder="Tên danh mục" value="{{ $color->color_vn }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-primary" form="modal-form-{{ $color->color_id }}" data-bs-dismiss="modal" title="Cập nhật">Cập nhật</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" title="Đóng">Đóng</button>
            </div>
        </div>
    </div>
</div>