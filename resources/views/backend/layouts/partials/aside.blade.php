<!-- Menu -->
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo d-flex justify-content-center" id="app-brand-admin">
        <a href="{{route('admin.dashboard')}}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img class="logo-aside-admin" src="{{asset('backend/assets/img/logo_sn.png')}}" alt="">
            </span>
            {{-- <span class="app-brand-text demo menu-text fw-bolder ms-2">Sneaker Square</span> --}}
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item {{(url()->current()) == route('admin.dashboard') ? 'active' : ''}}">
            <a href="{{route('admin.dashboard')}}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>
        
        <!-- Product -->
        @php
            $canAccessProduct = auth()->user()->can('Quản trị Sản phẩm') ||
                            auth()->user()->can('Quản trị Sản phẩm (Kho)') ||
                            auth()->user()->can('Quản trị Sản phẩm (Thống kê)') ||
                            auth()->user()->can('Quản trị Sản phẩm (Bình luận)');
        @endphp
        
        @if($canAccessProduct)
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Sản phẩm</span>
        </li>
        
        <li class="menu-item {{( (url()->current() == route('product.index')) || 
        (url()->current() == route('product.create')) || (url()->current() == route('comment.index')) || 
        (url()->current() == route('stock.create')) || (url()->current() == route('product.statistical')) 
        || (url()->current() == route('product.trashed')) || (url()->current() == route('comment.trashed')) ) ? 'active open' : ''}}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-dock-top"></i>
                <div data-i18n="Account Settings">Sản phẩm</div>
            </a>
        @endif
            <ul class="menu-sub">
                @can('Quản trị Sản phẩm (Thống kê)')
                <li class="menu-item {{(url()->current()) == route('product.statistical') ? 'active' : ''}}">
                    <a href="{{route('product.statistical')}}" class="menu-link">
                        <div data-i18n="Account">Thống kê</div>
                    </a>
                </li>
                @endcan
                @can('Quản trị Sản phẩm')
                <li class="menu-item {{(url()->current()) == route('product.index') ? 'active' : ''}}">
                    <a href="{{route('product.index')}}" class="menu-link">
                        <div data-i18n="Account">Danh sách</div>
                    </a>
                </li>
                <li class="menu-item {{(url()->current()) == route('product.create') ? 'active' : ''}}">
                    <a href="{{route('product.create')}}" class="menu-link">
                        <div data-i18n="Notifications">Thêm mới</div>
                    </a>
                </li>
                @endcan
                @can('Quản trị Sản phẩm (Kho)')
                <li class="menu-item {{(url()->current()) == route('stock.create') ? 'active' : ''}}">
                    <a href="{{route('stock.create')}}" class="menu-link">
                        <div data-i18n="Account">Kho</div>
                    </a>
                </li>
                @endcan
                @can('Quản trị Sản phẩm (Bình luận)')
                <li class="menu-item {{ ((url()->current() == route('comment.index')) || (url()->current() == route('comment.trashed'))) ? 'active' : ''}}">
                    <a href="{{route('comment.index')}}" class="menu-link">
                        <div data-i18n="Account">Bình luận</div>
                    </a>
                </li>
                @endcan
            </ul>
        </li>

        <!-- Product Category -->
        @can('Quản trị Sản phẩm')
        <li class="menu-item {{( (url()->current() == route('product-category.index')) || (url()->current() == route('product-category.create')) || (url()->current() == route('product-category.trashed'))) ? 'active open' : ''}}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-category"></i>
                <div data-i18n="Authentications">Danh mục</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{(url()->current()) == route('product-category.index') ? 'active' : ''}}">
                    <a href="{{route('product-category.index')}}" class="menu-link">
                        <div data-i18n="Basic">Danh sách</div>
                    </a>
                </li>
                <li class="menu-item {{(url()->current()) == route('product-category.create') ? 'active' : ''}}">
                    <a href="{{route('product-category.create')}}" class="menu-link">
                        <div data-i18n="Notifications">Thêm mới</div>
                    </a>
                </li>
            </ul>
        </li>
        @endcan
        <!-- Blog -->
        
        @can('Quản trị Bài viết')
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Blog</span></li>
        <li class="menu-item {{( (url()->current() == route('blog.index')) || (url()->current() == route('blog.create')) ||  (url()->current() == route('blog.trashed'))) ? 'active open' : ''}}">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-book-content"></i> 
                <div data-i18n="User interface">Bài viết</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{(url()->current() == route('blog.index')) ? 'active' : ''}}">
                    <a href="{{route('blog.index')}}" class="menu-link">
                        <div data-i18n="Accordion">Danh sách</div>
                    </a>
                </li>
                <li class="menu-item {{(url()->current() == route('blog.create')) ? 'active' : ''}}">
                    <a href="{{route('blog.create')}}" class="menu-link">
                        <div data-i18n="Notifications">Thêm mới</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- Blog Category -->
        <li class="menu-item {{( (url()->current() == route('blog-category.index')) || (url()->current() == route('blog-category.create')) 
        || (url()->current() == route('cate_blog.trashed')) ) ? 'active open' : ''}}">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-category"></i>
                <div data-i18n="Extended UI">Danh mục</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{(url()->current() == route('blog-category.index')) ? 'active' : ''}}">
                    <a href="{{route('blog-category.index')}}" class="menu-link">
                        <div data-i18n="Accordion">Danh sách</div>
                    </a>
                </li>
                <li class="menu-item {{(url()->current() == route('blog-category.create')) ? 'active' : ''}}">
                    <a href="{{route('blog-category.create')}}" class="menu-link">
                        <div data-i18n="Notifications">Thêm mới</div>
                    </a>
                </li>
            </ul>
        </li>
        <!-- Tags Blog -->
        <li class="menu-item {{( (url()->current() == route('tags.index')) || (url()->current() == route('tags.create')) 
        || (url()->current() == route('tag.trashed'))) ? 'active open' : ''}}">
            <a href="javascript:void(0)" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-bookmark"></i> 
                <div data-i18n="Extended UI">Tag</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{(url()->current() == route('tags.index')) ? 'active' : ''}}">
                    <a href="{{route('tags.index')}}" class="menu-link">
                        <div data-i18n="Accordion">Danh sách</div>
                    </a>
                </li>
                <li class="menu-item {{(url()->current() == route('tags.create')) ? 'active' : ''}}">
                    <a href="{{route('tags.create')}}" class="menu-link">
                        <div data-i18n="Notifications">Thêm mới</div>
                    </a>
                </li>
            </ul>
        </li>
        @endcan
        @php
            $canAccessManage = auth()->user()->can('Quản trị Tài khoản (Khách hàng)') ||
                            auth()->user()->can('Quản trị Tài khoản (Quản trị viên)') ||
                            auth()->user()->can('Quản trị Mã giảm giá') ||
                            auth()->user()->can('Quản trị Đơn hàng') ||
                            auth()->user()->can('Thanh toán');
        @endphp
        @if($canAccessManage)
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Quản trị</span>
        </li>
        @endif
        <li class="menu-item {{( (url()->current() == route('account.user')) || 
            (url()->current() == route('account.index')) || (url()->current() == route('account.create'))) ? 'active open' : ''}}">
            @php
                $canAccessAcc = auth()->user()->can('Quản trị Tài khoản (Khách hàng)') ||
                                auth()->user()->can('Quản trị Tài khoản (Quản trị viên)');
            @endphp
            @if($canAccessAcc)
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class='bx bx-user menu-icon' ></i>
                <div data-i18n="Form Elements">Tài khoản</div>
            </a>
            @endif
            @can('Quản trị Tài khoản (Khách hàng)')
            <ul class="menu-sub">
                <li class="menu-item {{(url()->current() == route('account.user')) ? 'active' : ''}}">
                    <a href="{{route('account.user')}}" class="menu-link">
                        <div data-i18n="Basic Inputs">Khách hàng</div>
                    </a>
                </li>
            </ul>
            @endcan
            @can('Quản trị Tài khoản (Quản trị viên)')
            <ul class="menu-sub">
                <li class="menu-item {{((url()->current() == route('account.index')) || (url()->current() == route('account.create'))) ? 'active' : ''}}">
                    <a href="{{route('account.index')}}" class="menu-link">
                        <div data-i18n="Basic Inputs">Quản trị viên</div>
                    </a>
                </li>
            </ul>
            @endcan
        </li>

        <!-- Discount Code -->
        @can('Quản trị Mã giảm giá')
        <li class="menu-item {{(url()->current() == route('coupon.index') || (url()->current() == route('coupon.trashed')) || (url()->current() == route('coupon.create'))) ? 'active' : ''}}">
            <a href="{{route('coupon.index')}}" class="menu-link">
                <i class='bx bxs-coupon menu-icon' ></i>
                <div data-i18n="Form Layouts">Mã giảm giá</div>
            </a>
           
        </li>
        @endcan
        @can('Quản trị Đơn hàng')
        <li class="menu-item {{(url()->current() == route('order.index')) ? 'active' : ''}}">
            <a href="{{route('order.index')}}" class="menu-link">
                <i class='bx bx-printer menu-icon'></i>
                <div data-i18n="Form Layouts">Đơn hàng</div>
            </a>
        </li>
        @endcan
        @can('Thanh toán')
        <li class="menu-item">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class='bx bx-credit-card-alt menu-icon'></i>
                <div data-i18n="Form Elements">Thanh toán</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item">
                    <a href="https://sandbox.vnpayment.vn/merchantv2/" class="menu-link" target="_blank">
                        <div data-i18n="Basic Inputs">VNPAY</div>
                    </a>
                </li>
            </ul>
        </li>
        @endcan
        @can('Khách hàng liên hệ')
        <li class="menu-item {{(url()->current() == route('contact-form.index') || (url()->current() == route('contact-form-trashed'))) ? 'active' : ''}}">
            <a href="{{route('contact-form.index')}}" class="menu-link">
                <i class='bx bxs-envelope menu-icon'></i>
                <div data-i18n="Form Layouts">Liên hệ</div>
            </a>
        </li>
        @endcan
        @php
            $canAccessManage = auth()->user()->can('Thống kê truy cập') ||
                            auth()->user()->can('Thống kê doanh thu') 
        @endphp
        @if($canAccessManage)
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Thống kê</span>
        </li>
        @endif
        @can('Thống kê truy cập')
        <li class="menu-item {{(url()->current() == route('admin.statistical')) ? 'active open' : ''}}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class='bx bx-user menu-icon' ></i>
                <div data-i18n="Form Elements">Truy cập</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{(url()->current() == route('admin.statistical')) ? 'active' : ''}}">
                    <a href="{{route('admin.statistical')}}" class="menu-link">
                        <div data-i18n="Basic Inputs">Chi tiết</div>
                    </a>
                </li>
            </ul>
        </li>
        @endcan
        @can('Thống kê doanh thu')
        <li class="menu-item {{(url()->current() == route('admin.revenue')) ? 'active' : ''}}">
            <a href="{{route('admin.revenue')}}" class="menu-link">
                <i class='bx bxs-bar-chart-alt-2 menu-icon'></i>
                <div data-i18n="User interface">Doanh thu</div>
            </a>
        </li>
        @endcan
       
        @php
            $canAccessSet = auth()->user()->can('Quản trị Menu') ||
                            auth()->user()->can('Quản trị Slide') ||
                            auth()->user()->can('Quản trị Thông tin') ||
                            auth()->user()->can('Giới thiệu') ||
                            auth()->user()->can('Quản trị FAQ');
        @endphp
        @if($canAccessSet)
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Cấu hình chung</span></li>
        @endif
        @can('Quản trị Menu')
        <li class="menu-item {{((url()->current() == route('menus.index')) || (url()->current() == route('menu.trashed'))) ? 'active' : ''}}">
            <a href="{{route('menus.index')}}" class="menu-link">
                <i class='menu-icon bx bx-food-menu bx-detail'></i>
                <div data-i18n="User interface">Menu</div>
            </a>
        </li>
        @endcan
        @can('Quản trị Slide')
        <li class="menu-item {{( (url()->current() == route('cate-slide.index')) || (url()->current() == route('promotion.index'))  || (url()->current() == route('cate-slide.create')) 
            || (url()->current() == route('cate_slide.trashed')) || (url()->current() == route('promotion.trashed')) || (url()->current() == route('promotion.create')) ) ? 'active open' : ''}}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class='bx bx-slideshow menu-icon'></i>
                <div data-i18n="Form Elements">Slide</div>
            </a>
            <ul class="menu-sub">
                <li class="menu-item {{(url()->current() == route('cate-slide.index') || (url()->current() == route('cate_slide.trashed'))  || (url()->current() == route('cate-slide.create')) ) ? 'active' : ''}}">
                    <a href="{{route('cate-slide.index')}}" class="menu-link">
                        <div data-i18n="Basic Inputs">Danh mục</div>
                    </a>
                </li>
            </ul>
            <ul class="menu-sub">
                <li class="menu-item {{(url()->current() == route('promotion.index') || (url()->current() == route('promotion.trashed')) || (url()->current() == route('promotion.create'))) ? 'active' : ''}}">
                    <a href="{{route('promotion.index')}}" class="menu-link">
                        <div data-i18n="Basic Inputs">Hình ảnh</div>
                    </a>
                </li>
            </ul>
        </li>
        @endcan
        @can('Quản trị Thông tin')
        <li class="menu-item {{(url()->current() == route('info-contact.index') 
            || url()->current() == route('info_contact.trashed') || url()->current() == route('info-contact.create')) ? 'active' : ''}}">
            <a href="{{route('info-contact.index')}}" class="menu-link">
                <i class='menu-icon bx bxs-contact'></i>
                <div data-i18n="Extended UI">Thông tin</div>
            </a>
        </li>
        @endcan
        @can('Quản trị FAQ')
        <li class="menu-item {{(url()->current() == route('faq.index') || url()->current() == route('faq.create') || url()->current() == route('faq.trashed')) ? 'active' : ''}}">
            <a href="{{route('faq.index')}}" class="menu-link">
                <i class='bx bx-question-mark menu-icon'></i>
                <div data-i18n="Extended UI">Chính sách</div>
            </a>
        </li>
        @endcan
        @can('Giới thiệu')
        <li class="menu-item {{(url()->current() == route('about.index')) ? 'active' : ''}}">
            <a href="{{route('about.index')}}" class="menu-link">
                <i class='bx bx-book-alt menu-icon'></i>
                <div data-i18n="Extended UI">Giới thiệu</div>
            </a>
        </li>
        @endcan
        <!-- Misc -->
        <li class="menu-header small text-uppercase"><span class="menu-header-text">Hỗ trợ</span></li>
        <li class="menu-item {{(url()->current() == route('support')) ? 'active' : ''}}">
            <a href="{{route('support')}}"
                class="menu-link">
                <i class="menu-icon tf-icons bx bx-support"></i>
                <div data-i18n="Support">Hỗ trợ kỹ thuật</div>
            </a>
        </li>
        <li class="menu-item">
            <a href="/"
                target="_blank" class="menu-link">
                <i class='menu-icon tf-icons bx bx-globe'></i>
                <div data-i18n="Documentation">Sneaker Square</div>
            </a>
        </li>
        <div class="menu-item p-3">
            <a onclick="return confirm('Bạn có chắc muốn thoát khỏi phiên đăng nhập này?')" href="{{route('admin.logout')}}" class="btn btn-secondary mx-3 d-flex justify-content-center align-items-center m-2">
                <i class="bx bx-power-off me-2"></i>
                Đăng xuất
            </a>
        </div>
    </ul>
</aside>
<!-- / Menu -->
