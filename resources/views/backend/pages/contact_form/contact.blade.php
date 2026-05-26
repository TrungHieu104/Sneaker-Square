@extends('backend.index')

@section('title')
    Liên hệ
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Quản trị /</span> <a href="{{route('contact-form.index')}}" class="tab-sort">Liên hệ</a></h4>
 <!-- Bordered Table -->
<div class="card mb-4 card-border-top">
    <div class="px-4 d-flex justify-content-between gap-3 align-items-center">
        <h5 class="card-header">Quản lý liên hệ</h5>
        <div>
            <a href="{{route('contact-form-trashed')}}" class="btn btn-danger mt-1">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a>
        </div>        
    </div>
    <div class="card-body">
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
                @if($contact->isEmpty())
                    <tbody>
                        <tr>
                            <td colspan="9">
                                <p class="text-center m-auto">Không có liên lạc nào mới</p>
                            </td>
                        </tr>
                    </tbody>
                @else
                    <tbody>
                        @foreach($contact as $ct)
                        <tr>
                            <td>
                                {{ $index++ }}
                            </td>
                            <td>
                                <strong>{{$ct->name}}</strong>
                            </td>
                            <td>
                                {{$ct->title}}
                            </td>
                            <td>
                                <a href="mailto:{{$ct->email}}" class="text-secondary statistical-link">{{$ct->email}}</a>
                            </td>
                            <td>
                                <a href="tel:{{$ct->phone}}" class="text-secondary statistical-link">
                                    {{ '' . substr($ct->phone, 0, 4) . ' ' . substr($ct->phone, 4, 3) . ' ' . substr($ct->phone, 7) }}
                                </a>
                            </td>
                            <td>
                                @if($ct->status == 0)
                                    <span class="badge bg-label-danger me-1">Chưa xử lý</span>
                                @elseif($ct->status == 1)
                                    <span class="badge bg-label-warning me-1">Đang xử lý</span>
                                @else
                                    <span class="badge bg-label-success me-1">Đã xử lý</span>
                                @endif
                            </td>
                            <td>{{date('d/m/Y', strtotime($ct->created_at))}}</td>
                            <td class="text-center">
                                <button class="btn btn-success text-center btn-sm mx-1" title="Xem" data-bs-toggle="modal" data-bs-target="#ContactModal{{$ct->id}}"><i class="far fa-eye"></i></button>
                            </td>
                            <!-- Modal -->
                            <div class="modal fade" id="ContactModal{{$ct->id}}" tabindex="-1" aria-labelledby="ContactModal{{$ct->id}}Label" aria-hidden="true">
                                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                    <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="ContactModal{{$ct->id}}Label">Liên hệ chi tiết</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="fullname-form" class="form-label">Họ tên</label>
                                            <input type="text" id="fullname-form" class="form-control" value="{{$ct->name}}" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="email-form" class="form-label">Địa chỉ email</label>
                                            <input type="text" id="email-form" class="form-control" value="{{$ct->email}}" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="phone-form" class="form-label">Số điện thoại</label>
                                            <input type="text" id="phone-form" class="form-control" value="{{$ct->phone}}" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="title-form" class="form-label">Tiêu đề</label>
                                            <input type="text" id="title-form" class="form-control" value="{{$ct->title}}" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label for="content-form" class="form-label">Nội dung</label>
                                            <textarea id="content-form" class="form-control" cols="30" rows="5" readonly>{{$ct->content}}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                    </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Modal End -->
                            <td>
                                <div class="d-flex">
                                    <form action="{{route('contact.no_process',$ct->id)}}"  method="post">
                                        @method('PUT') @csrf
                                        <button class="btn btn-danger  btn-sm mx-1" @if($ct->status ==0) disabled @endif href="" title="xử lý"><i class="fas fa-times"></i></i></button>
                                    </form>
                                    <form action="{{route('contact.processing',$ct->id)}}" method="post">
                                        @method('PUT') @csrf
                                        <button class="btn btn-warning  btn-sm mx-1"@if($ct->status ==1) disabled @endif href="" title="Đang xử lý"><i class="fab fa-telegram-plane"></i></button>
                                    </form>
                                    <form action="{{route('contact.handle',$ct->id)}}" method="post">
                                        @method('PUT') @csrf
                                        <button class="btn btn-success  btn-sm mx-1"@if($ct->status ==2) disabled @endif href="" title="xử lý"><i class="fas fa-check"></i></button>
                                    </form>
                                    <form action="{{route('contact-form.destroy',$ct->id)}}" method="post">
                                        @method('delete') @csrf
                                        <button class="btn btn-danger  btn-sm mx-1" href="" title="xóa mềm" onclick="return confirm('Bạn có muốn đưa liên hệ này vào sọt rác ?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                @endif
            </table>
        </div>
        {{ $contact->onEachSide(1)->links('backend.layouts.partials.pagination') }}
    </div>
</div>
<!--/ Bordered Table -->
@endsection