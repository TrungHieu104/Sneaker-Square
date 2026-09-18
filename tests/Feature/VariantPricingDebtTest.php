<?php

namespace Tests\Feature;

use App\Models\OrderModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use App\Services\CartPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * The gaps the audit turned up once prices moved onto the variant.
 */
class VariantPricingDebtTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->seedLookupTables();
        config(['responsecache.enabled' => false]);
    }

    // ------------------------------------------------------------ the sale page

    public function test_bien_the_dang_giam_gia_thi_san_pham_len_trang_sale(): void
    {
        $product = $this->makeProduct(price: 100_000, salePrice: 0, slug: 'sale-o-bien-the');
        $this->priceVariant($product, price: 100_000, salePrice: 79_000);

        $onSale = ProductModel::onSale()->pluck('pro_id')->all();

        $this->assertContains(
            $product->pro_id,
            $onSale,
            'Thẻ sản phẩm đã gạch giá thì trang Sale cũng phải liệt kê nó',
        );
    }

    public function test_san_pham_khong_giam_gia_khong_len_trang_sale(): void
    {
        $product = $this->makeProduct(price: 100_000, salePrice: 0, slug: 'khong-sale');

        $this->assertNotContains($product->pro_id, ProductModel::onSale()->pluck('pro_id')->all());
    }

    /**
     * A variant that priced itself does not take part in the product's discount, so
     * a product whose every variant does that is not on sale at all.
     */
    public function test_san_pham_giam_gia_nhung_moi_bien_the_deu_tu_dat_gia_thi_khong_len_sale(): void
    {
        $product = $this->makeProduct(price: 100_000, salePrice: 80_000, slug: 'sale-bi-che');
        $this->priceVariant($product, price: 130_000);

        $loaded = ProductModel::withPriceRange()->find($product->pro_id);

        $this->assertFalse($loaded->isOnSale(), 'Không biến thể nào đang giảm giá');
        $this->assertNotContains($product->pro_id, ProductModel::onSale()->pluck('pro_id')->all());
    }

    /**
     * A variant may be held back from a product-wide discount by pinning its sale
     * price to 0 while still following the product's list price.
     */
    public function test_bien_the_co_the_bi_loai_khoi_dot_giam_gia_cua_san_pham(): void
    {
        $product = $this->makeProduct(price: 100_000, salePrice: 80_000, slug: 'sale-loai-tru');
        $excluded = $this->priceVariant($product, price: null, salePrice: 0);

        $this->assertSame(100_000, $excluded->sellingPrice($product), 'Vẫn bán giá gốc, không theo đợt giảm');
        $this->assertSame(
            ['min' => 100_000, 'max' => 100_000],
            ProductModel::withPriceRange()->find($product->pro_id)->priceRange(),
            'Khoảng giá tính bằng SQL phải khớp với PHP ở cả trường hợp này',
        );
    }

    // -------------------------------------------------------- sorting by price

    public function test_sap_xep_theo_gia_dung_gia_cua_bien_the(): void
    {
        // All three carry a product price of 100k; only the variants differ.
        $cheap = $this->makeProduct(price: 100_000, slug: 'sap-xep-re');
        $this->priceVariant($cheap, price: 50_000, capitalPrice: 30_000);

        $middle = $this->makeProduct(price: 100_000, slug: 'sap-xep-giua');

        $dear = $this->makeProduct(price: 100_000, slug: 'sap-xep-dat');
        $this->priceVariant($dear, price: 300_000);

        $ascending = ProductModel::orderBySellingPrice('asc')->pluck('pro_slug')->all();
        $descending = ProductModel::orderBySellingPrice('desc')->pluck('pro_slug')->all();

        $this->assertSame(['sap-xep-re', 'sap-xep-giua', 'sap-xep-dat'], $ascending);
        $this->assertSame(['sap-xep-dat', 'sap-xep-giua', 'sap-xep-re'], $descending);
    }

    // ------------------------------------- products sold without colour or size

    public function test_san_pham_khong_mau_size_van_dat_hang_duoc(): void
    {
        $user = $this->makeUser();
        $this->makeAddress($user);
        $product = $this->makeProduct(price: 100_000, stock: 10, slug: 'phu-kien', withVariant: false);

        session(['cart' => [[
            'proSlug' => $product->pro_slug,
            'pro_name' => $product->pro_name,
            'quantity' => 2,
            'color_id' => null,
            'size_id' => null,
            'pro_price' => 100_000,
        ]]]);

        $this->actingAs($user)->post('/thanh-toan', ['payment' => 'cod']);

        $order = OrderModel::where('user_id', $user->user_id)->first();

        $this->assertNotNull($order, 'Sản phẩm không màu/size phải đặt hàng được');
        $this->assertSame(232_000, (int) $order->order_total, '2 × 100.000 + 32.000 ship');
        $this->assertSame(8, $this->stockOf($product, null, null), 'Tồn kho phải bị trừ');
    }

    public function test_san_pham_khong_mau_size_ban_theo_gia_rieng_cua_no(): void
    {
        $product = $this->makeProduct(price: 100_000, stock: 10, slug: 'phu-kien-gia-rieng', withVariant: false);
        $this->priceVariant($product, price: 150_000, colorId: null, sizeId: null);

        $this->assertSame(150_000, app(CartPricingService::class)->unitPrice($product, null, null));
    }

    // --------------------------------------------------- stocking through admin

    public function test_nhap_tab_khac_khong_cham_vao_gia_cua_mau(): void
    {
        $admin = $this->makeStockAdmin();
        $product = $this->makeProduct(price: 100_000, stock: 10, slug: 'hang-hon-hop');

        $this->actingAs($admin)->post(route('stock.store'), [
            'pro_id' => $product->pro_id,
            'quantity_date' => now()->toDateString(),
            'pro_type' => 1,
            'quantityOthers' => 5,
            'priceOthers' => 250_000,
        ]);

        $coloured = ProductQuantityModel::where('pro_id', $product->pro_id)
            ->whereNotNull('color_id')
            ->firstOrFail();

        $this->assertNull($coloured->pro_price, 'Giá của biến thể có màu không được bị ghi đè');
        $this->assertSame(10, (int) $coloured->quantity, 'Tồn kho của biến thể có màu không được cộng thêm');
        $this->assertSame(250_000, (int) $this->variantlessRow($product)->pro_price);
    }

    /**
     * MySQL counts each NULL as distinct, so the unique index cannot hold this case
     * and the controller has to.
     */
    public function test_nhap_nhieu_lan_khong_tao_them_dong_khong_mau_size(): void
    {
        $admin = $this->makeStockAdmin();
        $product = $this->makeProduct(price: 100_000, stock: 10, slug: 'nhap-nhieu-lan', withVariant: false);

        foreach ([5, 7] as $amount) {
            $this->actingAs($admin)->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 1,
                'quantityOthers' => $amount,
            ]);
        }

        $rows = ProductQuantityModel::where('pro_id', $product->pro_id)
            ->whereNull('size_id')
            ->whereNull('color_id')
            ->get();

        $this->assertCount(1, $rows, 'Chỉ được có đúng một dòng cho sản phẩm không màu/size');
        $this->assertSame(22, (int) $rows->first()->quantity, '10 + 5 + 7');
    }

    // ------------------------------------------------------- the stock screen

    public function test_man_hinh_kho_khong_vo_khi_san_pham_vua_co_vua_khong_co_size(): void
    {
        $admin = $this->makeStockAdmin();
        $product = $this->makeProduct(price: 100_000, stock: 10, slug: 'kho-hon-hop');
        ProductQuantityModel::create([
            'quantity' => 4,
            'quantity_date' => now()->toDateString(),
            'pro_id' => $product->pro_id,
            'size_id' => null,
            'color_id' => null,
        ]);

        $this->actingAs($admin)
            ->get(route('stock.show', $product->pro_slug))
            ->assertOk();
    }

    public function test_loi_sua_gia_chi_hien_o_dung_bien_the_do(): void
    {
        $admin = $this->makeStockAdmin();
        $product = $this->makeProduct(price: 100_000, stock: 10, slug: 'loi-rieng');
        $other = $this->addVariant($product, colorId: 2);
        $edited = ProductQuantityModel::where('color_id', 1)->firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('stock.show', $product->pro_slug))
            ->put(route('stock.update.price', $edited->quantity_id), ['capital_price' => 900_000]);

        $errors = session('errors');

        $this->assertTrue($errors->getBag('variant-price-'.$edited->quantity_id)->has('pro_price'));
        $this->assertFalse($errors->getBag('variant-price-'.$other->quantity_id)->has('pro_price'));
        $this->assertFalse($errors->getBag('default')->has('pro_price'));
    }

    // -------------------------------------------------- the second audit pass

    /**
     * A blank box keeps the variant's existing price, so the check has to run on
     * what the row will hold after saving.
     */
    public function test_nhap_them_hang_khong_bi_chan_nham_boi_gia_cua_san_pham(): void
    {
        $admin = $this->makeStockAdmin();
        $product = $this->makeProduct(price: 100_000, slug: 'nhap-lai-gia-cao');
        $this->priceVariant($product, price: 120_000, capitalPrice: 70_000);

        $this->actingAs($admin)
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 0,
                'size_id' => [1],
                'color_id' => [1],
                'quantityColorAndSize' => [1 => 5],
                'capitalPriceColor' => [1 => 110_000],
            ])
            ->assertSessionHasNoErrors();

        $variant = ProductQuantityModel::where('color_id', 1)->firstOrFail();

        $this->assertSame(110_000, (int) $variant->capital_price, 'Giá vốn 110k hợp lệ với giá bán 120k của chính biến thể');
    }

    public function test_gia_von_cao_hon_gia_ban_cua_bien_the_van_bi_chan(): void
    {
        $admin = $this->makeStockAdmin();
        $product = $this->makeProduct(price: 100_000, slug: 'nhap-lai-gia-sai');
        $this->priceVariant($product, price: 120_000, capitalPrice: 70_000);

        $this->actingAs($admin)
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 0,
                'size_id' => [1],
                'color_id' => [1],
                'quantityColorAndSize' => [1 => 5],
                'capitalPriceColor' => [1 => 130_000],
            ])
            ->assertSessionHasErrors('priceColor.1');
    }

    public function test_xoa_dong_gio_hang_dung_bien_the_khach_bam(): void
    {
        $product = $this->makeProduct(price: 100_000, slug: 'gio-hai-mau');
        $this->addVariant($product, colorId: 2, price: 120_000);

        session(['cart' => [
            $this->cartLine($product, 1),
            ['proSlug' => $product->pro_slug, 'pro_name' => $product->pro_name,
                'quantity' => 1, 'color_id' => 2, 'size_id' => 1, 'pro_price' => 120_000],
        ]]);

        $this->post(route('delProduct.cart', $product->pro_slug), [
            'color_id' => 2,
            'size_id' => 1,
            'giatri_donhang' => 0,
        ]);

        $left = collect(session('cart'))->pluck('color_id')->all();

        $this->assertSame([1], $left, 'Bấm xóa màu đỏ thì màu đen phải còn lại');
    }

    /**
     * Marking an order delivered records what it actually sold for.
     */
    public function test_thong_ke_ghi_doanh_thu_theo_gia_da_ban(): void
    {
        $user = $this->makeUser();
        $this->makeAddress($user);
        $product = $this->makeProduct(price: 100_000, stock: 5, slug: 'thong-ke');
        $this->addVariant($product, colorId: 2, price: 120_000, capitalPrice: 70_000);

        $cart = $this->cartLine($product, 2);
        $cart['color_id'] = 2;
        session(['cart' => [$cart]]);
        $this->actingAs($user)->post('/thanh-toan', ['payment' => 'cod']);

        $order = OrderModel::firstOrFail();

        // The product's price moves after the sale; the report must not follow.
        $product->update(['pro_price' => 999_000, 'capital_price' => 1_000]);

        $admin = $this->makeStockAdmin();
        $this->actingAs($admin)->put(route('order.update', $order->order_id), [
            'note' => '',
            'status' => 1,
            'deli' => 1,
            'order_product_id' => [$product->pro_id],
            'cou_val' => 0,
        ]);

        $statistic = DB::table('statistical')->first();

        $this->assertNotNull($statistic, 'Phải ghi được bản thống kê');
        $this->assertSame(240_000, (int) $statistic->sales, '2 × 120.000, giá lúc bán');
        $this->assertSame(100_000, (int) $statistic->profit, '2 × (120.000 − 70.000)');
    }

    private function makeStockAdmin(): UserModel
    {
        $admin = $this->makeUser(email: 'kho@example.test', username: 'thukho', role: 1);

        foreach (['Quản trị Sản phẩm', 'Quản trị Sản phẩm (Kho)', 'Quản trị Đơn hàng'] as $name) {
            $admin->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        return $admin;
    }

    private function variantlessRow(ProductModel $product): ProductQuantityModel
    {
        return ProductQuantityModel::where('pro_id', $product->pro_id)
            ->whereNull('size_id')
            ->whereNull('color_id')
            ->firstOrFail();
    }

    // ------------------------------- every row of a colour, not just the first

    /**
     * A blank box merges the delivery into every row of that colour, so the check
     * has to look at every one of them.
     *
     * Asking a single row let this through: the product sells at 1.000.000, one
     * size priced itself at 350.000, and a cost of 500.000 was measured against
     * whichever row the database returned first.
     */
    public function test_gia_von_nhap_kho_khong_duoc_dim_size_khac_xuong_duoi_gia_von(): void
    {
        DB::table('size')->insertOrIgnore(['size_id' => 2, 'size' => '36', 'size_hidden' => 1]);

        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-hai-size');
        ProductQuantityModel::query()->delete();
        $this->addVariant($product, colorId: 1, sizeId: 1);
        $this->addVariant($product, colorId: 1, sizeId: 2, price: 350_000, capitalPrice: 100_000);

        $this->actingAs($this->makeStockAdmin())
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 0,
                'size_id' => [1, 2],
                'color_id' => [1],
                'quantityColorAndSize' => [1 => 5],
                'capitalPriceColor' => [1 => 500_000],
            ])
            ->assertSessionHasErrors('priceColor.1');

        foreach (ProductQuantityModel::where('pro_id', $product->pro_id)->get() as $row) {
            $this->assertGreaterThan(
                $row->capitalPrice($product),
                $row->listPrice($product),
                'size_id '.$row->size_id.' dang ban duoi gia von',
            );
        }
    }

    /**
     * The same check, with the priced row the other way round: it used to pass or
     * fail on row order alone.
     */
    public function test_thu_tu_dong_khong_quyet_dinh_ket_qua_kiem_tra(): void
    {
        DB::table('size')->insertOrIgnore(['size_id' => 2, 'size' => '36', 'size_hidden' => 1]);

        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-dao-thu-tu');
        ProductQuantityModel::query()->delete();
        $this->addVariant($product, colorId: 1, sizeId: 1, price: 350_000, capitalPrice: 100_000);
        $this->addVariant($product, colorId: 1, sizeId: 2);

        $this->actingAs($this->makeStockAdmin())
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 0,
                'size_id' => [1, 2],
                'color_id' => [1],
                'quantityColorAndSize' => [1 => 5],
                'capitalPriceColor' => [1 => 500_000],
            ])
            ->assertSessionHasErrors('priceColor.1');
    }

    /**
     * A cost every row of the colour can carry still goes through.
     */
    public function test_gia_von_hop_le_voi_moi_size_van_duoc_nhap(): void
    {
        DB::table('size')->insertOrIgnore(['size_id' => 2, 'size' => '36', 'size_hidden' => 1]);

        $product = $this->makeProduct(price: 1_000_000, slug: 'giay-von-hop-le');
        ProductQuantityModel::query()->delete();
        $this->addVariant($product, colorId: 1, sizeId: 1);
        $this->addVariant($product, colorId: 1, sizeId: 2, price: 350_000, capitalPrice: 100_000);

        $this->actingAs($this->makeStockAdmin())
            ->post(route('stock.store'), [
                'pro_id' => $product->pro_id,
                'quantity_date' => now()->toDateString(),
                'pro_type' => 0,
                'size_id' => [1, 2],
                'color_id' => [1],
                'quantityColorAndSize' => [1 => 5],
                'capitalPriceColor' => [1 => 200_000],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(200_000, (int) ProductQuantityModel::where('pro_id', $product->pro_id)
            ->where('size_id', 2)->value('capital_price'));
    }
}
