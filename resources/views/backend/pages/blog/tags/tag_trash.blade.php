@extends('backend.index')

@section('title')
    Thùng rác Tags
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Bài viết /</span> Thẻ tags</h4>
    <!-- BcateSlideed Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý thẻ tag</h5>
            <div class="px-4">
                <a href="{{route('tags.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
            
        </div> 
        <div class="card-body">
            @if ($tagTrash->isNotEmpty())
                <div class="d-flex mb-3 gap-3">
                    <a href="{{route('tag.restoreAll')}}" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></a>
                    <a href="{{route('tag.deleteAll')}}" class="btn btn-danger mt-1 mb-2">Xóa tất cả</a>
                </div>
            @endif
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">STT</th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=tag_content&sort-type={{ $orderBy === 'tag_content' ? $orderType : 'desc' }}">
                                    Nội dung thẻ tag
                                </a>
                            </th>
                            <th class="text-center">Slug thẻ</th>
                            <th class="text-center" style="width: 100px;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($tagTrash->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center">
                                    Thùng rác rỗng.
                                </td>
                            </tr>
                        @else
                            @php
                                $stt = $tagTrash->firstItem();
                            @endphp
                            @foreach($tagTrash as $tgT)
                            <tr>
                                <td class="text-center">{{$stt}}</td>
                                <td class="px-3 text-center">
                                    {{$tgT->tag_content}}
                                </td>
                                <td class="px-3 text-center">
                                    {{$tgT->tag_slug}}
                                </td>
                                <td class="text-center">
                                    <div>
                                        <a class="p-2 btn btn-success btn-sm m-1"
                                            href="{{route('tag.restore',['id'=>$tgT->tag_id])}}" title="Hoàn tác"><i class="fas fa-trash-restore"></i>
                                        </a>
                                        <form class="d-inline" action="{{route('tag.delete',['id'=>$tgT->tag_id])}}" method="post"> @csrf @method('DELETE')
                                            <button onclick="return confirm('Bạn có chắc muốn xóa thẻ tag vĩnh viễn?')" class="p-2 btn btn-danger btn-sm m-1" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>   
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                {{$tagTrash->onEachSide(1)->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div>
@endsection
