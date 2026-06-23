<?php
use App\Http\Controllers\Backend\AuthAdminController;
use App\Http\Controllers\Backend\BlogAdminController;
use App\Http\Controllers\Backend\BlogCateController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\ProductAdminController;
use App\Http\Controllers\Backend\ImageController;
use App\Http\Controllers\Backend\CommentAdminController;
use App\Http\Controllers\Backend\ProductQuantityController;
use App\Http\Controllers\Backend\ContactAdminController;
use App\Http\Controllers\Backend\MenusAdminController;
use App\Http\Controllers\Backend\FaqAdminController;
use App\Http\Controllers\Backend\ProductCateController;
use App\Http\Controllers\Backend\OrderAdminController;
use App\Http\Controllers\Backend\CouponAdminController;
use App\Http\Controllers\Backend\CateSlideAdminController;
use App\Http\Controllers\Backend\PromotionAdminController;
use App\Http\Controllers\Backend\TagsAdminController;
use App\Http\Controllers\Backend\AboutAdminController;
use App\Http\Controllers\Backend\ContactFormController;

use App\Http\Controllers\Frontend\AuthUserController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Frontend\CommentController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\LoginFB;
use App\Http\Controllers\Frontend\LoginGoogle;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\UserController;
use App\Http\Controllers\Frontend\WishListController;
use App\Http\Controllers\Frontend\DeliveryInfoController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Frontend\PaymentCheckoutController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;


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
Route::fallback(function() {
    abort(404);
});

Route::group(['middleware' => 'web'], function () {

    Route::get('/', [HomeController::class, 'index'])->name('home.page');
    Route::get('/ve-chung-toi', [HomeController::class, 'about'])->name('about.page');
    Route::get('/lien-he', [HomeController::class, 'contact'])->name('contact.page');
    Route::post('/lien-he-form',[HomeController::class,'store'])->name('contact-form.sto');

    // Route Product
    Route::get('/san-pham', [ProductController::class, 'index'])->name('product.page');
    Route::get('/danh-muc/{cate_slug}', [ProductController::class, 'index'])->name('product.by.cate');
    Route::get('/san-pham-hot', [ProductController::class, 'index'])->name('product.hot');
    Route::get('/san-pham-sale', [ProductController::class, 'index'])->name('product.sale');
    Route::get('/san-pham/{pro_slug}', [ProductController::class, 'detail'])->name('product.detail');
    Route::post('/binh-luan/{pro_id}', [CommentController::class, 'store'])->name('comments.store');
    Route::get('/tim-kiem', [SearchController::class, 'search'])->name('search.frontend');

    // Route Product WishList
    Route::get('/san-pham-yeu-thich', [WishListController::class, 'index'])->name('product.wishlist');
    Route::post('/them-san-pham-yeu-thich', [WishListController::class, 'store'])->name('product.wishlist_store');
    Route::post('/yeu-thich-total', [WishListController::class, 'count'])->name('product.wishlist_count');
    Route::post('/xoa-yeu-thich', [WishListController::class, 'destroy'])->name('product.wishlist_destroy');

    // Route Cart
    Route::get('/gio-hang', [ProductController::class, 'cart'])->name('product.cart');
    Route::post('/them-san-pham/{pro_slug}', [ProductController::class, 'addPro'])->name('addProduct.cart');
    Route::post('/ma-giam-gia', [ProductController::class, 'checkCoupon'])->name('checkCoupon.cart');
    Route::post('/bo-ma-giam-gia', [ProductController::class, 'removeCoupon'])->name('removeCoupon.cart');
    Route::post('/xoa-san-pham/{pro_slug}', [ProductController::class, 'delPro'])->name('delProduct.cart');
    Route::get('/xoa-gio-hang', [ProductController::class, 'delcart'])->name('del.cart');
    Route::get('/gio-hang-trong', [ProductController::class, 'emptyCart'])->name('empty.cart');

    // Route Payment
    Route::get('/thanh-toan', [ProductController::class, 'checkout'])->name('product.checkout');
    Route::post('/thanh-toan', [ProductController::class, 'checkoutPOST'])->name('product.checkoutPOST');
    Route::post('/token-delivery', [ProductController::class, 'getToken']);
    Route::resource('dia-chi', (DeliveryInfoController::class))->names([
        'index' => 'diachi.index',
        'create' => 'diachi.create',
        'store' => 'diachi.store',
        'show' => 'diachi.show',
        'edit' => 'diachi.edit',
        'update' => 'diachi.update',
        'destroy' => 'diachi.destroy',
    ]);
    Route::post('/dia-chi-selected', [DeliveryInfoController::class, 'deliInfoSelected'])->name('deliInfoSelected');
    Route::get('/kiem-tra-trang-thai-dat-hang', [ProductController::class, 'processCheckout'])->name('process.checkout');
    Route::get('/dat-hang-thanh-cong', [ProductController::class, 'successCheckout'])->name('success.checkout');
    Route::get('/dat-hang-that-bai', [ProductController::class, 'failedCheckout'])->name('failed.checkout');
    Route::get('/mail-don-hang', [ProductController::class, 'orderMail'])->name('orderMail.checkout');
    Route::get('/don-hang/{order_code}', [ProductController::class, 'orderBill'])->name('orderBill.checkout');
    Route::patch('/don-hang/{order_code}', [ProductController::class, 'cancelOrder'])->name('cancelOrder');
    Route::get('/in-don-hang/{order_code}', [ProductController::class, 'printBill'])->name('printBill.checkout');

    // Route Blog
    Route::get('/bai-viet', [BlogController::class, 'index'])->name('blog.page');
    Route::get('/tag/{tag_slug}', [BlogController::class, 'tags'])->name('tags.news');
    Route::get('/bai-viet/{cate_news_slug}', [BlogController::class, 'index'])->name('cate.news');
    Route::get('/chi-tiet/{news_slug}', [BlogController::class, 'detail'])->name('news.detail');
    Route::get('/tim-kiem-bai-viet', [BlogController::class, 'search'])->name('news.search');

    // Route Policy
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
    Route::middleware(['auth'])->group(function () {
        Route::resource('/thong-tin-tai-khoan', (UserController::class));
        Route::get('cap-nhat-mat-khau',[UserController::class ,'updatePass'])->name('user.update_pass');
        Route::get('thay-doi-mat-khau/{token}',[UserController::class ,'changePass'])->name('user.change_pass')->middleware('auth');
        Route::put('cap-nhat-mat-khau',[UserController::class ,'updatePassPost'])->name('user.update_pass_post');
        Route::post('cap-nhat-email/{id}',[UserController::class ,'changeEmail'])->name('user.change_email');
        Route::get('dia-chi-giao-hang',[UserController::class ,'delivery'])->name('user.delivery');
        Route::post('/dia-chi-default', [DeliveryInfoController::class, 'deliInfoDefault'])->name('deliInfoDefault');
        Route::get('thong-tin-don-hang',[UserController::class ,'userOrder'])->name('user.order');
        Route::post('da-nhan-hang',[UserController::class ,'successOrder'])->name('success.order');
        Route::patch('/yeu-cau-tra-hang/{order_code}',[UserController::class ,'returnOrder'])->name('return.order');
    });

    // Login Google
    Route::get('auth/google', [LoginGoogle::class, 'redirectToGoogle'])->name('login_google');
    Route::get('auth/google/callback', [LoginGoogle::class, 'handleGoogleCallback']);

    // Sign up
    Route::get('/dang-ky', [AuthUserController::class, 'register'])->name('user.register');
    Route::post('/dang-ky', [AuthUserController::class, 'registerPost'])->name('user.register_post');

    // Reset password
    Route::get('/quen-mat-khau', [AuthUserController::class, 'forgot'])->name('user.forgot');
    Route::post('/quen-mat-khau', [AuthUserController::class, 'forgotPost'])->name('user.forgot_post');
    Route::get('/doi-mat-khau/{token}', [AuthUserController::class,'resetPass'])->name('user.reset_pass');;
    Route::post('/doi-mat-khau/{token}', [AuthUserController::class, 'resetPassPost'])->name('user.reset_pass_post');

})->middleware('cacheResponse:600');

// ======================================Ckfinder================================================
Route::any('/ckfinder/connector', '\CKSource\CKFinderBridge\Controller\CKFinderController@requestAction')->name('ckfinder_connector');
Route::any('/ckfinder/browser', '\CKSource\CKFinderBridge\Controller\CKFinderController@browserAction')->name('ckfinder_browser');

// ======================================Backend================================================
Route::group(['prefix' => 'admin'], function () {

    Route::get('/login', [AuthAdminController::class, 'login'])->name('admin.login');
    Route::redirect('/', '/login');
    Route::post('/login', [AuthAdminController::class, 'loginCheck'])->name('admin.login_check');

});

Route::group(['prefix' => 'admin', 'middleware' => 'admin.login'], function () {

    Route::get('/logout', [AuthAdminController::class, 'logout'])->name('admin.logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/dashboard', [DashboardController::class, 'indexPost'])->name('admin.dashboard.post');
    Route::get('/filter-visitor', [DashboardController::class, 'filterVisitor'])->name('dashboard.filter.visitor');
    Route::post('/filter-account-user', [DashboardController::class, 'filterAccountUser'])->name('dashboard.filter.account');
    Route::post('/export-csv', [DashboardController::class,'export_scv'])->name('export.scv');
    Route::post('/export-csv-day', [DashboardController::class,'export_scv_day'])->name('export.scvday');
    Route::post('/export-csv-week', [DashboardController::class,'export_scv_week'])->name('export.scvweek');
    Route::post('/export-csv-month', [DashboardController::class,'export_scv_month'])->name('export.scvmonth');
    Route::post('/export-csv-month-prev', [DashboardController::class,'export_scv_monthprev'])->name('export.scvmonthprev');
    Route::post('/export-csv-year', [DashboardController::class,'export_scv_year'])->name('export.scvyear');
    Route::get('/support', [DashboardController::class,'support'])->name('support');
    Route::get('/check-new-orders', [DashboardController::class, 'checkNewOrders'])->name('check.noti');
    Route::get('/revenue', [DashboardController::class,'revenue'])->name('admin.revenue')->middleware('permission:Thống kê doanh thu');

    // Visitor
    Route::get('/statistical', [DashboardController::class,'statistical'])->name('admin.statistical')->middleware('permission:Thống kê truy cập');

    // Product
    Route::resource('product', (ProductAdminController::class))->middleware('permission:Quản trị Sản phẩm');
    Route::post('/products/update-status/{pro_id}', [ProductAdminController::class, 'updateStatus'])->name('product.update.status');
    Route::post('/products/update-hot/{pro_id}', [ProductAdminController::class, 'updateHot'])->name('product.update.hot');  
    Route::get('/products/trashed', [ProductAdminController::class,'trashed'])->name('product.trashed')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/product/restore/{cate_id}', [ProductAdminController::class,'restore'])->name('product.restore')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/products/restore-all', [ProductAdminController::class,'restoreAll'])->name('product.restore.all')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/product/delete/{cate_id}', [ProductAdminController::class,'delete'])->name('product.delete')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/products/delete-all', [ProductAdminController::class, 'deleteAll'])->name('product.delete.all')->middleware('permission:Quản trị Sản phẩm');
    
    // Thống kê sản phẩm
    Route::get('/products/statistical', [ProductAdminController::class, 'productsStatistical'])->name('product.statistical')->middleware('permission:Quản trị Sản phẩm (Thống kê)');

    // Hình ảnh
    Route::resource('image', (ImageController::class))->middleware('permission:Quản trị Sản phẩm');
    Route::get('/images/{pro_slug}/trashed', [ImageController::class,'trashed'])->name('image.trashed')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/image/restore/{img_id}', [ImageController::class,'restore'])->name('image.restore')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/images/{pro_slug}/restore-all', [ImageController::class,'restoreAll'])->name('image.restore.all')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/image/delete/{img_id}', [ImageController::class,'delete'])->name('image.delete')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/images/{pro_slug}/delete-all', [ImageController::class, 'deleteAll'])->name('image.delete.all')->middleware('permission:Quản trị Sản phẩm');

    // Bình luận
    Route::resource('comment', (CommentAdminController::class))->middleware('permission:Quản trị Sản phẩm (Bình luận)');
    Route::get('/comments/trashed', [CommentAdminController::class,'trashed'])->name('comment.trashed')->middleware('permission:Quản trị Sản phẩm (Bình luận)');
    Route::get('/comment/restore/{comment_id}', [CommentAdminController::class,'restore'])->name('comment.restore')->middleware('permission:Quản trị Sản phẩm (Bình luận)');
    Route::get('/comments/restore-all', [CommentAdminController::class,'restoreAll'])->name('comment.restore.all')->middleware('permission:Quản trị Sản phẩm (Bình luận)');
    Route::get('/comment/delete/{comment_id}', [CommentAdminController::class,'delete'])->name('comment.delete')->middleware('permission:Quản trị Sản phẩm (Bình luận)');
    Route::get('/comments/delete-all', [CommentAdminController::class, 'deleteAll'])->name('comment.delete.all')->middleware('permission:Quản trị Sản phẩm (Bình luận)');

    // Kho
    Route::resource('stock', (ProductQuantityController::class))->middleware('permission:Quản trị Sản phẩm (Kho)');
    Route::post('/store-new-color', [ProductQuantityController::class, 'storeNewColor'])->name('stock.new.color')->middleware('permission:Quản trị Sản phẩm (Kho)');
    Route::put('/update-color/{color_id}', [ProductQuantityController::class, 'updateColor'])->name('stock.update.color')->middleware('permission:Quản trị Sản phẩm (Kho)');
    Route::delete('/delete-color/{color_id}', [ProductQuantityController::class, 'deleteColor'])->name('stock.delete.color')->middleware('permission:Quản trị Sản phẩm (Kho)');

    // Danh mục sản phẩm
    Route::resource('product-category', (ProductCateController::class))->middleware('permission:Quản trị Sản phẩm');
    Route::post('/product-categories/update-status/{cate_id}', [ProductCateController::class, 'updateStatus'])->name('product-category.update.status')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/product-categories/trashed', [ProductCateController::class,'trashed'])->name('product-category.trashed')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/product-category/restore/{cate_id}', [ProductCateController::class,'restore'])->name('product-category.restore')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/product-categories/restore-all', [ProductCateController::class,'restoreAll'])->name('product-category.restore.all')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/product-category/delete/{cate_id}', [ProductCateController::class,'delete'])->name('product-category.delete')->middleware('permission:Quản trị Sản phẩm');
    Route::get('/product-categories/delete-all', [ProductCateController::class, 'deleteAll'])->name('product-category.delete.all')->middleware('permission:Quản trị Sản phẩm');

    // Blog
    Route::resource('blog', (BlogAdminController::class))->middleware('permission:Quản trị Bài viết');
    Route::get('/blog-trashed', [BlogAdminController::class, 'trashed'])->name('blog.trashed')->middleware('permission:Quản trị Bài viết');
    Route::get('/blog/restore/{id}', [BlogAdminController::class, 'restore'])->name('blog.restore')->middleware('permission:Quản trị Bài viết');
    Route::get('/blog-restore-all', [BlogAdminController::class, 'restoreAll'])->name('blog.restoreAll')->middleware('permission:Quản trị Bài viết');
    Route::delete('/blog/delete/{id}', [BlogAdminController::class, 'forceDelete'])->name('blog.delete')->middleware('permission:Quản trị Bài viết');
    Route::get('/blog-delete-all', [BlogAdminController::class, 'deleteAll'])->name('blog.deleteAll')->middleware('permission:Quản trị Bài viết');
    Route::post('/blog-status/{id}', [BlogAdminController::class, 'status'])->name('blog.status')->middleware('permission:Quản trị Bài viết');
    Route::post('/blog-hot/{id}', [BlogAdminController::class, 'hot'])->name('blog.hot')->middleware('permission:Quản trị Bài viết');
    
    // Blog Category
    Route::resource('blog-category', (BlogCateController::class))->middleware('permission:Quản trị Bài viết');
    Route::get('/blog-category-trashed', [BlogCateController::class, 'trashed'])->name('cate_blog.trashed')->middleware('permission:Quản trị Bài viết');
    Route::get('/blog-category/restore/{id}', [BlogCateController::class, 'restore'])->name('cate_blog.restore')->middleware('permission:Quản trị Bài viết');
    Route::get('/blog-category-restore-all', [BlogCateController::class, 'restoreAll'])->name('cate_blog.restoreAll')->middleware('permission:Quản trị Bài viết');
    Route::delete('/blog-category/delete/{id}', [BlogCateController::class, 'forceDelete'])->name('cate_blog.delete')->middleware('permission:Quản trị Bài viết');
    Route::get('/blog-category-delete-all', [BlogCateController::class, 'deleteAll'])->name('cate_blog.deleteAll')->middleware('permission:Quản trị Bài viết');
    Route::post('/blog-category-status/{id}', [BlogCateController::class, 'status'])->name('cate_blog.status')->middleware('permission:Quản trị Bài viết');

    // Tags Blog
    Route::resource('tags', (TagsAdminController::class))->middleware('permission:Quản trị Bài viết');
    Route::get('/tag-trashed', [TagsAdminController::class, 'trashed'])->name('tag.trashed')->middleware('permission:Quản trị Bài viết');
    Route::get('/tag/restore/{id}', [TagsAdminController::class, 'restore'])->name('tag.restore')->middleware('permission:Quản trị Bài viết');
    Route::get('/tag-restore-all', [TagsAdminController::class, 'restoreAll'])->name('tag.restoreAll')->middleware('permission:Quản trị Bài viết');
    Route::delete('/tag/delete/{id}', [TagsAdminController::class, 'forceDelete'])->name('tag.delete')->middleware('permission:Quản trị Bài viết');
    Route::get('/tag-delete-all', [TagsAdminController::class, 'deleteAll'])->name('tag.deleteAll')->middleware('permission:Quản trị Bài viết');
    Route::post('/tag-status/{id}', [TagsAdminController::class, 'status'])->name('tag.status')->middleware('permission:Quản trị Bài viết');

    // Account
    Route::resource('account', (AuthAdminController::class));
    Route::get('/account/{encryptedUserId}/edit', [AuthAdminController::class, 'edit'])->name('account.edits');
    Route::get('/account/{encryptedUserId}/edituser', [AuthAdminController::class, 'editUser'])->name('account.edituser')->middleware('permission:Quản trị Tài khoản (Khách hàng)');
    Route::put('/accounts/{id}', [AuthAdminController::class, 'updateUser'])->name('account.updateuser');
    Route::get('/account-user', [AuthAdminController::class, 'listAccountUser'])->name('account.user')->middleware('permission:Quản trị Tài khoản (Khách hàng)');
    Route::get('/info/{encryptedUserId}', [AuthAdminController::class, 'editInfo'])->name('account.info');
    Route::post('/info/{id}', [AuthAdminController::class, 'updateInfo'])->name('account.updateinfo');
    Route::post('/change-password', [AuthAdminController::class, 'updatePassword'])->name('change.password');
    Route::post('/exportuser-csv', [AuthAdminController::class,'exportus_scv'])->name('exportus.scv');
    Route::post('/exportadmin-csv', [AuthAdminController::class,'exportad_scv'])->name('exportad.scv');

    // Filter Ajax
    Route::post('/filter-dashboard',[DashboardController::class, 'dashboardFilter'])->name('dashboardFilter');
    Route::post('/filter-by-date', [DashboardController::class, 'filterByDate'])->name('filterByDay');

    // Phân quyền
    Route::get('/assign-permission/{encryptedUserId}', [AuthAdminController::class, 'assign_permission'])->name('assign_permission');
    Route::get('/assign-role/{encryptedUserId}', [AuthAdminController::class, 'assign_role'])->name('assign_role');
    Route::post('/insert_roles/{id}', [AuthAdminController::class, 'insert_role']);
    Route::post('/insert_permission/{id}', [AuthAdminController::class, 'insert_permission']);
    Route::post('/insert_permission', [AuthAdminController::class, 'insert_per_permission']);

    // Thông tin liên hệ
    Route::resource('info-contact', (ContactAdminController::class))->middleware('permission:Quản trị Thông tin');
    Route::get('/info-contacts/trashed', [ContactAdminController::class, 'trashed'])->name('info_contact.trashed')->middleware('permission:Quản trị Thông tin');
    Route::delete('/info-contact/soft-delete/{id}', [ContactAdminController::class, 'softDelete'])->name('info_contact.softDelete')->middleware('permission:Quản trị Thông tin');
    Route::get('/info-contact/restore/{id}', [ContactAdminController::class, 'restore'])->name('info_contact.restore')->middleware('permission:Quản trị Thông tin');
    Route::get('/info-contacts/restore-all', [ContactAdminController::class, 'restoreAll'])->name('info_contact.restoreAll')->middleware('permission:Quản trị Thông tin');
    Route::get('/info-contact/delete/{id}', [ContactAdminController::class, 'forceDelete'])->name('info_contact.delete')->middleware('permission:Quản trị Thông tin');
    Route::get('/info-contacts/delete-all', [ContactAdminController::class, 'deleteAll'])->name('info_contact.delete.all')->middleware('permission:Quản trị Thông tin');
    Route::post('/info-status/{id}', [ContactAdminController::class, 'status'])->name('info.status');

    // Mã khuyến mãi
    Route::resource('coupon', (CouponAdminController::class))->middleware('permission:Quản trị Mã giảm giá');
    Route::get('/coupons/trashed', [CouponAdminController::class,'trashed'])->name('coupon.trashed')->middleware('permission:Quản trị Mã giảm giá');
    Route::delete('/coupon/soft-delete/{id}', [CouponAdminController::class,'softDelete'])->name('coupon.softDelete')->middleware('permission:Quản trị Mã giảm giá');
    Route::get('/coupon/restore/{id}', [CouponAdminController::class,'restore'])->name('coupon.restore')->middleware('permission:Quản trị Mã giảm giá');
    Route::get('/coupons/restore-all', [CouponAdminController::class,'restoreAll'])->name('coupon.restoreAll')->middleware('permission:Quản trị Mã giảm giá');
    Route::get('/coupons/delete/{id}', [CouponAdminController::class,'forceDelete'])->name('coupon.delete')->middleware('permission:Quản trị Mã giảm giá');
    Route::get('/coupons/delete-all', [CouponAdminController::class, 'deleteAll'])->name('coupon.delete.all')->middleware('permission:Quản trị Mã giảm giá');
    Route::post('/exportcou-csv', [CouponAdminController::class,'exportcou_scv'])->name('exportcou.scv');
    Route::get('/send-coupon',  function() {abort(404);});
    Route::post('/send-coupon',[CouponAdminController::class,'sendCoupon'])->name('sendCoupon');
    
    // FAQ Câu hỏi thường gặp
    Route::resource('faq', (FaqAdminController::class))->middleware('permission:Quản trị FAQ');
    Route::get('/faqs/trashed', [FaqAdminController::class, 'trashed'])->name('faq.trashed')->middleware('permission:Quản trị FAQ');
    Route::get('/faq/{encryptedFaqId}/edit', [FaqAdminController::class, 'edit'])->name('faqs.edit')->middleware('permission:Quản trị FAQ');
    Route::delete('/faq/soft-delete/{encryptedFaqId}', [FaqAdminController::class, 'softDelete'])->name('faq.softDelete')->middleware('permission:Quản trị FAQ');
    Route::get('/faq/restore/{encryptedFaqId}', [FaqAdminController::class, 'restore'])->name('faq.restore')->middleware('permission:Quản trị FAQ');
    Route::get('/faqs/restore-all', [FaqAdminController::class, 'restoreAll'])->name('faq.restoreAll')->middleware('permission:Quản trị FAQ');
    Route::get('/faq/delete/{encryptedFaqId}', [FaqAdminController::class, 'forceDelete'])->name('faq.delete')->middleware('permission:Quản trị FAQ');
    Route::get('/faqs/delete-all', [FaqAdminController::class, 'deleteAll'])->name('faq.delete.all')->middleware('permission:Quản trị FAQ');
    Route::post('/faq-status/{id}', [FaqAdminController::class, 'status'])->name('faq.status');

    // Giới thiệu
    Route::resource('about', (AboutAdminController::class))->middleware('permission:Giới thiệu');
    Route::get('/about/{encryptedAboutId}/edit', [AboutAdminController::class, 'edit'])->name('abouts.edit')->middleware('permission:Giới thiệu');

    // Đơn hàng
    Route::resource('order', (OrderAdminController::class))->middleware('permission:Quản trị Đơn hàng');
    Route::get('/order/{encryptedOrderId}/edit', [OrderAdminController::class, 'edit'])->name('orders.edit')->middleware('permission:Quản trị Đơn hàng');
    Route::post('/update-order-qty', [OrderAdminController::class, 'update_order_qty'])->name('update.order')->middleware('permission:Quản trị Đơn hàng');
    Route::get('/order-print/{encryptedOrderId}',[OrderAdminController::class,'printOrder'])->name('order.print')->middleware('permission:Quản trị Đơn hàng');
    Route::post('/exportorder-csv', [OrderAdminController::class,'exportorder_scv'])->name('exportorder.scv');

    // Hình ảnh Slide
    Route::resource('promotion', (PromotionAdminController::class))->middleware('permission:Quản trị Slide');
    Route::get('/promotions/trashed', [PromotionAdminController::class, 'trashed'])->name('promotion.trashed')->middleware('permission:Quản trị Slide');
    Route::delete('/promotions/soft-delete/{id}', [PromotionAdminController::class, 'softDelete'])->name('promotion.softDelete')->middleware('permission:Quản trị Slide');
    Route::get('/promotion/restore/{id}', [PromotionAdminController::class, 'restore'])->name('promotion.restore')->middleware('permission:Quản trị Slide');
    Route::get('/promotions/restore-all', [PromotionAdminController::class, 'restoreAll'])->name('promotion.restoreAll')->middleware('permission:Quản trị Slide');
    Route::get('/promotion/delete/{id}', [PromotionAdminController::class, 'forceDelete'])->name('promotion.delete')->middleware('permission:Quản trị Slide');
    Route::get('/promotions/delete-all', [PromotionAdminController::class, 'deleteAll'])->name('promotion.delete.all')->middleware('permission:Quản trị Slide');
    Route::post('/promotion-status/{id}', [PromotionAdminController::class, 'status'])->name('promotion.status');
    
    // Danh mục Silde
    Route::resource('cate-slide', (CateSlideAdminController::class))->middleware('permission:Quản trị Slide');
    Route::get('/cateslide/trashed', [CateSlideAdminController::class, 'trashed'])->name('cate_slide.trashed')->middleware('permission:Quản trị Slide');
    Route::delete('/cate-slide/soft-delete/{id}', [CateSlideAdminController::class, 'softDelete'])->name('cate_slide.softDelete')->middleware('permission:Quản trị Slide');
    Route::get('/cate-slide/restore/{id}', [CateSlideAdminController::class, 'restore'])->name('cate_slide.restore')->middleware('permission:Quản trị Slide');
    Route::get('/cateslide/restore-all', [CateSlideAdminController::class, 'restoreAll'])->name('cate_slide.restoreAll')->middleware('permission:Quản trị Slide');
    Route::get('/cate-slide/delete/{id}', [CateSlideAdminController::class, 'forceDelete'])->name('cate_slide.delete')->middleware('permission:Quản trị Slide');
    Route::get('/cateslide/delete-all', [CateSlideAdminController::class, 'deleteAll'])->name('cate_slide.delete.all')->middleware('permission:Quản trị Slide');
    Route::post('/status/{id}', [CateSlideAdminController::class, 'status'])->name('cate_slide.status');

    // Menu
    Route::resource('menus', (MenusAdminController::class))->middleware('permission:Quản trị Menu');
    Route::get('/menu/trashed', [MenusAdminController::class, 'trashed'])->name('menu.trashed')->middleware('permission:Quản trị Menu');
    Route::delete('/menus/soft-delete/{id}', [MenusAdminController::class, 'softDelete'])->name('menu.softDelete')->middleware('permission:Quản trị Menu');
    Route::get('/menus/restore/{id}', [MenusAdminController::class, 'restore'])->name('menu.restore')->middleware('permission:Quản trị Menu');
    Route::get('/menu/restore-all', [MenusAdminController::class, 'restoreAll'])->name('menu.restoreAll')->middleware('permission:Quản trị Menu');
    Route::get('/menus/delete/{id}', [MenusAdminController::class, 'forceDelete'])->name('menu.delete')->middleware('permission:Quản trị Menu');
    Route::get('/menu/delete-all', [MenusAdminController::class, 'deleteAll'])->name('menus.delete.all')->middleware('permission:Quản trị Menu');
    Route::post('/menu-status/{id}', [MenusAdminController::class, 'status'])->name('menu.status');
    
    // Form liên hệ
    Route::resource('contact-form', (ContactFormController::class))->middleware('permission:Khách hàng liên hệ');
    Route::put('/handle/{id}', [ContactFormController::class, 'handle'])->name('contact.handle')->middleware('permission:Khách hàng liên hệ');
    Route::put('/processing/{id}', [ContactFormController::class, 'Processing'])->name('contact.processing')->middleware('permission:Khách hàng liên hệ');
    Route::put('/noprocess/{id}', [ContactFormController::class, 'noProcess'])->name('contact.no_process')->middleware('permission:Khách hàng liên hệ');
    Route::get('/contact-form-trashed', [ContactFormController::class, 'trashed'])->name('contact-form-trashed')->middleware('permission:Khách hàng liên hệ');
    Route::get('/contact-form/restores/{id}', [ContactFormController::class, 'restore'])->name('contact-form.restore')->middleware('permission:Khách hàng liên hệ');
    Route::get('/contact-forms/restore-all', [ContactFormController::class, 'restoreAll'])->name('contact-form.restoreAll')->middleware('permission:Khách hàng liên hệ');
    Route::get('/contact-form/delete/{id}', [ContactFormController::class, 'forceDelete'])->name('contact-form.forceDelete')->middleware('permission:Khách hàng liên hệ');
    Route::get('/contact-forms/delete-all', [ContactFormController::class, 'deleteAll'])->name('contact-form.delete.all')->middleware('permission:Khách hàng liên hệ');

})->middleware('cacheResponse:600');