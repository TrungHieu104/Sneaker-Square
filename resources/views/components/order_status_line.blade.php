{{--
    The status line on a customer's order card.

    The carrier half is printed only while there is a parcel to report on: an
    order still being packed, already cancelled or long since finished has no
    lorry anywhere, and a truck icon against it is noise.

    @param \App\Models\OrderModel $order
--}}
@php
    use App\Enums\OrderStatus;
    use App\Services\Shipping\GhnStatus;

    // A gateway order nobody has paid for yet is waiting on the customer, not
    // on the shop, and says so.
    $trangThai = $order->hasStatus(OrderStatus::New) && $order->isAwaitingPayment()
        ? 'Chờ thanh toán'
        : $order->order_status->label();

    $maVanChuyen = (string) ($order->order_shipping_status ?? '');
    $dangVanChuyen = $maVanChuyen !== ''
        && $order->usesGhn()
        && $order->hasStatus(OrderStatus::ReadyToShip, OrderStatus::Delivering, OrderStatus::Delivered);
@endphp

@if ($dangVanChuyen)
    <span class="nkmfr2">
        {{-- Carried on the element, not in a <style> block: a rule written as
             `svg { fill }` inside an inline SVG is a document-wide rule, and
             this line is drawn once per order. --}}
        <svg class="mx-1" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 640 512" fill="#26aa99"
            aria-hidden="true">
            <path
                d="M48 0C21.5 0 0 21.5 0 48V368c0 26.5 21.5 48 48 48H64c0 53 43 96 96 96s96-43 96-96H384c0 53 43 96 96 96s96-43 96-96h32c17.7 0 32-14.3 32-32s-14.3-32-32-32V288 256 237.3c0-17-6.7-33.3-18.7-45.3L512 114.7c-12-12-28.3-18.7-45.3-18.7H416V48c0-26.5-21.5-48-48-48H48zM416 160h50.7L544 237.3V256H416V160zM112 416a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zm368-48a48 48 0 1 1 0 96 48 48 0 1 1 0-96z" />
        </svg>
        {{ GhnStatus::label($maVanChuyen) }}
    </span> |
@endif
<span class="text-co fw-bold text-uppercase">{{ $trangThai }}</span>
