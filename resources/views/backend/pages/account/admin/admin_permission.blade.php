@extends('backend.index')

@section('title')
    Tài khoản quản trị
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('account.index')}}" class="tab-back">Danh sách / </a></span> Phân quyền</h4>
    <!-- Bordered Table -->
    <form action="{{url('admin/insert_permission',[$user->user_id])}}" method="POST">
        @csrf
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Cấp quyền cho User: <b>{{$user->name}}</b></h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <a href="{{route('account.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
                        <button type="submit" class="btn btn-success px-5 mt-1 text-white">Hoàn tất</button>
                    </div>
                </div>
                @if(isset($name_roles))
                <p class="ml-2">Vai trò hiện tại: <b>{{$name_roles}}</b></p>
                @endif
                <br>
                <p class="p-0 ml-2">Các quyền trong website:</p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="select-all-checkbox">
                            <label class="form-check-label" for="select-all-checkbox">Chọn tất cả</label>
                        </div>
                        @foreach($permission->take($permission->count() / 2) as $per)
                            <div class="form-check form-check-inline ml-2">
                                <input class="form-check-input" type="checkbox"
                                    @foreach($get_permission_via_role as $get)
                                        @if($get->id == $per->id)
                                            checked
                                        @endif
                                    @endforeach
                                name="permission[]" id="{{$per->id}}" value="{{$per->id}}">
                                <label class="form-check-label" for="{{$per->id}}">{{$per->name}}</label>
                            </div><br>
                        @endforeach
                    </div>
                    <div class="col-md-6">
                        @foreach($permission->slice($permission->count() / 2) as $per)
                            <div class="form-check form-check-inline ml-2">
                                <input class="form-check-input" type="checkbox"
                                    @foreach($get_permission_via_role as $get)
                                        @if($get->id == $per->id)
                                            checked
                                        @endif
                                    @endforeach
                                name="permission[]" id="{{$per->id}}" value="{{$per->id}}">
                                <label class="form-check-label" for="{{$per->id}}">{{$per->name}}</label>
                            </div><br>
                        @endforeach
                    </div>
                </div>                
            </div>
        </div>
    </form>
@endsection

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Function to handle "Select All" checkbox change
        document.getElementById('select-all-checkbox').addEventListener('change', function () {
            let checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach((checkbox) => {
                checkbox.checked = this.checked;
            });
        });
    });
</script>