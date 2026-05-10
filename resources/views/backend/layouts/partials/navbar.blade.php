<!-- Navbar -->
<nav class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="bx bx-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center" id="search">
            <form action="#" method="GET" id="search-form" class="mb-0">
                <div class="nav-item d-flex align-items-center">
                    <button class="border-0 bg-white search" type="submit" onclick="return checkKeyword()"><i class="bx bx-search fs-4 lh-0"></i></button>
                    <input type="text" value="{{ $keyword }}" id="searchInput" name="keyword" class="form-control border-0 shadow-none" placeholder="Tìm kiếm..."
                        aria-label="Search..." />
                </div>
            </form>
        </div>
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <li class="nav-item navbar-dropdown dropdown-user dropdown me-4">
                <a id="viewButton" href="#" class="fs-4 link-bell" data-bs-toggle="dropdown">
                    <i class="fas fa-bell"></i>
                    <span id="notification-count" class="top-2 text-white fs-6 d-flex justify-content-center align-items-center notifi">0</span>
                </a>
                <div class="dropdown-menu drop dropdown-menu-end mb-0 pb-0">
                    <h5 class="p-3 pb-0 mb-0">Thông báo</h5>
                    <div class="row pb-2 align-items-center pt-2">
                        <span class="text-center" id="noti-alert"></span>
                    </div>
                @can('Quản trị Đơn hàng')
                    <a href="{{route('order.index')}}" class="tab-sort neworder">
                        <div class="row p-3 align-items-center pt-0">
                            <div class="col-2 col-sm-2 pt-1">
                                <div class="avatar flex-shrink-0 me-3">
                                    <img src="/backend/assets/img/icons/unicons/noti-order.png" alt="User" class="rounded h-100 w-100" />
                                </div>
                            </div>
                            <div class="col-6 col-sm-6 p-0">
                                <div class="pro-name size-f pt-2">Cửa hàng có <span id="newOrderCount" style="font-weight:bold"></span> đơn hàng mới!</div>
                            </div>
                            <div class="col-4 col-md-4">
                                <div class="pro-name size-t pt-2" id="timeAgo">Bây giờ</div>
                            </div>
                        </div>
                    </a>
                    <a href="{{route('order.index')}}" class="tab-sort success">
                        <div class="row p-3 align-items-center pt-0">
                            <div class="col-2 col-sm-2 pt-1">
                                <div class="avatar flex-shrink-0 me-3">
                                    <img src="/backend/assets/img/icons/unicons/order9.png" alt="User" class="rounded h-100 w-100" />
                                </div>
                            </div>
                            <div class="col-6 col-sm-6 p-0">
                                <div class="pro-name size-f pt-2">Hôm nay, có <span id="sucessOrderCount" style="font-weight:bold"></span> đơn hàng giao thành công!</div>
                            </div>
                            <div class="col-4 col-md-4">
                                <div class="pro-name size-t pt-2" id="timeSuccessAgo">Bây giờ</div>
                            </div>
                        </div>
                    </a>
                    <a href="{{route('order.index')}}" class="tab-sort return">
                        <div class="row p-3 align-items-center pt-0">
                            <div class="col-2 col-sm-2">
                                <div class="avatar flex-shrink-0 me-3">
                                    <img src="/backend/assets/img/icons/unicons/order4.png" alt="User" class="h-100 w-100"/>
                                </div>
                            </div>
                            <div class="col-6 col-sm-6 p-0">
                                <div class="pro-name size-f pt-2 ">Có <span id="returnOrderCount" style="font-weight:bold"></span> đơn hàng yêu cầu hoàn hàng!</div>
                            </div>
                            <div class="col-4 col-md-4">
                                <div class="pro-name size-t pt-2" id="timeReturnAgo">Bây giờ</div>
                            </div>
                        </div>
                    </a>
                @endcan
                @can('Quản trị Mã giảm giá')
                    <a href="{{route('coupon.index')}}" class="tab-sort coupon">
                        <div class="row p-3 align-items-center pt-0">
                            <div class="col-2 col-sm-2">
                                <div class="avatar flex-shrink-0 me-3">
                                    <img src="/backend/assets/img/icons/unicons/order10.png" alt="User" class="rounded h-100 w-100" />
                                </div>
                            </div>
                            <div class="col-6 col-sm-6 p-0">
                                <div class="pro-name size-f pt-2 ">Mã giảm <span id="couponCount" style="font-weight:bold"></span> đã hết hạn vào hôm nay!</div>
                            </div>
                            <div class="col-4 col-md-4">
                                <div class="pro-name size-t pt-2" id="timeCouponAgo">Bây giờ</div>
                            </div>
                        </div>
                    </a>
                @endcan
                @can('Khách hàng liên hệ')
                    <a href="{{route('contact-form.index')}}" class="tab-sort contact">
                        <div class="row p-3 align-items-center pt-0">
                            <div class="col-2 col-sm-2">
                                <div class="avatar flex-shrink-0 me-3">
                                    <img src="/backend/assets/img/icons/unicons/order7.png" alt="User" class="rounded h-100 w-100" />
                                </div>
                            </div>
                            <div class="col-6 col-sm-6 p-0">
                                <div class="pro-name size-f pt-2">Có <span id="contactCount" style="font-weight:bold"></span> khách hàng liên hệ với của hàng!</div>
                            </div>
                            <div class="col-4 col-md-4">
                                <div class="pro-name size-t pt-2" id="timeContactAgo">Bây giờ</div>
                            </div>
                        </div>
                    </a>
                @endcan
                @can('Quản trị Slide')
                    <a href="{{route('promotion.index')}}" class="tab-sort promotion">
                        <div class="row p-3 align-items-center pt-0">
                            <div class="col-2 col-sm-2">
                                <div class="avatar flex-shrink-0 me-3">
                                    <img src="/backend/assets/img/icons/unicons/order3.png" alt="User" class="h-100 w-100"/>
                                </div>
                            </div>
                            <div class="col-6 col-sm-6 p-0">
                                <div class="pro-name size-f pt-2">Quảng cáo <span class="slides-custom-class" id="slideCount" style="font-weight:bold;"></span> đã hết hạn!</div>
                            </div>
                            <div class="col-4 col-md-4">
                                <div class="pro-name size-t pt-2" id="timeSlideAgo">Bây giờ</div>
                            </div>
                        </div>
                    </a>
                @endcan
                </div>
            </li>
            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img onerror="this.src='/uploads/img_error3.png'" src="{{asset(Auth::user()->user_img)}}" class="w-100 h-100 rounded-circle border" alt="{{Auth::user()->name}}"  width="50px">
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{route('account.info',['encryptedUserId' => encrypt(Auth::user()->user_id)])}}">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img onerror="this.src='/uploads/img_error3.png'" src="{{asset(Auth::user()->user_img)}}" class="w-100 h-100 rounded-circle border" alt="{{Auth::user()->name}}"  width="50px">
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block">{{Auth::user()->name}}</span>
                                    <small class="text-muted">
                                        {{((Auth::user()->user_role) == 1) ? 'Quản trị' : 'Nhân viên'}}
                                    </small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{route('account.info',['encryptedUserId' => encrypt(Auth::user()->user_id)])}}">
                            <i class="bx bx-user me-2"></i>
                            <span class="align-middle">Thông tin</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{route('info-contact.index')}}">
                            <i class="bx bx-cog me-2"></i>
                            <span class="align-middle">Cài đặt</span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <a onclick="return confirm('Bạn có chắc muốn thoát khỏi phiên đăng nhập này?')" href="{{route('admin.logout')}}" class="btn btn-secondary d-flex justify-content-center align-items-center m-2">
                        <i class="bx bx-power-off me-2"></i>
                        Đăng xuất
                    </a>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
<!-- / Navbar -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    function checkKeyword() {
        const keyword = document.getElementById('searchInput').value.trim();
        if (keyword === '') {
            // alert('Vui lòng nhập từ khoá tìm kiếm!');
            return false; 
        }
        return true;
    }
    window.addEventListener('load', function() {
        checkURL();
    });

    window.addEventListener('popstate', function() {
        checkURL();
    });

    function checkURL() {
        let currentURL = window.location.href;
        let keyword = getParameterByName('keyword');

        if (currentURL.includes('keyword=') && (!keyword || keyword.trim() === '')) {
            window.location.href = '/admin/dashboard'; // Chuyển hướng về trang chủ
        }
    }

    function getParameterByName(name) {
        name = name.replace(/[\[\]]/g, '\\$&');
        let regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
        let results = regex.exec(window.location.href);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    }

    $(document).ready(function() {
        setInterval(()=>{
            $.ajax({
                url: '/admin/check-new-orders', 
                method: 'GET',
                success: function(response) {
                    $('#newOrderCount').text(response.newOrderCount);
                    $('#returnOrderCount').text(response.returnOrderCount);
                    $('#sucessOrderCount').text(response.sucessOrderCount);
                    $('#couponCount').text(response.couponCount);
                    $('#contactCount').text(response.contactCount);
                    $('#slideCount').text(response.slideCount);
                    
                    const elementsToUpdate = {
                        timestampOrder: 'timeAgo',
                        timestampReturnOrder: 'timeReturnAgo',
                        timestampSuccessOrder: 'timeSuccessAgo',
                        timestampContact: 'timeContactAgo',
                        timestampSlide: 'timeSlideAgo',
                        timestampCoupon: 'timeCouponAgo',
                    };

                    for (const key in elementsToUpdate) {
                        if (Object.hasOwnProperty.call(elementsToUpdate, key)) {
                            const timestamp = response[key];
                            const id = elementsToUpdate[key];
                            updateTimeAgo(timestamp, id);
                        }
                    }

                    let zeroCount = 0;
                    
                    if (response.returnOrderCount === 0) {
                        $('.return').hide();
                    } else {
                        $('.return').show(); 
                        zeroCount++;
                    }

                    if (response.sucessOrderCount === 0) {
                        $('.success').hide();
                    } else {
                        $('.success').show(); 
                        zeroCount++;
                    }

                    if (response.newOrderCount === 0) {
                        $('.neworder').hide();
                    } else {
                        $('.neworder').show(); 
                        zeroCount++;
                    }
                   
                    if (response.couponCount.length === 0) {
                        $('.coupon').hide();
                    } else {
                        $('.coupon').show(); 
                        zeroCount++;
                    }

                    if (response.slideCount.length === 0) {
                        $('.promotion').hide();
                    } else {
                        $('.promotion').show(); 
                        zeroCount++;
                    }
                    
                    if (response.contactCount === 0) {
                        $('.contact').hide();
                    } else {
                        $('.contact').show(); 
                        zeroCount++;
                    }

                    if (zeroCount === 0) {
                        $('#noti-alert').text('Không có thông báo mới');
                    } else {
                        $('#notification-count').text(zeroCount);
                    }
                },
                error: function(err) {
                    console.error('Error while checking new orders:', err);
                }
            });
        }, 1000); 
    })

    function updateTimeAgo(timestamp, id) {
        const element = document.getElementById(id);
        const now = new Date();
        const timestampInMilliseconds = timestamp * 1000;
        const timestampDate = new Date(timestampInMilliseconds);


        const timeDifference = now - timestampDate;
        const minuteDifference = Math.floor(timeDifference / 60000);
        if (element !== null) {
            if (minuteDifference < 1) {
                element.textContent = 'Vừa xong';
            } else if (minuteDifference === 1) {
                element.textContent = '1 phút trước';
            } else if (minuteDifference < 60) {
                element.textContent = `${minuteDifference} phút trước`;
            } else {
                const hourDifference = Math.floor(minuteDifference / 60);
                if (hourDifference === 1) {
                    element.textContent = '1 giờ trước';
                } else if (hourDifference < 24) {
                    element.textContent = `${hourDifference} giờ trước`;
                } else {
                    const dayDifference = Math.floor(hourDifference / 24);
                    if (dayDifference === 1) {
                        element.textContent = '1 ngày trước';
                    } else {
                        element.textContent = `${dayDifference} ngày trước`;
                    }
                }
            }
        } else {
        // console.log('Element not found.');
        }
    }
</script>