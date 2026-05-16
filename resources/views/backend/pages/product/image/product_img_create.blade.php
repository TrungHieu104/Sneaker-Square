<div class="d-flex justify-content-between align-items-center">
    <div class="px-4 w-50">
        <form action="{{ route('image.store') }}" method="POST" enctype="multipart/form-data">
            @csrf {{ method_field('POST') }}
            {{-- <div class="card mb-4 card-border-top"> --}}
                {{-- <div class="card-body"> --}}
                    <div class="row">
                        <div class="mb-3">
                            <input type="hidden" class="form-control" name="pro_id" value="{{ ($allImages[0]? $allImages[0]->getProduct->pro_id:$proId) }}" id="title" readonly/>
                        </div>
                    </div>
    
                    <div class="row">
                        <div class="mb-3">
                            <label for="title" class="form-label">Hình ảnh</label>
                            <input type="file" class="form-control w-100" id="quantity" name="img_name[]" multiple />
                            @if ($errors->has("img_name")) 
                                @foreach ($errors->get("img_name") as $error) 
                                    <small class="text-danger fst-italic">
                                        {{ $error }}
                                    </small> 
                                @endforeach               
                            @endif
                            @if ($errors->has("img_name.*")) 
                                @foreach ($errors->all() as $error) 
                                    <small class="text-danger fst-italic">
                                        {{ $error }}
                                    </small> 
                                @endforeach               
                            @endif
                        </div>   
                    </div>
    
                    <div class="row">
                        <div class="mb-3">
                            <label for="title" class="form-label">Hiển thị</label><br>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" name="img_hidden" type="radio" value="1" id="hidden-false" {{(old('img_hidden') == '1') ? 'checked' : 'checked'}} />
                                    <label class="form-check-label" for="hidden-false"> Hiện </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" name="img_hidden" type="radio" value="0" id="hidden-true" {{(old('img_hidden') == '0') ? 'checked' : ''}} />
                                    <label class="form-check-label" for="hidden-true"> Ẩn </label>
                                </div>
                            </div>
                        </div> 
                    </div>
    
            <div class="mb-4">
                <div class="mb-3 d-flex gap-3">
                    <button type="submit" class="btn btn-success px-5">Thêm</button>
                    <button type="reset" class="btn btn-primary px-5">Làm lại</button>
                </div>
            </div>
        </form>
    </div>
</div>