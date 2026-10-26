{{-- One line of an open cart. Hovering the name shows the product as the shop has it now: every colour and size, what is left and at what price. --}}
@php
    $sanPham = $stock[(int) $dong['product']->pro_id] ?? null;
    $tienNho = fn (int $soTien) => number_format($soTien, 0, ',', '.').'đ';
@endphp
<div class="small text-truncate" style="max-width: 200px;">
    <span class="stock-trigger" tabindex="0" role="button">{{ $dong['product']->pro_name }}</span>
    <span class="text-muted">
        @if ($dong['size']) · size {{ $dong['size'] }} @endif · ×{{ $dong['quantity'] }}
    </span>
    @if ($sanPham)
        <template class="stock-card">
            <div class="d-flex gap-2 mb-2">
                <img src="{{ asset($sanPham['product']->pro_img) }}" alt="" width="56" height="56"
                    class="rounded border flex-shrink-0" style="object-fit: cover;" onerror="this.src='/uploads/img_error2.jpg'">
                <div>
                    <div class="fw-semibold">{{ $sanPham['product']->pro_name }}</div>
                    @if ($sanPham['product']->isOnSale())
                        <span class="text-primary fw-semibold">{{ $sanPham['product']->displaySellingPrice() }}đ</span>
                        <s class="text-muted ms-1">{{ $sanPham['product']->displayListPrice() }}đ</s>
                    @else
                        <span class="fw-semibold">{{ $sanPham['product']->displayListPrice() }}đ</span>
                    @endif
                    <div class="text-muted">Còn {{ array_sum(array_column($sanPham['variants'], 'quantity')) }} đôi trong kho</div>
                </div>
            </div>
            @foreach (collect($sanPham['variants'])->groupBy('color') as $mau => $bienThe)
                @php
                    $gia = $bienThe->min('price');
                    $giaGoc = $bienThe->min('list_price');
                @endphp
                <div class="mb-1">
                    <span class="fw-semibold">{{ $mau ?: 'Không màu' }}</span>
                    <span class="text-muted">· {{ $tienNho($gia) }}@if ($gia < $giaGoc) <s>{{ $tienNho($giaGoc) }}</s>@endif</span>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    @foreach ($bienThe as $bt)
                        @php $trongGio = $bt['color_id'] === $dong['color_id'] && $bt['size_id'] === $dong['size_id']; @endphp
                        <span class="badge {{ $trongGio ? 'bg-warning text-dark' : ($bt['quantity'] > 0 ? 'bg-label-success' : 'bg-label-secondary text-decoration-line-through') }}">
                            {{ $bt['size'] ?? '—' }}: {{ $bt['quantity'] }}
                        </span>
                    @endforeach
                </div>
            @endforeach
            <div class="text-muted">Size: số đôi còn. Ô vàng là biến thể khách để trong giỏ.</div>
        </template>
    @endif
</div>
