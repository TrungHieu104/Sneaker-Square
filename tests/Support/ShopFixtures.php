<?php

namespace Tests\Support;

use App\Models\DeliveryInfoModel;
use App\Models\ProductModel;
use App\Models\ProductQuantityModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The minimum a shop needs before an order can exist: one category, one size,
 * one colour, a customer, an address and a product with stock.
 */
trait ShopFixtures
{
    private const SHIPPING_FEE = 30000;

    private function seedLookupTables(): void
    {
        DB::table('category')->insertOrIgnore([
            'cate_id' => 1, 'cate_name' => 'Giày thể thao', 'cate_slug' => 'giay-the-thao',
            'cate_sort' => 1, 'cate_hidden' => 1,
        ]);
        DB::table('size')->insertOrIgnore(['size_id' => 1, 'size' => '42', 'size_hidden' => 1]);
        DB::table('color')->insertOrIgnore([
            'color_id' => 1, 'color' => 'black', 'color_vn' => 'Đen', 'color_hidden' => 1,
        ]);
        DB::table('color')->insertOrIgnore([
            'color_id' => 2, 'color' => 'red', 'color_vn' => 'Đỏ', 'color_hidden' => 1,
        ]);
    }

    private function makeUser(string $email = 'khach@example.test', string $username = 'khachhang', int $role = 0): UserModel
    {
        $user = UserModel::create([
            'username' => $username,
            'name' => 'Nguyễn Văn A',
            'email' => $email,
            'password' => Hash::make('secret-password'),
            'user_role' => $role,
        ]);

        // `user_status` is deliberately left out of the model's $fillable, so
        // an active account has to be set explicitly rather than mass-assigned.
        $user->user_status = 1;
        $user->save();

        return $user;
    }

    private function makeAddress(UserModel $user, int $shippingFee = self::SHIPPING_FEE): DeliveryInfoModel
    {
        return DeliveryInfoModel::create([
            'info_name' => 'Nguyễn Văn A',
            'info_phone' => '0912345678',
            'info_email' => 'khach@example.test',
            'info_address' => '1 Võ Văn Ngân',
            'info_ward' => 'Linh Chiểu',
            'info_district' => 'Thủ Đức',
            'info_province' => 'TP.HCM',
            'info_delivery_fee' => (string) $shippingFee,
            'info_default' => 1,
            'user_id' => $user->user_id,
        ]);
    }

    private function makeProduct(
        int $price = 1_000_000,
        int $salePrice = 0,
        int $stock = 10,
        string $slug = 'nike-air',
        bool $withVariant = true,
    ): ProductModel
    {
        $product = ProductModel::create([
            // Stored already title-cased, the way ProductRequest normalises it.
            'pro_name' => ucwords('Sản phẩm ' . $slug),
            'pro_slug' => $slug,
            'pro_code' => 'SKU-' . $slug,
            'pro_price' => $price,
            'pro_price_sale' => $salePrice,
            'capital_price' => (int) ($price * 0.6),
            'pro_img' => $slug . '.jpg',
            'pro_date' => now()->toDateString(),
            'pro_hidden' => 1,
            'cate_id' => 1,
        ]);

        ProductQuantityModel::create([
            'quantity' => $stock,
            'quantity_date' => now()->toDateString(),
            'pro_id' => $product->pro_id,
            'size_id' => $withVariant ? 1 : null,
            'color_id' => $withVariant ? 1 : null,
        ]);

        return $product;
    }

    /**
     * A cart line as it sits in the session. `pro_price` is deliberately wrong
     * to prove the server no longer trusts that number.
     *
     * @return array<string, mixed>
     */
    private function cartLine(ProductModel $product, int $quantity = 1, ?int $fakePrice = null): array
    {
        return [
            'proSlug' => $product->pro_slug,
            'pro_name' => $product->pro_name,
            'quantity' => $quantity,
            'color_id' => 1,
            'size_id' => 1,
            'pro_price' => $fakePrice ?? $product->pro_price,
        ];
    }

    private function priceVariant(
        ProductModel $product,
        ?int $price = null,
        ?int $salePrice = null,
        ?int $capitalPrice = null,
        ?int $colorId = 1,
        ?int $sizeId = 1,
    ): ProductQuantityModel {
        $variant = ProductQuantityModel::where('pro_id', $product->pro_id)
            ->where('color_id', $colorId)
            ->where('size_id', $sizeId)
            ->firstOrFail();

        $variant->pro_price = $price;
        $variant->pro_price_sale = $salePrice;
        $variant->capital_price = $capitalPrice;
        $variant->save();

        return $variant;
    }

    private function addVariant(
        ProductModel $product,
        int $colorId,
        int $sizeId = 1,
        int $stock = 10,
        ?int $price = null,
        ?int $salePrice = null,
        ?int $capitalPrice = null,
    ): ProductQuantityModel {
        return ProductQuantityModel::create([
            'quantity' => $stock,
            'quantity_date' => now()->toDateString(),
            'pro_id' => $product->pro_id,
            'size_id' => $sizeId,
            'color_id' => $colorId,
            'pro_price' => $price,
            'pro_price_sale' => $salePrice,
            'capital_price' => $capitalPrice,
        ]);
    }

    private function stockOf(ProductModel $product, ?int $sizeId = 1, ?int $colorId = 1): int
    {
        return (int) ProductQuantityModel::where('pro_id', $product->pro_id)
            ->where('size_id', $sizeId)
            ->where('color_id', $colorId)
            ->value('quantity');
    }
}
