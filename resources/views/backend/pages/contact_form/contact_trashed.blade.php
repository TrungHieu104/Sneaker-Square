@extends('backend.index')

@section('title')
    Liên hệ
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('contact-form.index')}}" class='tab-back'>Liên hệ /</a></span> Thùng rác</h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý liên hệ</h5>
            <div class="px-4">
                <a href="{{route('contact-form.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
            </div>
        </div> 
        <div class="card-body">
            <div class="d-flex gap-3 mb-3">
                <a href="{{route('contact-form.restoreAll')}}" class="btn btn-success mt-1 mb-2">Khôi phục tất cả &nbsp;<i class="fas fa-recycle"></i></a>
                <a href="{{ route('contact-form.delete.all') }}" title="Xóa" class="btn btn-danger mt-1 mb-2" onclick="return confirm('Bạn có chắc muốn xóa tất cả liên hệ vĩnh viễn?')">Xóa tất cả &nbsp;<i class="fas fa-trash"></i></a>
            </div>
            <div class="table-responsive text-nowrap">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>STT</th>
                    <th>Họ và tên</th>
                    <th>Tiêu đề</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Trạng thái</th>
                    <th>Ngày gửi</th>
                    <th>Chi tiết</th>
                    <th>Hành động</th>
                </tr>
                </thead>
                @if($contactTrash->isEmpty())
                    <tbody>
                        <tr>
                            <td colspan="9">
                                <p class="text-center m-auto">Không có liên lạc nào mới</p>
                            </td>
                        </tr>
                    </tbody>
                @else
                    <tbody>
                        @foreach($contactTrash as $ct)
                        <tr>
                            <td>
                                {{ $trashed++ }}
                            </td>
                            <td>
                                <strong>{{$ct->name}}</strong>
                            </td>
                            <td>
                                {{$ct->title}}
                            </td>
                            <td>
                                {{$ct->email}}
                            </td>
                            <td>
                                {{$ct->phone}}
                            </td>
                            <td>
                                @if($ct->status == 0)
                                <span class="badge bg-label-danger me-1">Chưa xử lý</span>
                                @elseif($ct->status == 1)
                                <span class="badge bg-label-warning me-1">Đang xử lý</span>
                                @else($ct->status == 2)
                                <span class="badge bg-label-success me-1">Đã xử lý</span>
                                @endif
                            </td>
                            <td>{{date('d/m/Y', strtotime($ct->created_at))}}</td>
                            <td>
                                <a class="btn btn-success  btn-sm mx-1" href="" title="Xem" data-bs-toggle="modal" data-bs-target="#ContactModal{{$ct->id}}"><i class="far fa-eye"></i></a>
                                <!-- Modal -->
                                <div class="modal fade" id="ContactModal{{$ct->id}}" tabindex="-1" aria-labelledby="ContactModal{{$ct->id}}Label" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="ContactModal{{$ct->id}}Label">Liên hệ chi tiết</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                    <span>- <b>{{$ct->name}}</b></span> <br>
                                    <span>- {{$ct->email}}</span> <br>
                                    <span>- {{$ct->phone}}</span> <br>
                                    <textarea class="form-control" readonly="">{{$ct->content}}</textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                    </div>
                                    </div>
                                </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex">
                                    <form action="{{route('contact-form.restore',$ct->id)}}">
                                        <button class="btn btn-success  btn-sm mx-1" href="" title="Khôi phục"><i class="fas fa-trash-restore"></i></i></button>
                                    </form>
                                    <form action="{{route('contact-form.forceDelete',$ct->id)}}">
                                        <button class="btn btn-danger  btn-sm mx-1" href="" title="xóa" onclick="return confirm('Bạn có chắc muốn xóa liên hệ này vĩnh viễn?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        {{$contactTrash->onEachSide(1)->links('backend.layouts.partials.pagination')}}
                    </tbody>
                @endif
            </table>
        </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
