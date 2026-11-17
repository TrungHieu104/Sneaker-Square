<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The icon buttons in the action column of every admin list.
 */
class AdminRowActionTest extends TestCase
{
    public function test_nut_co_link_la_the_a_mau_theo_hanh_dong_va_co_ten(): void
    {
        $html = (string) $this->blade('<x-admin.row-action action="edit" title="Chỉnh sửa" href="/admin/faq/1/edit" />');

        $this->assertStringContainsString('<a href="/admin/faq/1/edit" title="Chỉnh sửa" aria-label="Chỉnh sửa" class="row-action row-action-edit">', $html);
        $this->assertStringContainsString('<i class="fas fa-edit"></i>', $html);
    }

    public function test_nut_khong_co_link_mac_dinh_khong_gui_form(): void
    {
        $html = (string) $this->blade('<x-admin.row-action action="view" title="Xem" data-bs-toggle="modal" />');

        $this->assertStringStartsWith('<button ', trim($html));
        $this->assertStringContainsString('class="row-action row-action-view"', $html);
        $this->assertStringContainsString('type="button"', $html);
        $this->assertStringContainsString('data-bs-toggle="modal"', $html);
    }

    public function test_nut_xoa_trong_form_gui_form_va_tat_duoc_khi_khong_xoa_duoc(): void
    {
        $enabled = (string) $this->blade('<x-admin.row-action action="delete" title="Xóa" type="submit" :disabled="false" />');
        $disabled = (string) $this->blade('<x-admin.row-action action="delete" title="Xóa" type="submit" :disabled="true" />');

        $this->assertStringContainsString('type="submit"', $enabled);
        $this->assertStringNotContainsString('disabled', $enabled);
        $this->assertStringContainsString('disabled="disabled"', $disabled);
        $this->assertStringContainsString('row-action-delete', $disabled);
    }

    public function test_khoi_phuc_cung_mau_voi_xem_va_nut_phu_dung_icon_rieng(): void
    {
        $restore = (string) $this->blade('<x-admin.row-action action="restore" title="Khôi phục" href="/r" />');
        $more = (string) $this->blade('<x-admin.row-action action="more" title="Kho hàng" icon="fas fa-warehouse" href="/k" />');

        $this->assertStringContainsString('class="row-action row-action-view"', $restore);
        $this->assertStringContainsString('fa-trash-restore', $restore);
        $this->assertStringContainsString('class="row-action row-action-more"', $more);
        $this->assertStringContainsString('fa-warehouse', $more);
    }
}
