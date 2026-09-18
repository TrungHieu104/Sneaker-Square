<?php

namespace App\Services\Shipping;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Giao Hàng Nhanh, over its public Open API.
 *
 * Everything here runs server side. The token is a shop-wide credential: with
 * it anyone can create real shipments and read every order, so it must never
 * be handed to a browser.
 */
class GhnCarrier implements ShippingCarrier
{
    private const MASTER_DATA_TTL = 60 * 60 * 24 * 7;

    /**
     * "Hàng nhẹ". GHN answers with the services it will actually run on a
     * route, so the id is looked up rather than hardcoded.
     */
    private const LIGHT_GOODS = 2;

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function provinces(): array
    {
        return Cache::remember($this->cacheKey('provinces'), self::MASTER_DATA_TTL, function () {
            $rows = $this->get('/shiip/public-api/master-data/province');

            $provinces = array_map(
                fn (array $row) => ['id' => (int) $row['ProvinceID'], 'name' => trim((string) $row['ProvinceName'])],
                array_filter($rows, fn (array $row) => ($row['IsEnable'] ?? 1) == 1)
            );

            usort($provinces, fn (array $a, array $b) => strcoll($a['name'], $b['name']));

            return array_values($provinces);
        });
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function districts(int $provinceId): array
    {
        return Cache::remember($this->cacheKey("districts.{$provinceId}"), self::MASTER_DATA_TTL, function () use ($provinceId) {
            $rows = $this->post('/shiip/public-api/master-data/district', ['province_id' => $provinceId]);

            return array_values(array_map(
                fn (array $row) => ['id' => (int) $row['DistrictID'], 'name' => trim((string) $row['DistrictName'])],
                array_filter($rows, fn (array $row) => ($row['IsEnable'] ?? 1) == 1)
            ));
        });
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    public function wards(int $districtId): array
    {
        return Cache::remember($this->cacheKey("wards.{$districtId}"), self::MASTER_DATA_TTL, function () use ($districtId) {
            $rows = $this->post('/shiip/public-api/master-data/ward', ['district_id' => $districtId]);

            return array_values(array_map(
                fn (array $row) => ['code' => (string) $row['WardCode'], 'name' => trim((string) $row['WardName'])],
                array_filter($rows, fn (array $row) => ($row['IsEnable'] ?? 1) == 1)
            ));
        });
    }

    public function quote(Shipment $shipment): ShippingQuote
    {
        $serviceId = $this->serviceFor($shipment->toDistrictId);
        $box = config('services.ghn.box');

        $fee = $this->post('/shiip/public-api/v2/shipping-order/fee', [
            'service_id' => $serviceId,
            'service_type_id' => self::LIGHT_GOODS,
            'from_district_id' => (int) config('services.ghn.from_district_id'),
            'from_ward_code' => (string) config('services.ghn.from_ward_code'),
            'to_district_id' => $shipment->toDistrictId,
            'to_ward_code' => $shipment->toWardCode,
            'weight' => max(1, $shipment->weight),
            'length' => (int) $box['length'],
            'width' => (int) $box['width'],
            'height' => (int) $box['height'],
            'insurance_value' => max(0, $shipment->insuranceValue),
        ]);

        return new ShippingQuote(
            fee: (int) ($fee['total'] ?? 0),
            estimated: $this->leadtime($shipment, $serviceId),
            serviceId: $serviceId,
        );
    }

    /**
     * GHN's own words for "do not let the customer open the box". The shop
     * sells shoes on prepayment or COD, and neither case wants the parcel
     * opened at the door.
     */
    private const NO_INSPECTION = 'KHONGCHOXEMHANG';

    /**
     * `payment_type_id` 2 means the receiver pays the shipping fee. The shop
     * already charged it at checkout, so the fee is settled between the shop
     * and GHN, not at the door — that is what cod_amount is separately for.
     */
    private const SHOP_PAYS = 1;

    public function book(ShipmentOrder $order): ShipmentBooking
    {
        if (! config('services.ghn.create_orders')) {
            throw new ShippingUnavailable('Chức năng tạo vận đơn đang tắt (GHN_CREATE_ORDERS).');
        }

        $box = config('services.ghn.box');

        $data = $this->post('/shiip/public-api/v2/shipping-order/create', [
            'payment_type_id' => self::SHOP_PAYS,
            'required_note' => self::NO_INSPECTION,
            'client_order_code' => $order->reference,
            'from_name' => (string) config('services.ghn.from_name'),
            'from_phone' => (string) config('services.ghn.from_phone'),
            'from_address' => (string) config('services.ghn.from_address'),
            'from_district_id' => (int) config('services.ghn.from_district_id'),
            'from_ward_code' => (string) config('services.ghn.from_ward_code'),
            'to_name' => $order->toName,
            'to_phone' => $order->toPhone,
            'to_address' => $order->toAddress,
            'to_district_id' => $order->toDistrictId,
            'to_ward_code' => $order->toWardCode,
            'cod_amount' => max(0, $order->codAmount),
            'insurance_value' => max(0, $order->insuranceValue),
            'weight' => max(1, $order->weight),
            'length' => (int) $box['length'],
            'width' => (int) $box['width'],
            'height' => (int) $box['height'],
            'service_type_id' => self::LIGHT_GOODS,
            'note' => $order->note,
            'items' => $order->items,
        ]);

        $code = trim((string) ($data['order_code'] ?? ''));

        if ($code === '') {
            throw new ShippingUnavailable('GHN nhận đơn nhưng không trả về mã vận đơn.');
        }

        $estimated = $data['expected_delivery_time'] ?? null;

        return new ShipmentBooking(
            code: $code,
            fee: (int) ($data['total_fee'] ?? 0),
            estimated: $estimated
                ? CarbonImmutable::parse($estimated)->setTimezone(config('app.timezone'))
                : null,
        );
    }

    public function cancel(string $code): void
    {
        $this->post('/shiip/public-api/v2/switch-status/cancel', ['order_codes' => [$code]]);
    }

    /**
     * A route GHN has no leadtime for still has a price, so a failure here is
     * logged and swallowed rather than losing the quote.
     */
    private function leadtime(Shipment $shipment, int $serviceId): ?CarbonImmutable
    {
        try {
            $data = $this->post('/shiip/public-api/v2/shipping-order/leadtime', [
                'from_district_id' => (int) config('services.ghn.from_district_id'),
                'from_ward_code' => (string) config('services.ghn.from_ward_code'),
                'to_district_id' => $shipment->toDistrictId,
                'to_ward_code' => $shipment->toWardCode,
                'service_id' => $serviceId,
            ]);
        } catch (Throwable $e) {
            Log::warning('GHN leadtime failed', ['message' => $e->getMessage()]);

            return null;
        }

        $stamp = (int) ($data['leadtime'] ?? 0);

        return $stamp > 0 ? CarbonImmutable::createFromTimestamp($stamp)->setTimezone(config('app.timezone')) : null;
    }

    /**
     * Which service GHN will run between the shop and that district. Cached
     * per district: it changes with GHN's network, not with the parcel.
     */
    private function serviceFor(int $toDistrictId): int
    {
        return Cache::remember($this->cacheKey("service.{$toDistrictId}"), 60 * 60 * 12, function () use ($toDistrictId) {
            $rows = $this->post('/shiip/public-api/v2/shipping-order/available-services', [
                'shop_id' => (int) config('services.ghn.shop_id'),
                'from_district' => (int) config('services.ghn.from_district_id'),
                'to_district' => $toDistrictId,
            ]);

            foreach ($rows as $row) {
                if ((int) ($row['service_type_id'] ?? 0) === self::LIGHT_GOODS) {
                    return (int) $row['service_id'];
                }
            }

            if ($rows === []) {
                throw new ShippingUnavailable('GHN không giao tới khu vực này.');
            }

            return (int) $rows[0]['service_id'];
        });
    }

    /**
     * Keyed by host as well as subject: the sandbox has a geography of its own,
     * and switching environments must not serve one's province list under the
     * other's ids.
     */
    private function cacheKey(string $subject): string
    {
        return 'ghn.'.md5((string) config('services.ghn.host')).'.'.$subject;
    }

    /**
     * @return array<mixed>
     */
    private function get(string $path): array
    {
        return $this->unwrap($this->client()->get($this->url($path)));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<mixed>
     */
    private function post(string $path, array $payload): array
    {
        return $this->unwrap($this->client()->post($this->url($path), $payload));
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.ghn.host'), '/').$path;
    }

    private function client(): PendingRequest
    {
        $token = (string) config('services.ghn.token');

        if ($token === '') {
            throw new ShippingUnavailable('Chưa cấu hình GHN_TOKEN.');
        }

        return Http::withHeaders([
            'Token' => $token,
            'ShopId' => (string) config('services.ghn.shop_id'),
        ])->timeout((int) config('services.ghn.timeout', 8))->acceptJson();
    }

    /**
     * GHN answers 200 with a `code` of its own, so the HTTP status alone does
     * not say whether the call worked.
     *
     * @return array<mixed>
     */
    private function unwrap(Response $response): array
    {
        $body = $response->json();

        if (! is_array($body) || (int) ($body['code'] ?? 0) !== 200) {
            $message = is_array($body) ? ($body['code_message_value'] ?? $body['message'] ?? '') : '';

            throw new ShippingUnavailable('GHN từ chối yêu cầu: '.($message ?: $response->status()));
        }

        $data = $body['data'] ?? [];

        return is_array($data) ? $data : [];
    }
}
