{{--
    The admin's one confirmation dialog, in place of the browser's confirm().
    A form, link or radio asks it first by carrying data-confirm="<question>",
    and may name its button with data-confirm-ok="<label>".
--}}
<div class="modal fade" id="admin-confirm" tabindex="-1" aria-labelledby="admin-confirm-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="admin-confirm-title">Xác nhận</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body" id="admin-confirm-text"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
                <button type="button" class="btn btn-danger" id="admin-confirm-ok">Đồng ý</button>
            </div>
        </div>
    </div>
</div>
