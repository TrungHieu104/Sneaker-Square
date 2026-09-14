{{--
    An explanation a field needs but the form has no room to print.

    Pure CSS on purpose: main.js calls `new bootstrap.Tooltip(...)` but this
    template's bundle publishes no `bootstrap` global, so a `data-bs-toggle
    ="tooltip"` anywhere would throw and take the rest of that file down.

    Font Awesome rather than boxicons: boxicons.css points at
    ../fonts/boxicons.woff2 while the fonts live in fonts/boxicons/, so every
    bx-* icon renders as an empty box.

    @param string $text  what to explain
--}}
@once
    @push('css-backend')
    <style>
        .field-hint {
            position: relative;
            display: inline-block;
            vertical-align: middle;
            line-height: 1;
        }

        .field-hint__btn {
            padding: 0;
            border: 0;
            background: transparent;
            color: #a1acb8;
            font-size: 1rem;
            line-height: 1;
            cursor: pointer;
        }

        .field-hint__btn:hover,
        .field-hint__btn:focus {
            color: #696cff;
            outline: none;
        }

        /* Below the icon, not above: the product edit form sits in a scrollable
           modal, and a bubble reaching upward is clipped the moment its row is
           scrolled near the top of that box. */
        .field-hint__bubble {
            position: absolute;
            top: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            z-index: 1080;
            width: max-content;
            max-width: 260px;
            padding: 0.5rem 0.625rem;
            border-radius: 0.375rem;
            background: #2b2c40;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.2);
            color: #fff;
            font-size: 0.75rem;
            font-style: normal;
            font-weight: 400;
            line-height: 1.4;
            text-align: left;
            white-space: normal;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.15s ease;
            pointer-events: none;
        }

        .field-hint__bubble::after {
            content: '';
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-bottom-color: #2b2c40;
        }

        /* Hover, and focus so a click keeps it open for touch and keyboard. */
        .field-hint__btn:hover + .field-hint__bubble,
        .field-hint__btn:focus + .field-hint__bubble {
            opacity: 1;
            visibility: visible;
        }
    </style>
    @endpush
@endonce

<span class="field-hint">
    <button type="button" class="field-hint__btn" aria-label="{{ $text }}">
        <i class="fas fa-exclamation-circle"></i>
    </button>
    <span class="field-hint__bubble" role="tooltip">{{ $text }}</span>
</span>
