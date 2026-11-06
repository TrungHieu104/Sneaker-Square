@push('css-backend')
    <style>
        .list-row + .list-row {
            border-top: 1px solid #eceef1;
            margin-top: .85rem;
            padding-top: .85rem;
        }

        .row-title {
            flex-basis: 260px;
        }

        .min-w-0 {
            min-width: 0;
        }

        .chip {
            text-transform: none;
            font-size: .8125rem;
        }

        .stat {
            white-space: nowrap;
        }

        .stat small {
            display: block;
            color: #a1acb8;
        }

        .stat b {
            font-weight: 600;
            color: #566a7f;
        }

        @media (max-width: 575px) {
            .stat {
                gap: .9rem !important;
                font-size: .8125rem;
                width: 100%;
                justify-content: space-between;
            }

            .row-actions {
                width: 100%;
            }

            .row-actions .btn {
                flex: 1;
            }
        }
    </style>
@endpush
