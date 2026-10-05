<?php

use App\Http\Controllers\Backend\AboutAdminController;
use App\Http\Controllers\Backend\AuthAdminController;
use App\Http\Controllers\Backend\BlogAdminController;
use App\Http\Controllers\Backend\BlogCateController;
use App\Http\Controllers\Backend\CateSlideAdminController;
use App\Http\Controllers\Backend\CommentAdminController;
use App\Http\Controllers\Backend\ContactFormController;
use App\Http\Controllers\Backend\CouponAdminController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\FaqAdminController;
use App\Http\Controllers\Backend\GhnSimulatorController;
use App\Http\Controllers\Backend\ImageController;
use App\Http\Controllers\Backend\MenusAdminController;
use App\Http\Controllers\Backend\OrderAdminController;
use App\Http\Controllers\Backend\ProductAdminController;
use App\Http\Controllers\Backend\ProductCateController;
use App\Http\Controllers\Backend\ProductQuantityController;
use App\Http\Controllers\Backend\PromotionAdminController;
use App\Http\Controllers\Backend\ReturnAdminController;
use App\Http\Controllers\Backend\ShopSettingController;
use App\Http\Controllers\Backend\TagsAdminController;
use App\Http\Controllers\Backend\WalletAdminController;
use App\Http\Controllers\Frontend\AuthUserController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\CommentController;
use App\Http\Controllers\Frontend\CustomerOrderController;
use App\Http\Controllers\Frontend\DeliveryInfoController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\OrderPaymentController;
use App\Http\Controllers\Frontend\PaymentCallbackController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Frontend\ShippingController;
use App\Http\Controllers\Frontend\ShippingWebhookController;
use App\Http\Controllers\Frontend\SocialLoginController;
use App\Http\Controllers\Frontend\UserController;
use App\Http\Controllers\Frontend\WalletController;
use App\Http\Controllers\Frontend\WishListController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// ======================================Frontend================================================
Route::fallback(function () {
    abort(404);
});

// Everything in this group is response-cached for 10 minutes at the bottom of
// the file. Pages that show one person's own state — cart, checkout, orders,
// account — and the payment callbacks are marked doNotCacheResponse, because a
// cached copy of those is either somebody else's data or a callback that never
// reaches the controller.
// Where GHN reports a parcel moving. Outside the web group: a carrier
// callback has no session and needs none.
Route::post('/webhook/ghn/{token}', [ShippingWebhookController::class, 'ghn'])->name('webhook.ghn');

Route::group(['middleware' => 'web'], function () {

    Route::get('/', [HomeController::class, 'index'])->name('home.page');
    Route::get('/ve-chung-toi', [HomeController::class, 'about'])->name('about.page');
    Route::get('/lien-he', [HomeController::class, 'contact'])->name('contact.page');
    Route::post('/lien-he-form', [HomeController::class, 'store'])->name('contact-form.sto');

    // Route Product
    Route::get('/san-pham', [ProductController::class, 'index'])->name('product.page');
    Route::get('/danh-muc/{cate_slug}', [ProductController::class, 'index'])->name('product.by.cate');
    Route::get('/san-pham-hot', [ProductController::class, 'index'])->name('product.hot');
    Route::get('/san-pham-sale', [ProductController::class, 'index'])->name('product.sale');
    Route::get('/san-pham/{pro_slug}', [ProductController::class, 'detail'])->name('product.detail');
    Route::post('/binh-luan/{pro_id}', [CommentController::class, 'store'])->name('comments.store');
    Route::get('/tim-kiem', [SearchController::class, 'search'])->name('search.frontend');

    // Route Product WishList
    Route::get('/san-pham-yeu-thich', [WishListController::class, 'index'])->name('product.wishlist')->middleware('doNotCacheResponse');
    Route::post('/them-san-pham-yeu-thich', [WishListController::class, 'store'])->name('product.wishlist_store');
    Route::post('/yeu-thich-total', [WishListController::class, 'count'])->name('product.wishlist_count');
    Route::post('/xoa-yeu-thich', [WishListController::class, 'destroy'])->name('product.wishlist_destroy');

    // Route Cart
    Route::get('/gio-hang', [CartController::class, 'cart'])->name('product.cart')->middleware('doNotCacheResponse');
    Route::post('/them-san-pham/{pro_slug}', [CartController::class, 'addPro'])->name('addProduct.cart');
    Route::post('/ma-giam-gia', [CartController::class, 'checkCoupon'])->name('checkCoupon.cart');
    Route::post('/bo-ma-giam-gia', [CartController::class, 'removeCoupon'])->name('removeCoupon.cart');
    Route::post('/xoa-san-pham/{pro_slug}', [CartController::class, 'delPro'])->name('delProduct.cart');
    Route::get('/xoa-gio-hang', [CartController::class, 'delcart'])->name('del.cart')->middleware('doNotCacheResponse');
    Route::get('/gio-hang-trong', [CartController::class, 'emptyCart'])->name('empty.cart')->middleware('doNotCacheResponse');

    // Route Payment
    Route::get('/thanh-toan', [CheckoutController::class, 'checkout'])->name('product.checkout')->middleware('doNotCacheResponse');
    Route::post('/thanh-toan', [CheckoutController::class, 'checkoutPOST'])->name('product.checkoutPOST');
    // The carrier, proxied. GHN's token stays on the server: it can create
    // real shipments and read every order on the account.
    Route::get('/van-chuyen/tinh-thanh', [ShippingController::class, 'provinces'])->name('shipping.provinces');
    Route::get('/van-chuyen/quan-huyen', [ShippingController::class, 'districts'])->name('shipping.districts');
    Route::get('/van-chuyen/phuong-xa', [ShippingController::class, 'wards'])->name('shipping.wards');
    // The only one that costs a call to GHN per request, so it is kept to
    // signed-in customers; the address lists are public reference data.
    Route::post('/van-chuyen/bao-gia', [ShippingController::class, 'quote'])->name('shipping.quote')->middleware('auth');
    Route::resource('dia-chi', (DeliveryInfoController::class))->middleware('doNotCacheResponse')->names([
        'index' => 'diachi.index',
        'create' => 'diachi.create',
        'store' => 'diachi.store',
        'show' => 'diachi.show',
        'edit' => 'diachi.edit',
        'update' => 'diachi.update',
        'destroy' => 'diachi.destroy',
    ]);
    Route::post('/dia-chi-selected', [DeliveryInfoController::class, 'deliInfoSelected'])->name('deliInfoSelected');
    // Where the payment gateways send the customer back. Every parameter is
    // signature-checked in PaymentCallbackController before anything is acted on.
    Route::get('/kiem-tra-trang-thai-dat-hang', [PaymentCallbackController::class, 'handleReturn'])->name('process.checkout')->middleware('doNotCacheResponse');
    // The gateway's server-to-server notification, which arrives even when the
    // customer closes the tab before being redirected back.
    Route::post('/ipn-thanh-toan', [PaymentCallbackController::class, 'handleIpn'])->name('payment.ipn');
    Route::get('/dat-hang-thanh-cong', [CheckoutController::class, 'successCheckout'])->name('success.checkout')->middleware('doNotCacheResponse');
    Route::get('/dat-hang-that-bai', [CheckoutController::class, 'failedCheckout'])->name('failed.checkout')->middleware('doNotCacheResponse');
    Route::get('/don-hang/{order_code}', [CustomerOrderController::class, 'orderBill'])->name('orderBill.checkout')->middleware('doNotCacheResponse');
    Route::patch('/don-hang/{order_code}', [CustomerOrderController::class, 'cancelOrder'])->name('cancelOrder');
    Route::get('/in-don-hang/{order_code}', [CustomerOrderController::class, 'printBill'])->name('printBill.checkout')->middleware('doNotCacheResponse');

    // Route Blog
    Route::get('/bai-viet', [BlogController::class, 'index'])->name('blog.page');
    Route::get('/tag/{tag_slug}', [BlogController::class, 'tags'])->name('tags.news');
    Route::get('/bai-viet/{cate_news_slug}', [BlogController::class, 'index'])->name('cate.news');
    Route::get('/chi-tiet/{news_slug}', [BlogController::class, 'detail'])->name('news.detail');
    Route::get('/tim-kiem-bai-viet', [BlogController::class, 'search'])->name('news.search');

    // Route Policy
    Route::get('/chinh-sach/tra-hang-hoan-tien', [HomeController::class, 'returnPolicy'])->name('policy.return');
    Route::get('/chinh-sach/{faq_slug}', [HomeController::class, 'faqDetail'])->name('faq.detail');

    // Route Authorization
    Route::get('/dang-nhap', [AuthUserController::class, 'login'])->name('user.login');
    Route::post('/dang-nhap', [AuthUserController::class, 'loginPost'])->name('user.login_post');
    Route::get('/thoat', [AuthUserController::class, 'logout'])->name('user.logout');
    Route::get('/dang-ky', [AuthUserController::class, 'register'])->name('user.register');
    Route::post('/dang-ky', [AuthUserController::class, 'registerPost'])->name('user.register_post');

    // Verification email
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return back();
    })->middleware(['auth', 'signed'])->name('verification.verify');
    Route::post('/email/verification-notification', [UserController::class, 'verifiedEamil'])->middleware(['throttle:6,1'])->name('verification.send');
    Route::post('/remove-token', [UserController::class, 'removeToken']);
    Route::post('/send-mail-pass', [UserController::class, 'sendMailPass'])->name('send_mail_pass');
    Route::get('/verified-email-register/{user}', [AuthUserController::class, 'verifiedRegister'])->name('verifed_register');

    // Info account user
    Route::middleware(['auth', 'doNotCacheResponse'])->group(function () {
        Route::resource('/thong-tin-tai-khoan', (UserController::class));
        Route::get('cap-nhat-mat-khau', [UserController::class, 'updatePass'])->name('user.update_pass');
        Route::get('thay-doi-mat-khau/{token}', [UserController::class, 'changePass'])->name('user.change_pass')->middleware('auth');
        Route::put('cap-nhat-mat-khau', [UserController::class, 'updatePassPost'])->name('user.update_pass_post');
        Route::post('cap-nhat-email/{id}', [UserController::class, 'changeEmail'])->name('user.change_email');
        Route::get('dia-chi-giao-hang', [UserController::class, 'delivery'])->name('user.delivery');
        Route::post('/dia-chi-default', [DeliveryInfoController::class, 'deliInfoDefault'])->name('deliInfoDefault');
        Route::get('thong-tin-don-hang', [UserController::class, 'userOrder'])->name('user.order');
        Route::post('da-nhan-hang', [UserController::class, 'successOrder'])->name('success.order');
        Route::post('/don-hang/{order_code}/thanh-toan', [OrderPaymentController::class, 'pay'])->name('order.pay');
        Route::patch('/don-hang/{order_code}/phuong-thuc-thanh-toan', [OrderPaymentController::class, 'change'])->name('order.change_payment');
        Route::patch('/yeu-cau-tra-hang/{order_code}', [UserController::class, 'returnOrder'])->name('return.order');
        Route::patch('/huy-yeu-cau-tra-hang/{order_code}', [UserController::class, 'cancelReturn'])->name('return.cancel');
        Route::get('vi-cua-toi', [WalletController::class, 'index'])->name('user.wallet');
        Route::post('vi-cua-toi/nap-tien', [WalletController::class, 'topup'])->name('wallet.topup');
        Route::post('vi-cua-toi/rut-tien', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
    });

    // Login Google
    Route::get('auth/google', [SocialLoginController::class, 'redirect'])->defaults('provider', 'google')->name('login_google');
    Route::get('auth/google/callback', [SocialLoginController::class, 'callback'])->defaults('provider', 'google');

    // Login Facebook
    Route::get('auth/facebook', [SocialLoginController::class, 'redirect'])->defaults('provider', 'facebook')->name('login_facebook');
    Route::get('auth/facebook/callback', [SocialLoginController::class, 'callback'])->defaults('provider', 'facebook');

    // Reset password
    Route::get('/quen-mat-khau', [AuthUserController::class, 'forgot'])->name('user.forgot');
    Route::post('/quen-mat-khau', [AuthUserController::class, 'forgotPost'])->name('user.forgot_post');
    Route::get('/doi-mat-khau/{token}', [AuthUserController::class, 'resetPass'])->name('user.reset_pass');
    Route::post('/doi-mat-khau/{token}', [AuthUserController::class, 'resetPassPost'])->name('user.reset_pass_post');

})->middleware('cacheResponse:600');

// ======================================Ckfinder================================================
Route::any('/ckfinder/connector', '\CKSource\CKFinderBridge\Controller\CKFinderController@requestAction')->name('ckfinder_connector');
Route::any('/ckfinder/browser', '\CKSource\CKFinderBridge\Controller\CKFinderController@browserAction')->name('ckfinder_browser');

// ======================================Backend================================================
// The permission each admin area asks for, named once.
$can = [
    'about' => 'permission:Giới thiệu',
    'contactForms' => 'permission:Khách hàng liên hệ',
    'blog' => 'permission:Quản trị Bài viết',
    'faq' => 'permission:Quản trị FAQ',
    'menus' => 'permission:Quản trị Menu',
    'coupons' => 'permission:Quản trị Mã giảm giá',
    'slides' => 'permission:Quản trị Slide',
    'products' => 'permission:Quản trị Sản phẩm',
    'comments' => 'permission:Quản trị Sản phẩm (Bình luận)',
    'stock' => 'permission:Quản trị Sản phẩm (Kho)',
    'productStats' => 'permission:Quản trị Sản phẩm (Thống kê)',
    'shopInfo' => 'permission:Quản trị Thông tin',
    'customers' => 'permission:Quản trị Tài khoản (Khách hàng)',
    'orders' => 'permission:Quản trị Đơn hàng',
    'ordersOrShopInfo' => 'permission:Quản trị Đơn hàng|Quản trị Thông tin',
    'revenue' => 'permission:Thống kê doanh thu',
    'visitors' => 'permission:Thống kê truy cập',
];

Route::group(['prefix' => 'admin'], function () {

    Route::get('/login', [AuthAdminController::class, 'login'])->name('admin.login');
    Route::redirect('/', '/admin/login');
    Route::post('/login', [AuthAdminController::class, 'loginCheck'])->name('admin.login_check');

});

Route::group(['prefix' => 'admin', 'middleware' => 'admin.login'], function () use ($can) {

    Route::get('/logout', [AuthAdminController::class, 'logout'])->name('admin.logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/dashboard', [DashboardController::class, 'indexPost'])->name('admin.dashboard.post');
    Route::get('/filter-visitor', [DashboardController::class, 'filterVisitor'])->name('dashboard.filter.visitor');
    Route::post('/filter-account-user', [DashboardController::class, 'filterAccountUser'])->name('dashboard.filter.account');
    Route::post('/export-csv', [DashboardController::class, 'export_scv'])->name('export.scv');
    Route::post('/export-csv-day', [DashboardController::class, 'export_scv_day'])->name('export.scvday');
    Route::post('/export-csv-week', [DashboardController::class, 'export_scv_week'])->name('export.scvweek');
    Route::post('/export-csv-month', [DashboardController::class, 'export_scv_month'])->name('export.scvmonth');
    Route::post('/export-csv-month-prev', [DashboardController::class, 'export_scv_monthprev'])->name('export.scvmonthprev');
    Route::post('/export-csv-year', [DashboardController::class, 'export_scv_year'])->name('export.scvyear');
    Route::get('/support', [DashboardController::class, 'support'])->name('support');
    Route::get('/sse-notifications', [DashboardController::class, 'sseNotifications'])->name('sse.noti');
    Route::get('/revenue', [DashboardController::class, 'revenue'])->name('admin.revenue')->middleware($can['revenue']);

    // Visitor
    Route::get('/statistical', [DashboardController::class, 'statistical'])->name('admin.statistical')->middleware($can['visitors']);

    // Product
    Route::resource('product', (ProductAdminController::class))->middleware($can['products']);
    Route::post('/products/update-status/{pro_id}', [ProductAdminController::class, 'updateStatus'])->name('product.update.status');
    Route::post('/products/update-hot/{pro_id}', [ProductAdminController::class, 'updateHot'])->name('product.update.hot');
    Route::get('/products/trashed', [ProductAdminController::class, 'trashed'])->name('product.trashed')->middleware($can['products']);
    Route::get('/product/restore/{cate_id}', [ProductAdminController::class, 'restore'])->name('product.restore')->middleware($can['products']);
    Route::get('/products/restore-all', [ProductAdminController::class, 'restoreAll'])->name('product.restore.all')->middleware($can['products']);
    Route::get('/product/delete/{cate_id}', [ProductAdminController::class, 'delete'])->name('product.delete')->middleware($can['products']);
    Route::get('/products/delete-all', [ProductAdminController::class, 'deleteAll'])->name('product.delete.all')->middleware($can['products']);

    // Thống kê sản phẩm
    Route::get('/products/statistical', [ProductAdminController::class, 'productsStatistical'])->name('product.statistical')->middleware($can['productStats']);

    // Hình ảnh
    Route::resource('image', (ImageController::class))->middleware($can['products']);
    Route::get('/images/{pro_slug}/trashed', [ImageController::class, 'trashed'])->name('image.trashed')->middleware($can['products']);
    Route::get('/image/restore/{img_id}', [ImageController::class, 'restore'])->name('image.restore')->middleware($can['products']);
    Route::get('/images/{pro_slug}/restore-all', [ImageController::class, 'restoreAll'])->name('image.restore.all')->middleware($can['products']);
    Route::get('/image/delete/{img_id}', [ImageController::class, 'delete'])->name('image.delete')->middleware($can['products']);
    Route::get('/images/{pro_slug}/delete-all', [ImageController::class, 'deleteAll'])->name('image.delete.all')->middleware($can['products']);

    // Bình luận
    Route::resource('comment', (CommentAdminController::class))->middleware($can['comments']);
    Route::get('/comments/trashed', [CommentAdminController::class, 'trashed'])->name('comment.trashed')->middleware($can['comments']);
    Route::get('/comment/restore/{comment_id}', [CommentAdminController::class, 'restore'])->name('comment.restore')->middleware($can['comments']);
    Route::get('/comments/restore-all', [CommentAdminController::class, 'restoreAll'])->name('comment.restore.all')->middleware($can['comments']);
    Route::get('/comment/delete/{comment_id}', [CommentAdminController::class, 'delete'])->name('comment.delete')->middleware($can['comments']);
    Route::get('/comments/delete-all', [CommentAdminController::class, 'deleteAll'])->name('comment.delete.all')->middleware($can['comments']);

    // Kho
    Route::resource('stock', (ProductQuantityController::class))->middleware($can['stock']);
    Route::post('/store-new-color', [ProductQuantityController::class, 'storeNewColor'])->name('stock.new.color')->middleware($can['stock']);
    Route::put('/update-color/{color_id}', [ProductQuantityController::class, 'updateColor'])->name('stock.update.color')->middleware($can['stock']);
    Route::delete('/delete-color/{color_id}', [ProductQuantityController::class, 'deleteColor'])->name('stock.delete.color')->middleware($can['stock']);
    Route::put('/update-variant-price/{quantity_id}', [ProductQuantityController::class, 'updatePrice'])->name('stock.update.price')->middleware($can['stock']);
    Route::post('/stock/{pro_slug}/variant', [ProductQuantityController::class, 'storeVariant'])->name('stock.variant.store')->middleware($can['stock']);
    Route::put('/stock/adjust/{quantity_id}', [ProductQuantityController::class, 'adjustStock'])->name('stock.adjust')->middleware($can['stock']);

    // Danh mục sản phẩm
    Route::resource('product-category', (ProductCateController::class))->middleware($can['products']);
    Route::post('/product-categories/update-status/{cate_id}', [ProductCateController::class, 'updateStatus'])->name('product-category.update.status')->middleware($can['products']);
    Route::get('/product-categories/trashed', [ProductCateController::class, 'trashed'])->name('product-category.trashed')->middleware($can['products']);
    Route::get('/product-category/restore/{cate_id}', [ProductCateController::class, 'restore'])->name('product-category.restore')->middleware($can['products']);
    Route::get('/product-categories/restore-all', [ProductCateController::class, 'restoreAll'])->name('product-category.restore.all')->middleware($can['products']);
    Route::get('/product-category/delete/{cate_id}', [ProductCateController::class, 'delete'])->name('product-category.delete')->middleware($can['products']);
    Route::get('/product-categories/delete-all', [ProductCateController::class, 'deleteAll'])->name('product-category.delete.all')->middleware($can['products']);

    // Blog
    Route::resource('blog', (BlogAdminController::class))->middleware($can['blog']);
    Route::get('/blog-trashed', [BlogAdminController::class, 'trashed'])->name('blog.trashed')->middleware($can['blog']);
    Route::get('/blog/restore/{id}', [BlogAdminController::class, 'restore'])->name('blog.restore')->middleware($can['blog']);
    Route::get('/blog-restore-all', [BlogAdminController::class, 'restoreAll'])->name('blog.restoreAll')->middleware($can['blog']);
    Route::delete('/blog/delete/{id}', [BlogAdminController::class, 'forceDelete'])->name('blog.delete')->middleware($can['blog']);
    Route::get('/blog-delete-all', [BlogAdminController::class, 'deleteAll'])->name('blog.deleteAll')->middleware($can['blog']);
    Route::post('/blog-status/{id}', [BlogAdminController::class, 'status'])->name('blog.status')->middleware($can['blog']);
    Route::post('/blog-hot/{id}', [BlogAdminController::class, 'hot'])->name('blog.hot')->middleware($can['blog']);

    // Blog Category
    Route::resource('blog-category', (BlogCateController::class))->middleware($can['blog']);
    Route::get('/blog-category-trashed', [BlogCateController::class, 'trashed'])->name('cate_blog.trashed')->middleware($can['blog']);
    Route::get('/blog-category/restore/{id}', [BlogCateController::class, 'restore'])->name('cate_blog.restore')->middleware($can['blog']);
    Route::get('/blog-category-restore-all', [BlogCateController::class, 'restoreAll'])->name('cate_blog.restoreAll')->middleware($can['blog']);
    Route::delete('/blog-category/delete/{id}', [BlogCateController::class, 'forceDelete'])->name('cate_blog.delete')->middleware($can['blog']);
    Route::get('/blog-category-delete-all', [BlogCateController::class, 'deleteAll'])->name('cate_blog.deleteAll')->middleware($can['blog']);
    Route::post('/blog-category-status/{id}', [BlogCateController::class, 'status'])->name('cate_blog.status')->middleware($can['blog']);

    // Tags Blog
    Route::resource('tags', (TagsAdminController::class))->middleware($can['blog']);
    Route::get('/tag-trashed', [TagsAdminController::class, 'trashed'])->name('tag.trashed')->middleware($can['blog']);
    Route::get('/tag/restore/{id}', [TagsAdminController::class, 'restore'])->name('tag.restore')->middleware($can['blog']);
    Route::get('/tag-restore-all', [TagsAdminController::class, 'restoreAll'])->name('tag.restoreAll')->middleware($can['blog']);
    Route::delete('/tag/delete/{id}', [TagsAdminController::class, 'forceDelete'])->name('tag.delete')->middleware($can['blog']);
    Route::get('/tag-delete-all', [TagsAdminController::class, 'deleteAll'])->name('tag.deleteAll')->middleware($can['blog']);
    Route::post('/tag-status/{id}', [TagsAdminController::class, 'status'])->name('tag.status')->middleware($can['blog']);

    // Account
    Route::resource('account', (AuthAdminController::class));
    Route::get('/account/{encryptedUserId}/edit', [AuthAdminController::class, 'edit'])->name('account.edits');
    Route::get('/account/{encryptedUserId}/edituser', [AuthAdminController::class, 'editUser'])->name('account.edituser')->middleware($can['customers']);
    Route::put('/accounts/{id}', [AuthAdminController::class, 'updateUser'])->name('account.updateuser');
    Route::get('/account-user', [AuthAdminController::class, 'listAccountUser'])->name('account.user')->middleware($can['customers']);
    Route::get('/info/{encryptedUserId}', [AuthAdminController::class, 'editInfo'])->name('account.info');
    Route::post('/info/{id}', [AuthAdminController::class, 'updateInfo'])->name('account.updateinfo');
    Route::post('/change-password', [AuthAdminController::class, 'updatePassword'])->name('change.password');
    Route::post('/exportuser-csv', [AuthAdminController::class, 'exportus_scv'])->name('exportus.scv');
    Route::post('/exportadmin-csv', [AuthAdminController::class, 'exportad_scv'])->name('exportad.scv');

    // Filter Ajax
    Route::post('/filter-dashboard', [DashboardController::class, 'dashboardFilter'])->name('dashboardFilter');
    Route::post('/filter-by-date', [DashboardController::class, 'filterByDate'])->name('filterByDay');

    // Phân quyền
    Route::get('/assign-permission/{encryptedUserId}', [AuthAdminController::class, 'assign_permission'])->name('assign_permission');
    Route::get('/assign-role/{encryptedUserId}', [AuthAdminController::class, 'assign_role'])->name('assign_role');
    Route::post('/insert_roles/{id}', [AuthAdminController::class, 'insert_role']);
    Route::post('/insert_permission/{id}', [AuthAdminController::class, 'insert_permission']);
    Route::post('/insert_permission', [AuthAdminController::class, 'insert_per_permission']);

    // Thông tin liên hệ

    // Mã khuyến mãi
    Route::resource('coupon', (CouponAdminController::class))->middleware($can['coupons']);
    Route::get('/coupons/trashed', [CouponAdminController::class, 'trashed'])->name('coupon.trashed')->middleware($can['coupons']);
    Route::delete('/coupon/soft-delete/{id}', [CouponAdminController::class, 'softDelete'])->name('coupon.softDelete')->middleware($can['coupons']);
    Route::get('/coupon/restore/{id}', [CouponAdminController::class, 'restore'])->name('coupon.restore')->middleware($can['coupons']);
    Route::get('/coupons/restore-all', [CouponAdminController::class, 'restoreAll'])->name('coupon.restoreAll')->middleware($can['coupons']);
    Route::get('/coupons/delete/{id}', [CouponAdminController::class, 'forceDelete'])->name('coupon.delete')->middleware($can['coupons']);
    Route::get('/coupons/delete-all', [CouponAdminController::class, 'deleteAll'])->name('coupon.delete.all')->middleware($can['coupons']);
    Route::post('/exportcou-csv', [CouponAdminController::class, 'exportcou_scv'])->name('exportcou.scv');
    Route::get('/send-coupon', function () {
        abort(404);
    });
    Route::post('/send-coupon', [CouponAdminController::class, 'sendCoupon'])->name('sendCoupon');

    // FAQ Câu hỏi thường gặp
    Route::resource('faq', (FaqAdminController::class))->middleware($can['faq']);
    Route::get('/faqs/trashed', [FaqAdminController::class, 'trashed'])->name('faq.trashed')->middleware($can['faq']);
    Route::get('/faq/{encryptedFaqId}/edit', [FaqAdminController::class, 'edit'])->name('faqs.edit')->middleware($can['faq']);
    Route::delete('/faq/soft-delete/{encryptedFaqId}', [FaqAdminController::class, 'softDelete'])->name('faq.softDelete')->middleware($can['faq']);
    Route::get('/faq/restore/{encryptedFaqId}', [FaqAdminController::class, 'restore'])->name('faq.restore')->middleware($can['faq']);
    Route::get('/faqs/restore-all', [FaqAdminController::class, 'restoreAll'])->name('faq.restoreAll')->middleware($can['faq']);
    Route::get('/faq/delete/{encryptedFaqId}', [FaqAdminController::class, 'forceDelete'])->name('faq.delete')->middleware($can['faq']);
    Route::get('/faqs/delete-all', [FaqAdminController::class, 'deleteAll'])->name('faq.delete.all')->middleware($can['faq']);
    Route::post('/faq-status/{id}', [FaqAdminController::class, 'status'])->name('faq.status');

    // Giới thiệu
    Route::resource('about', (AboutAdminController::class))->middleware($can['about']);
    Route::get('/about/{encryptedAboutId}/edit', [AboutAdminController::class, 'edit'])->name('abouts.edit')->middleware($can['about']);

    // Đơn hàng
    Route::resource('order', (OrderAdminController::class))->middleware($can['orders']);
    Route::get('/order/{encryptedOrderId}/edit', [OrderAdminController::class, 'edit'])->name('orders.edit')->middleware($can['orders']);
    Route::get('/order-print/{encryptedOrderId}', [OrderAdminController::class, 'printOrder'])->name('order.print')->middleware($can['orders']);
    Route::post('/exportorder-csv', [OrderAdminController::class, 'exportorder_scv'])->name('exportorder.scv');
    Route::patch('/order/{order_id}/ma-van-don', [OrderAdminController::class, 'updateShippingCode'])->name('order.shipping_code')->middleware($can['orders']);
    Route::post('/order/{order_id}/tao-van-don', [OrderAdminController::class, 'bookShipment'])->name('order.book_shipment')->middleware($can['orders']);
    Route::post('/order/{order_id}/huy-van-don', [OrderAdminController::class, 'cancelShipment'])->name('order.cancel_shipment')->middleware($can['orders']);
    Route::post('/order/{order_id}/huy-ban-giao', [OrderAdminController::class, 'undoHandover'])->name('order.undo_handover')->middleware($can['orders']);
    Route::post('/order/{order_id}/duyet-huy', [OrderAdminController::class, 'approveCancel'])->name('order.approve_cancel')->middleware($can['orders']);
    Route::post('/order/{order_id}/tu-choi-huy', [OrderAdminController::class, 'rejectCancel'])->name('order.reject_cancel')->middleware($can['orders']);
    Route::post('/order/{order_id}/huy-don', [OrderAdminController::class, 'cancelByShop'])->name('order.cancel_by_shop')->middleware($can['orders']);
    // Server-sent events: the page is written to when GHN's callback lands, so
    // nothing on the client polls.
    Route::get('/order/{order_id}/hanh-trinh-stream', [OrderAdminController::class, 'shipmentStream'])->name('order.shipment_stream')->middleware($can['orders']);
    Route::middleware($can['orders'])->prefix('/order/{order_id}/tra-hang')->name('returns.')->group(function () {
        Route::post('/duyet', [ReturnAdminController::class, 'approve'])->name('approve');
        Route::post('/tu-choi', [ReturnAdminController::class, 'reject'])->name('reject');
        Route::post('/tao-van-don', [ReturnAdminController::class, 'book'])->name('book');
        Route::patch('/ma-van-don', [ReturnAdminController::class, 'shippingCode'])->name('shipping_code');
        Route::post('/huy-van-don', [ReturnAdminController::class, 'cancelShipment'])->name('cancel_shipment');
        Route::delete('/ma-van-don', [ReturnAdminController::class, 'detachShipment'])->name('detach_shipment');
        Route::post('/da-nhan', [ReturnAdminController::class, 'receive'])->name('receive');
        Route::post('/hoan-tien', [ReturnAdminController::class, 'refund'])->name('refund');
    });
    Route::middleware($can['orders'])->prefix('/vi-khach-hang')->name('wallet_admin.')->group(function () {
        Route::get('/', [WalletAdminController::class, 'index'])->name('index');
        Route::post('/rut-tien/{withdrawal_id}/da-chuyen', [WalletAdminController::class, 'markPaid'])->name('withdrawal_paid');
        Route::post('/rut-tien/{withdrawal_id}/tu-choi', [WalletAdminController::class, 'reject'])->name('withdrawal_reject');
    });
    Route::middleware($can['orders'])->prefix('/gia-lap-ghn')->name('ghn_simulator.')->group(function () {
        Route::get('/', [GhnSimulatorController::class, 'index'])->name('index');
        Route::post('/', [GhnSimulatorController::class, 'send'])->name('send');
        Route::post('/goi-that', [GhnSimulatorController::class, 'real'])->name('real');
        Route::post('/tu-hoan-thanh', [GhnSimulatorController::class, 'autoComplete'])->name('auto_complete');
    });
    // One page, two owners: the order rules belong to whoever runs orders,
    // the shop's address and phone to whoever ran the old info page.
    Route::get('/cau-hinh', [ShopSettingController::class, 'edit'])->name('setting.edit')->middleware($can['ordersOrShopInfo']);
    Route::put('/cau-hinh', [ShopSettingController::class, 'update'])->name('setting.update')->middleware($can['orders']);
    Route::put('/cau-hinh/chung', [ShopSettingController::class, 'updateGeneral'])->name('setting.update_general')->middleware($can['shopInfo']);

    // Hình ảnh Slide
    Route::resource('promotion', (PromotionAdminController::class))->middleware($can['slides']);
    Route::get('/promotions/trashed', [PromotionAdminController::class, 'trashed'])->name('promotion.trashed')->middleware($can['slides']);
    Route::delete('/promotions/soft-delete/{id}', [PromotionAdminController::class, 'softDelete'])->name('promotion.softDelete')->middleware($can['slides']);
    Route::get('/promotion/restore/{id}', [PromotionAdminController::class, 'restore'])->name('promotion.restore')->middleware($can['slides']);
    Route::get('/promotions/restore-all', [PromotionAdminController::class, 'restoreAll'])->name('promotion.restoreAll')->middleware($can['slides']);
    Route::get('/promotion/delete/{id}', [PromotionAdminController::class, 'forceDelete'])->name('promotion.delete')->middleware($can['slides']);
    Route::get('/promotions/delete-all', [PromotionAdminController::class, 'deleteAll'])->name('promotion.delete.all')->middleware($can['slides']);
    Route::post('/promotion-status/{id}', [PromotionAdminController::class, 'status'])->name('promotion.status');

    // Danh mục Silde
    Route::resource('cate-slide', (CateSlideAdminController::class))->middleware($can['slides']);
    Route::get('/cateslide/trashed', [CateSlideAdminController::class, 'trashed'])->name('cate_slide.trashed')->middleware($can['slides']);
    Route::delete('/cate-slide/soft-delete/{id}', [CateSlideAdminController::class, 'softDelete'])->name('cate_slide.softDelete')->middleware($can['slides']);
    Route::get('/cate-slide/restore/{id}', [CateSlideAdminController::class, 'restore'])->name('cate_slide.restore')->middleware($can['slides']);
    Route::get('/cateslide/restore-all', [CateSlideAdminController::class, 'restoreAll'])->name('cate_slide.restoreAll')->middleware($can['slides']);
    Route::get('/cate-slide/delete/{id}', [CateSlideAdminController::class, 'forceDelete'])->name('cate_slide.delete')->middleware($can['slides']);
    Route::get('/cateslide/delete-all', [CateSlideAdminController::class, 'deleteAll'])->name('cate_slide.delete.all')->middleware($can['slides']);
    Route::post('/status/{id}', [CateSlideAdminController::class, 'status'])->name('cate_slide.status');

    // Menu
    Route::post('/menus/update-positions', [MenusAdminController::class, 'updatePositions'])->name('menu.updatePositions')->middleware($can['menus']);
    Route::resource('menus', (MenusAdminController::class))->middleware($can['menus']);
    Route::get('/menu/trashed', [MenusAdminController::class, 'trashed'])->name('menu.trashed')->middleware($can['menus']);
    Route::delete('/menus/soft-delete/{id}', [MenusAdminController::class, 'softDelete'])->name('menu.softDelete')->middleware($can['menus']);
    Route::get('/menus/restore/{id}', [MenusAdminController::class, 'restore'])->name('menu.restore')->middleware($can['menus']);
    Route::get('/menu/restore-all', [MenusAdminController::class, 'restoreAll'])->name('menu.restoreAll')->middleware($can['menus']);
    Route::get('/menus/delete/{id}', [MenusAdminController::class, 'forceDelete'])->name('menu.delete')->middleware($can['menus']);
    Route::get('/menu/delete-all', [MenusAdminController::class, 'deleteAll'])->name('menus.delete.all')->middleware($can['menus']);
    Route::post('/menu-status/{id}', [MenusAdminController::class, 'status'])->name('menu.status');

    // Form liên hệ
    Route::resource('contact-form', (ContactFormController::class))->middleware($can['contactForms']);
    Route::put('/handle/{id}', [ContactFormController::class, 'handle'])->name('contact.handle')->middleware($can['contactForms']);
    Route::put('/processing/{id}', [ContactFormController::class, 'Processing'])->name('contact.processing')->middleware($can['contactForms']);
    Route::put('/noprocess/{id}', [ContactFormController::class, 'noProcess'])->name('contact.no_process')->middleware($can['contactForms']);
    Route::get('/contact-form-trashed', [ContactFormController::class, 'trashed'])->name('contact-form-trashed')->middleware($can['contactForms']);
    Route::get('/contact-form/restores/{id}', [ContactFormController::class, 'restore'])->name('contact-form.restore')->middleware($can['contactForms']);
    Route::get('/contact-forms/restore-all', [ContactFormController::class, 'restoreAll'])->name('contact-form.restoreAll')->middleware($can['contactForms']);
    Route::get('/contact-form/delete/{id}', [ContactFormController::class, 'forceDelete'])->name('contact-form.forceDelete')->middleware($can['contactForms']);
    Route::get('/contact-forms/delete-all', [ContactFormController::class, 'deleteAll'])->name('contact-form.delete.all')->middleware($can['contactForms']);

})->middleware('cacheResponse:600');
