@extends('backend.index')

@section('title')
    Menu
@endsection

@section('content')
<h4 class="fw-bold py-3 mb-3">
    <span class="text-muted fw-light">Cấu hình chung /</span>
    <a href="{{ route('menus.index') }}" class="tab-sort">Menu</a>
</h4>

<div class="card mb-4 card-border-top">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3">
        <h5 class="card-header mb-0">Quản lý thanh menu</h5>
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <span class="text-muted small">
                <i class="fas fa-grip-vertical me-1"></i> Kéo thả để sắp xếp thứ tự
            </span>
            @can('Quản trị Menu')
            <a href="{{ route('menus.create') }}" class="btn btn-success btn-sm">
                Thêm mới &nbsp;<i class="bi bi-plus-circle"></i>
            </a>
            <a href="{{ route('menu.trashed') }}" class="btn btn-danger btn-sm">
                Thùng rác &nbsp;<i class='bx bxs-trash'></i>
            </a>
            @endcan
        </div>
    </div>

    <div class="card-body pt-0">

        {{-- Toast thông báo --}}
        <div id="save-toast" class="alert alert-success d-none position-fixed"
             style="top: 20px; right: 20px; z-index: 9999; min-width: 260px; box-shadow: 0 4px 12px rgba(0,0,0,.15);">
            <i class="bi bi-check-circle me-2"></i> Đã lưu thứ tự menu!
        </div>

        @if($rootMenus->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-sitemap fa-2x mb-2 d-block"></i>
                Chưa có menu nào. <a href="{{ route('menus.create') }}">Thêm mới</a>
            </div>
        @else

        {{-- Legend --}}
        <div class="d-flex gap-3 mb-3 small text-muted">
            <span><span class="badge bg-secondary me-1">■</span> Menu cha (cấp 1)</span>
            <span><span class="badge" style="background:#e8eaff;color:#555" class="me-1">■</span> Menu con (cấp 2)</span>
        </div>

        <div id="root-sortable" class="sortable-list" data-parent="0">

            @foreach($rootMenus as $menu)
            <div class="menu-item-wrapper" data-id="{{ $menu->menu_id }}" data-parent="0">

                {{-- Hàng menu cha --}}
                <div class="menu-row d-flex align-items-center gap-2 p-2 mb-1 rounded"
                     style="background: #f1f3f9; border: 1px solid #dee2e6;">
                    <span class="drag-handle text-muted px-1" title="Kéo để sắp xếp" style="cursor:grab;">
                        <i class="fas fa-grip-vertical"></i>
                    </span>
                    <i class="fas fa-bars text-secondary me-1"></i>
                    <span class="fw-semibold flex-grow-1">{{ $menu->menu_name }}</span>
                    <span class="text-muted small me-2 d-none d-md-inline">
                        <i class="bi bi-link-45deg"></i> {{ $menu->menu_link }}
                    </span>
                    <span class="badge bg-secondary me-1">Vị trí {{ $menu->menu_position }}</span>
                    <div class="form-check form-switch mb-0 me-1" title="Hiển thị / Ẩn">
                        <input name="m-status" class="form-check-input status-toggle" type="checkbox"
                               value="1" id="chk_{{ $menu->menu_id }}"
                               {{ $menu->menu_hidden == 1 ? 'checked' : '' }}
                               data-id="{{ $menu->menu_id }}" />
                    </div>
                    @can('Quản trị Menu')
                    <a class="btn btn-primary btn-sm" href="/admin/menus/{{ $menu->menu_id }}/edit" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form class="d-inline" action="{{ route('menu.softDelete', ['id' => $menu->menu_id]) }}" method="post">
                        @csrf @method('DELETE')
                        <button type="submit" onclick="return confirm('Xóa menu này?')" class="btn btn-danger btn-sm" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                    @endcan
                </div>

                {{-- Menu con --}}
                @if(isset($childrenByParent[$menu->menu_id]) && $childrenByParent[$menu->menu_id]->count() > 0)
                <div class="sortable-list ms-4 mb-1" data-parent="{{ $menu->menu_id }}">
                    @foreach($childrenByParent[$menu->menu_id] as $child)
                    <div class="menu-item-wrapper" data-id="{{ $child->menu_id }}" data-parent="{{ $menu->menu_id }}">
                        <div class="menu-row d-flex align-items-center gap-2 p-2 mb-1 rounded"
                             style="background: #f8f9ff; border: 1px solid #e0e3f7;">
                            <span class="drag-handle text-muted px-1" title="Kéo để sắp xếp" style="cursor:grab;">
                                <i class="fas fa-grip-vertical"></i>
                            </span>
                            <i class="fas fa-level-up-alt fa-rotate-90 text-muted small"></i>
                            <span class="flex-grow-1">{{ $child->menu_name }}</span>
                            <span class="text-muted small me-2 d-none d-md-inline">
                                <i class="bi bi-link-45deg"></i> {{ $child->menu_link }}
                            </span>
                            <span class="badge bg-light text-secondary border me-1">Vị trí {{ $child->menu_position }}</span>
                            <div class="form-check form-switch mb-0 me-1" title="Hiển thị / Ẩn">
                                <input name="m-status" class="form-check-input status-toggle" type="checkbox"
                                       value="1" id="chk_{{ $child->menu_id }}"
                                       {{ $child->menu_hidden == 1 ? 'checked' : '' }}
                                       data-id="{{ $child->menu_id }}" />
                            </div>
                            @can('Quản trị Menu')
                            <a class="btn btn-primary btn-sm" href="/admin/menus/{{ $child->menu_id }}/edit" title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form class="d-inline" action="{{ route('menu.softDelete', ['id' => $child->menu_id]) }}" method="post">
                                @csrf @method('DELETE')
                                <button type="submit" onclick="return confirm('Xóa menu này?')" class="btn btn-danger btn-sm" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

            </div>
            @endforeach

        </div>
        @endif
    </div>
</div>
@endsection

@push('script-backend')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const toast = document.getElementById('save-toast');
    let toastTimer;

    function showToast(success = true) {
        toast.className = `alert ${success ? 'alert-success' : 'alert-danger'} position-fixed`;
        toast.innerHTML = success
            ? '<i class="bi bi-check-circle me-2"></i> Đã lưu thứ tự menu!'
            : '<i class="bi bi-x-circle me-2"></i> Lỗi khi lưu!';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('d-none'), 2500);
    }

    function collectPositions() {
        const positions = [];
        document.querySelectorAll('.sortable-list').forEach(list => {
            const parentId = parseInt(list.dataset.parent);
            list.querySelectorAll(':scope > .menu-item-wrapper').forEach((item, index) => {
                positions.push({
                    id: parseInt(item.dataset.id),
                    position: index,
                    parent_id: parentId
                });
            });
        });
        return positions;
    }

    function updatePositionBadges() {
        document.querySelectorAll('.sortable-list').forEach(list => {
            list.querySelectorAll(':scope > .menu-item-wrapper').forEach((item, index) => {
                const badge = item.querySelector('.badge');
                if (badge && badge.textContent.includes('Vị trí')) {
                    badge.textContent = `Vị trí ${index}`;
                }
            });
        });
    }

    function savePositions() {
        const positions = collectPositions();
        fetch('{{ route("menu.updatePositions") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ positions })
        })
        .then(res => res.json())
        .then(() => {
            updatePositionBadges();
            showToast(true);
        })
        .catch(() => showToast(false));
    }

    // Khởi tạo SortableJS cho tất cả danh sách
    document.querySelectorAll('.sortable-list').forEach(list => {
        new Sortable(list, {
            animation: 200,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: savePositions
        });
    });

    // Toggle trạng thái hiển thị
    document.querySelectorAll('.status-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const id = this.dataset.id;
            const status = this.checked ? 1 : 0;
            fetch(`/admin/menu-status/${id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ 'm-status': status })
            });
        });
    });
});
</script>

<style>
.sortable-ghost {
    opacity: 0.35;
    background: #c8d8fb !important;
    border: 2px dashed #6c8ebf !important;
}
.sortable-chosen {
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    transform: scale(1.01);
}
.sortable-drag {
    opacity: 0.95;
}
.drag-handle:active {
    cursor: grabbing;
}
.menu-row {
    transition: box-shadow 0.15s ease, background 0.15s ease;
}
.menu-row:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.menu-item-wrapper {
    user-select: none;
}
</style>
@endpush
