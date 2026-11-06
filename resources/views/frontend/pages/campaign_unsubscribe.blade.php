@extends('frontend.index')

@section('title')
    Email ưu đãi
@endsection

{{-- No promotion banner on a page someone reached by asking for fewer emails. --}}
@section('banner')
@endsection

@section('content')
    <section class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 text-center py-5">
                @if ($done)
                    <h4 class="mb-3">Bạn đã huỷ nhận email ưu đãi</h4>
                    <p class="text-muted mb-4">
                        Sneaker Square sẽ không gửi email ưu đãi cho tài khoản của bạn nữa. Email xác nhận đơn hàng và
                        thông báo giao hàng vẫn được gửi như bình thường.
                    </p>
                    <a href="{{ route('home.page') }}" class="btn btn-checkout px-4 text-white border-0">Về trang chủ</a>
                @else
                    <h4 class="mb-3">Không nhận email ưu đãi nữa?</h4>
                    <p class="text-muted mb-4">Bấm nút dưới đây để dừng hẳn các email ưu đãi cho tài khoản của bạn.</p>
                    <form method="POST" action="{{ route('campaign.unsubscribe.confirm', $token) }}">
                        @csrf
                        <button type="submit" class="btn btn-checkout px-4 text-white border-0">Huỷ nhận email ưu đãi</button>
                    </form>
                @endif
            </div>
        </div>
    </section>
@endsection
