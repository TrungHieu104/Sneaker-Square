{{--
    A picker for a list too long to scroll: type to narrow it down.

    Vanilla JS on purpose: the admin layout loads jQuery 3.6.0 and then 1.9.0 over
    the top of it.

    The real value goes through a hidden input under $name, so validation, old()
    and form="" keep working as they did with a plain <select>.

    @param string      $name         name of the submitted field
    @param iterable    $options      rows of ['value' => ..., 'label' => ...]
    @param string|null $form         id of the <form> when it lives elsewhere
    @param string|null $placeholder  text shown while nothing is picked
    @param mixed       $selected     currently selected value, if any
--}}
@php
    $form ??= null;
    $placeholder ??= 'Chọn...';
    $selected ??= null;
    $fieldId = 'ss-' . $name . '-' . uniqid();
    $selectedLabel = collect($options)->firstWhere('value', $selected)['label'] ?? '';
@endphp

@once
    @push('css-backend')
        <style>
            .searchable-select {
                position: relative;
            }

            .searchable-select__input[aria-expanded='true'] {
                border-color: #696cff;
            }

            /* Room for the clear button, so a long name never runs under it. */
            .searchable-select__input {
                padding-right: 2.25rem;
            }

            .searchable-select__clear {
                position: absolute;
                top: 50%;
                right: 0.6rem;
                transform: translateY(-50%);
                display: flex;
                align-items: center;
                justify-content: center;
                width: 1.25rem;
                height: 1.25rem;
                padding: 0;
                border: 0;
                border-radius: 50%;
                background: transparent;
                color: #a1acb8;
                font-size: 0.75rem;
                line-height: 1;
                cursor: pointer;
            }

            .searchable-select__clear:hover,
            .searchable-select__clear:focus {
                background: #eceef1;
                color: #566a7f;
            }

            .searchable-select__clear[hidden] {
                display: none;
            }

            .searchable-select__list {
                position: absolute;
                top: calc(100% + 2px);
                left: 0;
                right: 0;
                z-index: 1070;
                max-height: 16rem;
                overflow-y: auto;
                margin: 0;
                padding: 0.25rem 0;
                list-style: none;
                background: #fff;
                border: 1px solid #d9dee3;
                border-radius: 0.375rem;
                box-shadow: 0 0.25rem 1rem rgba(161, 172, 184, 0.45);
            }

            .searchable-select__list[hidden] {
                display: none;
            }

            .searchable-select__option {
                padding: 0.4rem 0.75rem;
                font-size: 0.875rem;
                color: #566a7f;
                cursor: pointer;
            }

            .searchable-select__option[aria-selected='true'] {
                font-weight: 600;
            }

            /* One highlight for both the mouse and the arrow keys. */
            .searchable-select__option:hover,
            .searchable-select__option.is-active {
                background: #696cff;
                color: #fff;
            }

            .searchable-select__empty {
                padding: 0.4rem 0.75rem;
                font-size: 0.875rem;
                font-style: italic;
                color: #a1acb8;
            }
        </style>
    @endpush

    @push('script-backend')
        <script>
            (function () {
                // Vietnamese is typed both ways, so "giay" has to find "Giày".
                // Lowercased first: đ survives NFD intact, so Đ has to become đ
                // before the last replace can reach it.
                function fold(text) {
                    return text.toLowerCase().normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .replace(/đ/g, 'd');
                }

                document.querySelectorAll('.searchable-select').forEach(function (root) {
                    const input = root.querySelector('.searchable-select__input');
                    const list = root.querySelector('.searchable-select__list');
                    const hidden = root.querySelector('.searchable-select__value');
                    const options = Array.from(list.querySelectorAll('.searchable-select__option'));
                    const empty = list.querySelector('.searchable-select__empty');
                    const clear = root.querySelector('.searchable-select__clear');
                    // Both halves of the last real choice: restoring the label
                    // alone leaves the box reading "Adidas X" while the field
                    // submits nothing.
                    let committedLabel = input.value;
                    let committedValue = hidden.value;

                    function open() {
                        list.hidden = false;
                        input.setAttribute('aria-expanded', 'true');
                    }

                    function close() {
                        list.hidden = true;
                        input.setAttribute('aria-expanded', 'false');
                        clearActive();
                    }

                    function clearActive() {
                        options.forEach(function (option) { option.classList.remove('is-active'); });
                    }

                    function syncClear() {
                        clear.hidden = input.value === '';
                    }

                    function visible() {
                        return options.filter(function (option) { return !option.hidden; });
                    }

                    function commit(option) {
                        options.forEach(function (other) { other.setAttribute('aria-selected', String(other === option)); });
                        hidden.value = option.dataset.value;
                        input.value = option.textContent.trim();
                        committedLabel = input.value;
                        committedValue = hidden.value;
                        syncClear();
                        close();
                        // Anything listening for a change on the old <select> still hears one.
                        hidden.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    function filter(term) {
                        const needle = fold(term.trim());
                        let shown = 0;

                        options.forEach(function (option) {
                            const match = needle === '' || fold(option.textContent).includes(needle);
                            option.hidden = !match;
                            if (match) { shown++; }
                        });

                        empty.hidden = shown > 0;
                    }

                    function setActive(step) {
                        const items = visible();
                        if (items.length === 0) { return; }

                        const current = items.findIndex(function (item) { return item.classList.contains('is-active'); });
                        const next = current === -1
                            ? (step > 0 ? 0 : items.length - 1)
                            : (current + step + items.length) % items.length;

                        clearActive();
                        items[next].classList.add('is-active');
                        items[next].scrollIntoView({ block: 'nearest' });
                    }

                    input.addEventListener('focus', function () { filter(''); open(); });

                    input.addEventListener('input', function () {
                        hidden.value = '';
                        filter(input.value);
                        syncClear();
                        open();
                    });

                    input.addEventListener('keydown', function (event) {
                        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                            event.preventDefault();
                            if (list.hidden) { filter(input.value); open(); }
                            setActive(event.key === 'ArrowDown' ? 1 : -1);
                            return;
                        }

                        if (event.key === 'Enter') {
                            const active = list.querySelector('.is-active:not([hidden])');
                            if (active) { event.preventDefault(); commit(active); }
                            return;
                        }

                        if (event.key === 'Escape') { restore(); }
                    });

                    options.forEach(function (option) {
                        // mousedown, not click: the input blurs before a click lands.
                        option.addEventListener('mousedown', function (event) {
                            event.preventDefault();
                            commit(option);
                        });
                    });

                    function restore() {
                        input.value = committedLabel;
                        hidden.value = committedValue;
                        syncClear();
                        close();
                    }

                    clear.addEventListener('mousedown', function (event) {
                        // mousedown, not click: blur lands first and restores the
                        // old name.
                        event.preventDefault();
                        options.forEach(function (option) { option.setAttribute('aria-selected', 'false'); });
                        input.value = '';
                        hidden.value = '';
                        committedLabel = '';
                        committedValue = '';
                        syncClear();
                        hidden.dispatchEvent(new Event('change', { bubbles: true }));
                        input.focus();
                        filter('');
                        open();
                    });

                    syncClear();

                    document.addEventListener('mousedown', function (event) {
                        if (!root.contains(event.target) && !list.hidden) { restore(); }
                    });

                    input.addEventListener('blur', function () {
                        if (input.value !== committedLabel) { restore(); }
                    });
                });
            })();
        </script>
    @endpush
@endonce

<div class="searchable-select">
    <input type="hidden" class="searchable-select__value" name="{{ $name }}" value="{{ $selected }}"
        @if ($form) form="{{ $form }}" @endif />

    <input type="text" class="form-control searchable-select__input" id="{{ $fieldId }}"
        value="{{ $selectedLabel }}" placeholder="{{ $placeholder }}"
        autocomplete="off" role="combobox" aria-expanded="false" aria-autocomplete="list" />

    <button type="button" class="searchable-select__clear" title="Xóa lựa chọn" aria-label="Xóa lựa chọn" hidden>
        <i class="fas fa-times"></i>
    </button>

    <ul class="searchable-select__list" role="listbox" hidden>
        @foreach ($options as $option)
            <li class="searchable-select__option" role="option" data-value="{{ $option['value'] }}"
                aria-selected="{{ $option['value'] == $selected ? 'true' : 'false' }}">{{ $option['label'] }}</li>
        @endforeach
        <li class="searchable-select__empty" hidden>Không tìm thấy</li>
    </ul>
</div>
