{{--
    Every return the customer has opened on this order: a list first, one
    request's docket when they pick it. Both live in the same modal — stacking
    modals to read a slip is a lot of machinery for one step back.

    @param \App\Models\OrderModel $order
--}}
@php
    use App\Models\OrderReturnModel;
    use App\Services\Returns\OrderReturns;

    $danhSach = $order->orderReturns;
    $tong = $danhSach->count();

    $trangThaiCua = fn (OrderReturnModel $r) => match ($r->status) {
        OrderReturnModel::REFUNDED => 'done',
        OrderReturnModel::REJECTED => 'refused',
        OrderReturnModel::CANCELLED => 'cancelled',
        default => 'open',
    };
@endphp

@once
    <style>
        /* Hallmark · component: return log · genre: editorial · theme: project tokens (main.css :root)
         * states: open · done · refused · cancelled
         * contrast: pass — ink #423F3E 9.1:1, muted #6B6867 5.3:1, both on white
         *
         * Built as a shop's docket book: a list of slips, then the slip itself.
         * Each slip carries a 4px rail — the same device as the stock tag, with
         * the same meanings: orange for the thing still being worked on,
         * taped-off red for the shelf that is shut, which is what a refusal is.
         * The ledger underneath runs dotted leaders out to the times, because
         * that is how a counter writes down what happened and when.
         */
        #returnDetail {
            --docket-muted: #6B6867;
        }

        .return-log__row {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            padding: 14px 16px 14px 22px;
            background-color: #ffffff;
            border: 1px solid rgba(0, 0, 0, .09);
            color: var(--color-secondary);
            font-family: var(--font-text);
            text-align: left;
        }

        .return-log__row + .return-log__row {
            margin-top: 10px;
        }

        .return-log__row:hover,
        .return-log__row:focus-visible {
            background-color: var(--light);
        }

        .return-log__row:focus-visible {
            outline: 2px solid var(--color-orange);
            outline-offset: 2px;
        }

        .return-log__row::before,
        .return-log__docket::before {
            content: '';
            position: absolute;
            inset: -1px auto -1px -1px;
            width: 4px;
            background-color: var(--docket-rail);
        }

        .return-log__row,
        .return-log__docket {
            --docket-rail: var(--color-orange);
        }

        .return-log__row[data-state='done'],
        .return-log__docket[data-state='done'] {
            --docket-rail: var(--color-success);
        }

        .return-log__row[data-state='cancelled'],
        .return-log__docket[data-state='cancelled'] {
            --docket-rail: var(--grey);
        }

        .return-log__row[data-state='refused'],
        .return-log__docket[data-state='refused'] {
            --docket-rail: var(--color-red);
        }

        /* A refusal shuts the slip the way an empty shelf shuts a size. */
        .return-log__row[data-state='refused']::before,
        .return-log__docket[data-state='refused']::before {
            background-image: repeating-linear-gradient(-45deg,
                var(--color-red) 0 3px,
                #ffffff 3px 6px);
        }

        .return-log__row-main {
            flex: 1 1 auto;
            min-width: 0;
        }

        .return-log__row-top {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }

        .return-log__row-when {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .return-log__row-state {
            margin-left: auto;
            font-weight: 600;
        }

        .return-log__row-what {
            font-size: 13px;
            color: var(--docket-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .return-log__chevron {
            flex: 0 0 auto;
            color: var(--docket-muted);
            font-size: 20px;
            line-height: 1;
        }

        .return-log__back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 14px;
            padding: 0;
            background: none;
            border: 0;
            color: var(--docket-muted);
            font-family: var(--font-text);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .return-log__back:hover {
            color: var(--color-orange-hover);
        }

        .return-log__docket {
            position: relative;
            padding: 16px 18px 16px 22px;
            background-color: #ffffff;
            border: 1px solid rgba(0, 0, 0, .09);
            color: var(--color-secondary);
            font-family: var(--font-text);
        }

        .return-log__head {
            display: flex;
            align-items: baseline;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .return-log__state {
            font-size: 15px;
            font-weight: 600;
        }

        .return-log__sent,
        .return-log__meta {
            font-size: 12px;
            color: var(--docket-muted);
            font-variant-numeric: tabular-nums;
        }

        .return-log__sent {
            margin-left: auto;
        }

        .return-log__label {
            margin: 16px 0 8px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--docket-muted);
        }

        .return-log__line {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 7px 0;
        }

        .return-log__line + .return-log__line {
            border-top: 1px dotted rgba(0, 0, 0, .14);
        }

        .return-log__variant {
            font-size: 12px;
            color: var(--docket-muted);
        }

        .return-log__qty {
            white-space: nowrap;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .return-log__note {
            padding: 10px 12px;
            background-color: var(--light);
            border-left: 3px solid var(--docket-rail);
            font-size: 13px;
        }

        .return-log__steps {
            list-style: none;
            margin: 0;
            padding: 0;
            font-size: 13px;
        }

        .return-log__step {
            display: flex;
            align-items: baseline;
            gap: 6px;
            padding: 4px 0;
        }

        /* The dotted run between a step and its time, as on a paper docket. */
        .return-log__leader {
            flex: 1 1 auto;
            min-width: 12px;
            border-bottom: 1px dotted rgba(0, 0, 0, .28);
            transform: translateY(-3px);
        }

        .return-log__time {
            color: var(--docket-muted);
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .return-log__photos {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .return-log__photos img {
            width: 76px;
            height: 76px;
            object-fit: cover;
            border: 1px solid rgba(0, 0, 0, .09);
        }

        .return-log__cancel {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px dotted rgba(0, 0, 0, .14);
        }

        .return-log__hint {
            margin-top: 6px;
            font-size: 12px;
            color: var(--docket-muted);
        }

        @media (max-width: 575.98px) {
            .return-log__row-state,
            .return-log__sent {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
@endonce

<div class="modal fade" id="returnDetail" tabindex="-1" aria-labelledby="returnDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h1 class="modal-title fs-5" id="returnDetailLabel">Danh sách yêu cầu trả hàng</h1>
                    <div class="return-log__meta">
                        Đơn {{ $order->order_code }} &middot; {{ $tong }} yêu cầu đã gửi
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div data-return-pane="list">
                    @foreach ($danhSach as $return)
                        @php
                            $ten = $return->items->first()?->line?->pro_name ?? 'Sản phẩm trong đơn';
                            $them = $return->items->count() - 1;
                        @endphp
                        <button type="button" class="return-log__row" data-state="{{ $trangThaiCua($return) }}"
                            data-return-open="{{ $return->return_id }}">
                            <span class="return-log__row-main">
                                <span class="return-log__row-top">
                                    <span class="return-log__row-when">{{ $return->created_at->format('H:i d/m/Y') }}</span>
                                    <span class="return-log__row-state">{{ $return->statusLabel() }}</span>
                                </span>
                                <span class="return-log__row-what">
                                    Trả {{ $ten }}@if ($them > 0) và {{ $them }} sản phẩm khác @endif
                                </span>
                            </span>
                            <span class="return-log__chevron" aria-hidden="true">&rsaquo;</span>
                        </button>
                    @endforeach
                </div>

                @foreach ($danhSach as $return)
                    @php
                        $anh = OrderReturns::imageUrls($return);

                        // One row per step that has actually happened: a request
                        // that was refused never reaches the others, and an empty
                        // row says nothing.
                        $moc = array_filter([
                            ['Gửi yêu cầu', $return->created_at],
                            [match ($return->status) {
                                OrderReturnModel::REJECTED => 'Cửa hàng từ chối',
                                OrderReturnModel::CANCELLED => 'Bạn huỷ yêu cầu',
                                default => 'Cửa hàng duyệt',
                            }, $return->decided_at],
                            ['Cửa hàng nhận hàng trả', $return->received_at],
                            ['Hoàn tiền vào ví', $return->refunded_at],
                        ], fn ($dong) => $dong[1] !== null);
                    @endphp

                    <div data-return-pane="detail" data-return-id="{{ $return->return_id }}" hidden>
                        <button type="button" class="return-log__back" data-return-back>
                            &lsaquo; Tất cả yêu cầu
                        </button>

                        <article class="return-log__docket" data-state="{{ $trangThaiCua($return) }}">
                            <header class="return-log__head">
                                <span class="return-log__state">{{ $return->statusLabel() }}</span>
                                <time class="return-log__sent">Gửi lúc {{ $return->created_at->format('H:i d/m/Y') }}</time>
                            </header>

                            @if ($return->status === OrderReturnModel::REJECTED && $return->reject_reason)
                                <div class="return-log__note">Lý do từ chối: {{ $return->reject_reason }}</div>
                            @endif

                            @if ($return->status === OrderReturnModel::REFUNDED)
                                <div class="return-log__note">
                                    Đã hoàn {{ number_format((int) $return->refund_amount, 0, ',', '.') }} VNĐ vào
                                    <a href="{{ route('user.wallet') }}">SPay</a>.
                                </div>
                            @endif

                            <div class="return-log__label">Sản phẩm đã gửi trả</div>
                            @foreach ($return->items as $dong)
                                <div class="return-log__line">
                                    <div>
                                        <div>{{ $dong->line?->pro_name }}</div>
                                        <div class="return-log__variant">
                                            Size {{ $dong->line?->size }} &middot; {{ $dong->line?->color }}
                                        </div>
                                    </div>
                                    <div class="return-log__qty">&times;{{ $dong->quantity }}</div>
                                </div>
                            @endforeach
                            @if ($return->isPartial())
                                <div class="return-log__hint">Trả một phần đơn nên phí vận chuyển không được hoàn.</div>
                            @endif

                            <div class="return-log__label">Lý do</div>
                            <div>{{ $return->reasonLabel() }}</div>
                            @if ($return->description)
                                <div class="return-log__variant mt-1">{{ $return->description }}</div>
                            @endif

                            @if ($anh !== [])
                                <div class="return-log__label">Ảnh đã gửi</div>
                                <div class="return-log__photos">
                                    @foreach ($anh as $duongDan)
                                        <a href="{{ $duongDan }}" target="_blank" rel="noopener">
                                            <img src="{{ $duongDan }}" alt="Ảnh sản phẩm trả">
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            <div class="return-log__label">Diễn biến</div>
                            <ul class="return-log__steps">
                                @foreach ($moc as [$ten, $luc])
                                    <li class="return-log__step">
                                        <span>{{ $ten }}</span>
                                        <span class="return-log__leader"></span>
                                        <span class="return-log__time">{{ $luc->format('H:i d/m/Y') }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($return->status === OrderReturnModel::REQUESTED)
                                <form action="{{ route('return.cancel', $order->order_code) }}" method="post"
                                    class="return-log__cancel">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="border grey-hover border-1 custom-btn text-dark">
                                        Huỷ yêu cầu này
                                    </button>
                                    <div class="return-log__hint">
                                        Huỷ xong bạn vẫn gửi lại được yêu cầu khác trong thời hạn trả hàng.
                                    </div>
                                </form>
                            @endif
                        </article>
                    </div>
                @endforeach
            </div>
            <div class="modal-footer">
                <button type="button" class="border grey-hover border-1 custom-btn text-dark"
                    data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

@once
    @push('script-access')
        <script>
            (function () {
                const modal = document.getElementById('returnDetail');
                if (!modal) {
                    return;
                }

                const title = modal.querySelector('.modal-title');
                const tieuDeGoc = title.textContent.trim();
                const panes = modal.querySelectorAll('[data-return-pane]');

                function show(id) {
                    panes.forEach(function (pane) {
                        const la = pane.dataset.returnPane === (id ? 'detail' : 'list')
                            && (!id || pane.dataset.returnId === id);
                        pane.hidden = !la;
                    });
                    title.textContent = id ? 'Chi tiết yêu cầu' : tieuDeGoc;
                }

                modal.addEventListener('click', function (e) {
                    const mo = e.target.closest('[data-return-open]');
                    if (mo) {
                        show(mo.dataset.returnOpen);
                        modal.querySelector('.modal-body').scrollTop = 0;
                        return;
                    }
                    if (e.target.closest('[data-return-back]')) {
                        show(null);
                    }
                });

                // Reopening the modal should start at the list, not wherever the
                // customer left off reading.
                modal.addEventListener('hidden.bs.modal', function () {
                    show(null);
                });
            })();
        </script>
    @endpush
@endonce
