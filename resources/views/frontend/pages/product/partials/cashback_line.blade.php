{{-- Bootstrap's tooltip needs Popper, which the storefront does not load. Focus
     stands in for hover on a phone: tapping the mark opens it. --}}
<div class="d-flex justify-content-between align-items-center gap-2 mb-3 text-success small">
    <span>
        Hoàn vào ví SPay ({{ $quote['percent'] }}%{{ $quote['cap'] ? ', tối đa '.number_format($quote['cap'], 0, ',', '.').'đ' : '' }})
        <span class="cashback-tip" tabindex="0" role="button" aria-label="Khi nào nhận tiền hoàn">
            <i class="bi bi-question-circle"></i>
            <span class="cashback-tip__body">
                Tiền được cộng vào ví SPay trong vòng {{ $days }} ngày sau khi đơn hoàn tất. Không áp dụng cho đơn huỷ hoặc trả hàng.
            </span>
        </span>
    </span>
    <span class="fw-semibold text-nowrap">+ {{ number_format($quote['amount'], 0, ',', '.') }} đ</span>
</div>

@once
    @push('css-access')
        <style>
            .cashback-tip {
                position: relative;
                cursor: help;
                margin-left: 2px;
                outline: none;
            }

            .cashback-tip__body {
                display: none;
                position: absolute;
                bottom: calc(100% + 8px);
                left: 50%;
                transform: translateX(-50%);
                width: 230px;
                padding: 8px 10px;
                border-radius: 4px;
                background: #333;
                color: #fff;
                font-size: 12px;
                line-height: 1.5;
                text-align: left;
                z-index: 5;
            }

            .cashback-tip__body::after {
                content: "";
                position: absolute;
                top: 100%;
                left: 50%;
                margin-left: -5px;
                border: 5px solid transparent;
                border-top-color: #333;
            }

            .cashback-tip:hover .cashback-tip__body,
            .cashback-tip:focus .cashback-tip__body {
                display: block;
            }
        </style>
    @endpush
@endonce
