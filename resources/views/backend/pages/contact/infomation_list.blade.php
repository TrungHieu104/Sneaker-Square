@extends('backend.index')

@section('title')
    Liên hệ
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light">Cấu hình chung /</span> <a href="{{route('info-contact.index')}}" class="tab-sort">Thông tin</a></h4>
    <!-- Bordered Table -->
    <div class="card mb-4 card-border-top">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-header">Quản lý thông tin</h5>
            <div class="px-4 d-flex gap-3">
                <a href="{{route('info-contact.create')}}" class="btn btn-success mt-1">Thêm mới &nbsp;<i class="bi bi-plus-circle"></i></a>
                <a href="{{route('info_contact.trashed')}}" class="btn btn-danger mt-1">Thùng rác &nbsp;<i class='bx bxs-trash'></i></a>            
            </div>
        </div> 
        <div class="card-body">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th class="text-center">
                                STT
                            </th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=contact_address&sort-type={{ $orderBy === 'contact_address' ? $orderType : 'desc' }}">
                                    Thông tin
                                </a>
                            </th>
                            <th class="text-center">
                                <a class="tab-sort" href="?sort-by=contact_hidden&sort-type={{ $orderBy === 'contact_hidden' ? $orderType : 'desc' }}">
                                    Hiển thị
                                </a>
                            </th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $stt = $contact->firstItem();
                        @endphp
                        @if($contact->isEmpty())
                            <tr>
                                <td colspan="7" class="text-center">
                                    Không có dữ liệu để hiển thị.
                                </td>
                            </tr>
                        @else
                            @foreach($contact as $data)
                                <tr>
                                    <td class="text-center">
                                        {{$stt}}
                                    </td>
                                    <td>
                                        <span class="pro-name">- Địa chỉ: <b>{{$data->contact_address}}</b></span><br>            
                                        - Số điện thoại: <b>{{ ''.substr($data->contact_phone, 0, 4).' '.substr($data->contact_phone, 4, 3).' '.substr($data->contact_phone, 7) }} </b><br>
                                        - Email: <b>{{$data->contact_email}}</b><br>
                                        - Hiển thị: <span id="status_{{ $data->id }}" style="color: {{ $data->contact_hidden == 0 ? 'red' : 'green' }}">
                                            {{ $data->contact_hidden == 0 ? 'Ẩn' : 'Hiện' }}
                                        </span> <br>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $disabled = $count === 1 ? 'disabled' : '';
                                        @endphp
                                        @if($data->contact_hidden == 1)
                                            <input disabled name="info-status" class="form-check-input" type="checkbox" value="1" id="checkbox_{{ $data->contact_id }}" {{ $data->contact_hidden == 1 ? 'checked' : '' }} data-id="{{ $data->contact_id }}" />
                                        @else
                                            <input {{$disabled}} name="info-status" class="form-check-input" type="checkbox" value="1" id="checkbox_{{ $data->contact_id }}" {{ $data->contact_hidden == 1 ? 'checked' : '' }} data-id="{{ $data->contact_id }}" />
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            <button type="button" class="p-2 btn btn-success btn-sm m-1" title="Xem" data-bs-toggle="modal" data-bs-target="#contactModal{{$data->contact_id}}">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a class="p-2 btn btn-warning btn-sm m-1" href="{{ route('info-contact.edit', ['info_contact' => $data->contact_id]) }}" title="Chỉnh sửa"><i class="fas fa-edit"></i></a>
                                            <form class="d-inline" action="{{ route('info_contact.softDelete', ['id' => $data->contact_id]) }}" method="get">
                                                @csrf  
                                                @method('DELETE')
                                                @if($data->contact_hidden==0)
                                                    <button type="submit" onclick="return confirm('Bạn có chắc muốn xóa?')" class="btn btn-danger btn-sm m-1 p-2">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @else
                                                    <button type="button" disabled class="btn btn-danger btn-sm m-1 p-2" title="Không thể xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </form>
                                            
                                        </div>
                                    </td>
                                </tr>
                           
                            <div class="modal fade" id="contactModal{{$data->contact_id}}" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header text-center align-middle">  
                                            <div class="ml-5">
                                                <h5>#{{$stt}}</h5>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body view_faq_data">
                                            <h3 class="text-center">Thông tin liên hệ</h3>
                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="email-info" class="form-label">Email</label>
                                                        <input type="text" id="email-info" class="form-control" value="{{$data->contact_email}}" readonly>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label for="address-info" class="form-label">Địa chỉ</label>
                                                        <input type="text" id="address-info" class="form-control" value="{{$data->contact_address}}" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-6 mb-3">
                                                    <label for="phone-info" class="form-label">Số điện thoại</label>
                                                    <input type="text" id="phone-info" class="form-control" value="{{ ''.substr($data->contact_phone, 0, 4).' '.substr($data->contact_phone, 4, 3).' '.substr($data->contact_phone, 7) }}" readonly>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <label for="status-info" class="form-label">Hiển thị</label>
                                                    <input type="text" id="status-info" class="form-control" value="{{ $data->contact_hidden == 0 ? 'Ẩn' : 'Hiện' }}" readonly>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-lg-6 mb-3">
                                                    <label for="author" class="form-label">Tác giả</label>
                                                    <input type="text" id="author" class="form-control" value="{{$data->contact_created_by}}" readonly>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <label for="edit-by" class="form-label">Người chỉnh sửa</label>
                                                    <input type="text" id="edit-by" class="form-control" value="{{$data->contact_updated_by}}" readonly>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="google-map" class="form-label">Google Map</label>
                                                <textarea class="form-control" id="google-map" rows="8" readonly>{!! ($data->map_link) !!}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="fanpage" class="form-label">Fanpage Facebook</label>
                                                <textarea class="form-control" id="fanpage" rows="8" readonly>{!! ($data->fanpage_link) !!}</textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label for="live-chat" class="form-label">Live Chat</label>
                                                <textarea class="form-control" id="live-chat" rows="8" readonly>{!! ($data->tawk_link) !!}</textarea>
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
                {{$contact->onEachSide(1)->links('backend.layouts.partials.pagination')}}
            </div>
        </div>
    </div>
    <!--/ Bordered Table -->
@endsection
