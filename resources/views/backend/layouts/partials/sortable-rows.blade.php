@push('script-backend')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="{{ asset_v('backend/ajax/sortable-rows.js') }}"></script>
    <style>
        .drag-handle { cursor: grab; color: #a1acb8; padding: 4px 6px; border-radius: 4px; }
        .drag-handle:hover { color: #566a7f; background: #f0f1f5; }
        .drag-handle:active { cursor: grabbing; }
        .sortable-ghost td { background: #eef0ff !important; }
    </style>
@endpush
