@extends('frontend.index')

@section('title')
    Giỏ hàng
@endsection

@section('banner')
    <!-- Start banner Area -->
 
    <!-- End banner Area -->
@endsection

<!-- Content -->
@section('content')
    <section class="cart-order">
        <!-- Start Product Cart -->
        <div class="container-fluid px-5 my-5">
            <div class="col-lg-12 box__shadow-round rounded">
                <div class="container-xxl">
                    <div class="row">
                        <div class="col-lg-8 p-3">
                            <div class="container-fluid px-0">
                                <div class="d-flex justify-content-between p-3">
                                    <h4>Giỏ hàng</h4>
                                </div>
                                <div class="card border-0">
                                    <div class="d-flex justify-content-center mt-5">
                                        <div>
                    
                                            <img class="d-flex m-auto img-emptycart" src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/orderlist/5fafbb923393b712b96488590b8f781f.png" alt="">
                                            <p class="mt-3">
                                                Không có bất kì sản phẩm nào trong giỏ !
                                            </p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 mt-3">
                                            <div class="container-fluid px-0">
                                                <div class="row">
                                                    <div class="col-lg-6 mb-3">
                                                        <a href="{{ route('product.page') }}" class="btn-del__cart btn-add__pro">
                                                            <span class="button__text">Mua thêm hàng</span>
                                                            <span class="button__icon">
                                                                <i class='bx bx-shopping-bag' ></i>
                                                            </span>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 p-3">
                            <h4 class="text-center mb-3 p-3">Thanh toán</h4>
                            <form action="#">
                                <div class="mb-3">
                                    <label for="coupon" class="form-label">Mã giảm giá</label>
                                    <input type="text" class="form-control" id="coupon" placeholder="Nhập tại đây">
                                </div>
                                <button disabled class="apply-discount-button rounded btn-order">Kiểm tra</button>
                            </form>
                            <hr class="mt-4">
                            <div class="d-flex justify-content-between mb-3">
                                <h5 class="fw-bold">TỔNG TIỀN</h5>
                                <h5>0 VNĐ</h5>
                            </div>
                            <button disabled class="btn-checkout disabled rounded btn-order form-control text-white border-0 mt-2">Thanh toán</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Product Cart -->
    </section>
@endsection