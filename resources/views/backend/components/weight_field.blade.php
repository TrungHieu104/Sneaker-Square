{{--
    Shipping weight in grams, the unit the carrier's fee API takes.

    The presets exist because a shop owner knows the shoe but not what its box
    weighs on a scale, and a wrong weight is only discovered when the carrier
    reweighs the parcel and bills the difference.

    @param int|string|null $value  current weight
    @param string $uid             unique per form: the edit modal is rendered
                                   once per row, so ids cannot be fixed strings
    @param bool $showErrors        the edit modal is rendered once per row and
                                   $errors is global, so one bad row would
                                   redden every modal on the page
--}}
@php
    $weightPresets = [
        40 => 'Dây giày',
        70 => 'Vớ 1 đôi',
        150 => 'Vớ 3 đôi · mũ',
        250 => 'Chai xịt · dung dịch',
        800 => 'Giày trẻ em',
        950 => 'Giày chạy bộ nhẹ',
        1200 => 'Sneaker phổ thông',
        1400 => 'Cổ cao · đế chunky',
    ];
@endphp

<div class="weight-field">
    <div class="d-flex align-items-center gap-1 mb-2">
        <label for="pro-weight-{{ $uid }}" class="form-label mb-0">Cân nặng</label>
        @include('backend.components.field_hint', [
            'text' => 'Cân nặng cả hộp lúc đóng gói, tính bằng gram. Đơn vị vận chuyển dùng số này để tính cước.',
        ])
    </div>

    <div class="input-group">
        <input type="number" class="form-control weight-input" id="pro-weight-{{ $uid }}"
               name="pro_weight" value="{{ old('pro_weight', $value) }}" min="1" max="50000" step="1">
        <span class="input-group-text">gram</span>
    </div>

    <div class="d-flex flex-wrap gap-1 mt-2">
        @foreach ($weightPresets as $gram => $label)
            <button type="button" class="btn btn-xs btn-outline-secondary weight-preset" data-gram="{{ $gram }}">
                {{ $label }} · {{ number_format($gram) }}g
            </button>
        @endforeach
    </div>

    <small class="text-muted fst-italic d-block mt-1 weight-readable"></small>

    @foreach (($showErrors ?? true) ? $errors->get('pro_weight') : [] as $error)
        <small class="text-danger fst-italic d-block">{{ $error }}</small>
    @endforeach
</div>

@once
    @push('css-backend')
        <style>
            .weight-field .btn-xs {
                padding: 0.15rem 0.45rem;
                font-size: 0.7rem;
                line-height: 1.4;
            }
        </style>
    @endpush

    @push('script-backend')
        <script>
            document.querySelectorAll('.weight-field').forEach(function (field) {
                const input = field.querySelector('.weight-input');
                const readable = field.querySelector('.weight-readable');

                function refresh() {
                    const gram = parseInt(input.value, 10);

                    readable.textContent = gram > 0 ? '≈ ' + (gram / 1000).toFixed(2) + ' kg' : '';

                    field.querySelectorAll('.weight-preset').forEach(function (preset) {
                        preset.classList.toggle('active', parseInt(preset.dataset.gram, 10) === gram);
                    });
                }

                field.querySelectorAll('.weight-preset').forEach(function (preset) {
                    preset.addEventListener('click', function () {
                        input.value = preset.dataset.gram;
                        refresh();
                    });
                });

                input.addEventListener('input', refresh);
                refresh();
            });
        </script>
    @endpush
@endonce
