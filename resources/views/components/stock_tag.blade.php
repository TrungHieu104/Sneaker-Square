{{--
    A shelf tag: what the shop has left of one product, or of one variant.

    @param int $stock       units on the shelf
    @param int $threshold   at or below this the tag turns urgent (default 5)
    @param bool $exact      print the count even when there is plenty
    @param string $id       optional, for the script that re-writes the tag
--}}
@php
    $stock = (int) ($stock ?? 0);
    $threshold = (int) ($threshold ?? 5);
    $exact = (bool) ($exact ?? false);

    $state = $stock === 0 ? 'out' : ($stock <= $threshold ? 'low' : 'in');
    $count = str_pad((string) $stock, 2, '0', STR_PAD_LEFT);
@endphp

@once
    {{-- Inline, the way components.shipment_timeline is: a tag belongs on the
         product page, the listing and the cart, and those do not all publish
         the same stack name. --}}
    <style>
        /* Hallmark · component: stock tag · genre: editorial · theme: project tokens (main.css :root)
         * states: in · low · out
         * contrast: pass — every word is #423F3E on #F5F5F5, 8.6:1
         *
         * Built as a stock-room tag, not a status pill: square like every button
         * on this site, a rail down the left like the current item in the account
         * menu, and colour spent only where it earns its place. "In stock" is the
         * ordinary case and says so in ink — the shop does not need a green light
         * to tell a customer that a shoe is for sale. Orange marks the count worth
         * hurrying for; the hazard rail marks the shelf that is shut.
         */
        .stock-tag {
            --stock-rail: var(--color-secondary);
            display: inline-flex;
            align-items: stretch;
            gap: 8px;
            padding-right: 10px;
            background-color: var(--light);
            color: var(--color-secondary);
            font-family: var(--font-text);
            font-size: 11px;
            font-weight: 500;
            letter-spacing: .12em;
            text-transform: uppercase;
            /* The count is re-written as the customer picks a size; lining
               figures of one width stop the tag twitching as it changes. */
            font-variant-numeric: tabular-nums;
        }

        .stock-tag::before {
            content: '';
            width: 4px;
            flex: 0 0 4px;
            background-color: var(--stock-rail);
        }

        .stock-tag__label,
        .stock-tag__count {
            padding-block: 5px;
            line-height: 16px;
        }

        /* The number carries its weight instead of a colour: none of this
           shop's warm accents clear 4.5:1 on light paper at this size, and a
           count a customer has to squint at is worse than a plain one. */
        .stock-tag__count {
            margin-left: -4px;
            font-weight: 600;
            letter-spacing: .04em;
        }

        .stock-tag[data-state='low'] {
            --stock-rail: var(--color-orange);
        }

        .stock-tag[data-state='out'] {
            --stock-rail: var(--color-red);
        }

        /* A shut shelf, not a red light: the rail is taped off. */
        .stock-tag[data-state='out']::before {
            background-image: repeating-linear-gradient(
                -45deg,
                var(--color-red) 0 3px,
                #ffffff 3px 6px
            );
        }
    </style>
@endonce

<span class="stock-tag" data-state="{{ $state }}" data-threshold="{{ $threshold }}"
    data-label-in="Còn hàng" data-label-exact="Số lượng:" data-label-low="Chỉ còn" data-label-out="Hết hàng"
    @isset($id) id="{{ $id }}" @endisset>
    @if ($state === 'out')
        <span class="stock-tag__label">Hết hàng</span>
    @elseif ($state === 'low')
        <span class="stock-tag__label">Chỉ còn</span><span class="stock-tag__count">{{ $count }}</span>
    @elseif ($exact)
        <span class="stock-tag__label">Số lượng:</span><span class="stock-tag__count">{{ $count }}</span>
    @else
        <span class="stock-tag__label">Còn hàng</span>
    @endif
</span>
