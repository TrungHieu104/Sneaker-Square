<!-- Get category -->
@php
    use App\Models\CategoryModel as Category;
    use App\Models\CateNewsModel as CateNews;
    $cates = Category::where('cate_parent_id', null)
                -> where('cate_hidden', 1)
                -> orderBy('cate_sort', 'asc')
                -> get();

    $cateNews = CateNews::where('cate_news_hidden',1)
                -> orderBy('cate_news_sort', 'asc')
                -> limit(5)
                -> get();            
@endphp 

<!-- Start Header Area -->
{{-- <div id="header">
    <div class="menu_burger">
        <a href="#menu" id="hambuger" title="Open the menu" menu="menu" fx="collapse" ease="elastic"
            role="button"><span></span></a>
    </div>
    <a href="{{route('home.page')}}" id="logo" class="text-primary">
        <img class="w-100 h-100" src="/frontend/img/logo_sn.png" alt="">
    </a>
    <div class="menu h-100">
        <div class="flex-menu h-100">
            <ul class="h-100">
                @foreach($menu as $data)
                    @if($data->menu_parent_id == 0)
                        <li class="text-menu">
                            <a href="{{$data->menu_link}}" class="menu-item {{( ((url()->current()) == url($data->menu_link)) ) ? 'active' : ''}}">{{$data->menu_name}} </a>
                            @foreach ($menu as $item)
                                @if ($data->menu_id == $item->menu_parent_id)
                                    <ul class="sub-menu">
                                        @foreach($menu as $menu2)
                                            @if($menu2->menu_parent_id == $data->menu_id)
                                                <li class="{{( (url()->current()) == url($menu2->menu_link) ) ? 'active' : ''}}">
                                                    <a href="{{ $menu2->menu_link}}">{{ $menu2->menu_name }}</a>
                                                </li>
                                            @endif
                                        @endforeach 
                                    </ul>
                                @endif
                            @endforeach
                        </li>
                    @endif
                @endforeach
            </ul> 
        </div>
    </div>
    <div class="search-group d-flex">
        <div id="icon__search" class="d-flex align-items-center">
            <i class='bx bx-search' title="Tìm kiếm"></i>
            <div id="form_search">
                <form action="{{route('search.frontend')}}" method="GET" id="search-form-fe">
                    <input type="text" class="input_search" name="keyword" placeholder="Nhập từ khóa...">
                    <button class="sub_search"><i class='bx bx-search'></i></button>
                </form>
            </div>
        </div>
        <div id="icon__heart" class="count-heart">
            <a href="{{route('product.wishlist')}}">
                <i class='bx bx-heart' title="Yêu thích"></i>
            </a>
            <span class="wish-list__count" id="quality"></span>
        </div>
        <div id="icon__shopping">
            <a href="{{route('product.cart')}}">
                <i class='bx bx-shopping-bag' title="Giỏ hàng"></i>
            </a>
            @if (session('cart'))
                
                <span id="quality">{{count((array) session('cart'))}}</span>
            @endif
        </div>
    </div>
</div> --}}
<div class="menu-navbar" id="header">
    <div class="menu-logo">
        <a href="{{route('home.page')}}">
            <img src="/frontend/img/logo_sn.png" alt="Sneaker Square">
        </a>
    </div>
    <div class="menu-data d-flex align-items-center">
        <ul class="menu-parent mb-0 ps-0" id="menu-parent">
            @foreach($menu as $data)
                @if($data->menu_parent_id == 0)
                    <li class="menu-item">
                        @php
                            $submenus = array_filter($menu, function($item) use ($data) {
                                return $item->menu_parent_id == $data->menu_id;
                            });
                        @endphp
                        <a href="{{$data->menu_link}}" class="menu-link {{( ((url()->current()) == url($data->menu_link)) ) ? 'active' : ''}}">{{$data->menu_name}} {!!(count($submenus) > 0 ) ? '<i class="bx bx-chevron-down fs-5"></i>' : ''!!}</a>
                        @if(count($submenus) > 0)
                            <ul class="sub-menu" id="sub-menu">
                                @foreach($submenus as $menu2)
                                    <li class="{{( (url()->current()) == url($menu2->menu_link) ) ? 'active' : ''}}">
                                        <a href="{{ $menu2->menu_link }}">{{ $menu2->menu_name }}</a>
                                    </li>
                                @endforeach 
                            </ul>
                        @endif
                    </li>
                @endif
            @endforeach
        </ul>
        
        <label for="menu-icon" class="menu-icon">
            <input id="menu-icon" type="checkbox" name="hamburger"/>
            <span></span>
            <span></span>
            <span></span>
        </label>
    </div>
    <div class="menu-cart d-flex gap-3">
        <div id="icon__search" class="d-flex align-items-center">
            <i class="bx bx-search fs-4" id="form-search" title="Tìm kiếm"></i>
            <div id="form_search">
                <form action="{{route('search.frontend')}}" method="GET" id="search-form-fe">
                    <input type="text" class="input_search" name="keyword" placeholder="Nhập từ khóa...">
                    <button class="sub_search"><i class='bx bx-search fs-5'></i></button>
                </form>
            </div>
        </div>
        <a href="{{route('product.wishlist')}}">
            <div id="icon__cart" class="d-flex align-items-center position-relative count-heart">
                <i class="bx bx-heart fs-4" title="Yêu thích"></i>
                <span class="position-absolute bottom-50 text-white" id="quality"></span>
            </div>
        </a>
        <a href="{{route('product.cart')}}">
            <div id="icon__cart" class="d-flex align-items-center position-relative">
                <i class="bx bx-shopping-bag fs-4" title="Giỏ hàng"></i>
                @if (session('cart'))
                    <span class="position-absolute bottom-50 text-white">{{count((array) session('cart'))}}</span>
                @endif
            </div>
        </a>
    </div>
</div>
<!-- End Header Area -->