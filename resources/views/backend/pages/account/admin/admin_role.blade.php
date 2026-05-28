@extends('backend.index')

@section('title')
    Tài khoản quản trị
@endsection

@section('content')
    <h4 class="fw-bold py-3 mb-3"><span class="text-muted fw-light"><a href="{{route('account.index')}}" class="tab-back">Danh sách / </a></span> Phân vai trò</h4>
    <!-- Bordered Table -->
    <form action="{{url('admin/insert_roles',[$user->user_id])}}" method="POST">
        <div class="card mb-4 card-border-top">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 mb-3 d-flex align-items-center">
                        <h5 class="fs-4 text-primary my-0">Phân vai trò cho User: <b>{{$user->name}}</b></h5>
                    </div>
                    <div class="col-lg-6 mb-3 d-flex justify-content-end gap-3">
                        <a href="{{route('account.index')}}" class="btn mt-1 btn-warning"><i class='bx bx-arrow-back' ></i></a>
                        <button type="submit" class="btn btn-success px-5 mt-1 text-white">Hoàn tất</button>
                    </div>
                </div> 
                @csrf
                <p class="ml-2">Vai trò:</p>
                @foreach($role as $key => $r)
                    @if(isset($all_column_roles))
                        <div class="form-check check-form-inline m-2">
                            <input type="radio" class="form-check-input"  {{$r->id==$all_column_roles->id ? 'checked' : ''}}  name='role' id="{{$r->id}}" value="{{$r->name}}">
                            <label for="{{$r->id}}" class="form-check-label">{{$r->name}}</label>
                        </div>
                    @else
                        <div class="form-check check-form-inline m-2">
                            <input type="radio" class="form-check-input" name='role' id="{{$r->id}}" value="{{$r->name}}">
                            <label for="{{$r->id}}" class="form-check-label">{{$r->name}}</label>
                        </div>
                    @endif
                @endforeach
                <br>
            
            </div>
        </div>
    </form>
    <!--/ Bordered Table -->
@endsection
