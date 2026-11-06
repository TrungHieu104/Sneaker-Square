<?php

namespace Tests\Support;

use App\Enums\CustomerSegment;
use App\Enums\OrderStatus;
use App\Models\CustomerSegmentModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Customers with a buying history, for tests about segments.
 */
trait SegmentFixtures
{
    private int $customerNumber = 0;

    private const DELIVERY_FEE = 30_000;

    private function customer(): UserModel
    {
        $number = ++$this->customerNumber;

        return $this->makeUser(email: "khach{$number}@example.test", username: "khach{$number}");
    }

    private function completedOrder(UserModel $user, int $goods = 1_000_000, int $daysAgo = 1, ?int $couponId = null, OrderStatus $status = OrderStatus::Completed): OrderModel
    {
        $at = now()->subDays($daysAgo);

        $id = DB::table('order')->insertGetId([
            'order_code' => 'DH'.Str::upper(Str::random(10)),
            'order_name' => 'A',
            'order_email' => $user->email,
            'order_address' => '1 Võ Văn Ngân',
            'order_local' => 'Thủ Đức',
            'order_phone' => '0912345678',
            'order_delivery_fee' => self::DELIVERY_FEE,
            'order_total' => $goods + self::DELIVERY_FEE,
            'order_payment' => 'cod',
            'order_payment_status' => 1,
            'order_completed_at' => $at,
            'order_date' => $at->toDateString(),
            'order_status' => $status->value,
            'coupon_id' => $couponId,
            'user_id' => $user->user_id,
            'created_at' => $at,
            'updated_at' => $at,
        ], 'order_id');

        return OrderModel::findOrFail($id);
    }

    /**
     * @param  int  $count  how many such customers
     * @return list<UserModel>
     */
    private function buyers(int $count, int $orders, int $lastOrderDays, int $goodsEach): array
    {
        $customers = [];

        for ($i = 0; $i < $count; $i++) {
            $customer = $this->customer();

            for ($n = 0; $n < $orders; $n++) {
                $this->completedOrder($customer, $goodsEach, $lastOrderDays + $n * 30);
            }

            $customers[] = $customer;
        }

        return $customers;
    }

    private function inSegment(UserModel $user, CustomerSegment $segment): void
    {
        CustomerSegmentModel::create([
            'user_id' => $user->user_id,
            'segment' => $segment->value,
            'recency_score' => 2,
            'frequency_score' => 2,
            'monetary_score' => 2,
            'last_order_on' => now()->subDays(10)->toDateString(),
            'order_count' => 2,
            'total_spent' => 2_000_000,
            'computed_at' => now(),
        ]);
    }
}
