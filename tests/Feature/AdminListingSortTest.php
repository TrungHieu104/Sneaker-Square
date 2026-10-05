<?php

namespace Tests\Feature;

use App\Models\TagsModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\ShopFixtures;
use Tests\TestCase;

/**
 * Every admin listing sorts by the column named in its own URL, which used to
 * reach the query unchecked.
 */
class AdminListingSortTest extends TestCase
{
    use RefreshDatabase;
    use ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedLookupTables();
    }

    private function blogAdmin(): UserModel
    {
        $admin = $this->makeUser(email: 'admin@example.test', username: 'quantri', role: 1);
        $admin->givePermissionTo(Permission::findOrCreate('Quản trị Bài viết', 'web'));

        return $admin;
    }

    private function trashTag(string $content): void
    {
        TagsModel::create(['tag_content' => $content, 'tag_slug' => str($content)->slug()])->delete();
    }

    public function test_thung_rac_sap_xep_dung_theo_cot_duoc_chon(): void
    {
        $this->trashTag('Zeta');
        $this->trashTag('Alpha');
        $this->trashTag('Mid');

        // The header link carries the direction to flip from, so `desc` asks for A to Z.
        $tags = $this->actingAs($this->blogAdmin())
            ->get(route('tag.trashed', ['sort-by' => 'tag_content', 'sort-type' => 'desc']))
            ->assertOk()
            ->viewData('tagTrash');

        $this->assertSame(['Alpha', 'Mid', 'Zeta'], collect($tags->items())->pluck('tag_content')->all());
    }

    public function test_cot_sap_xep_khong_ton_tai_thi_quay_ve_mac_dinh(): void
    {
        $this->trashTag('Alpha');

        $this->actingAs($this->blogAdmin())
            ->get(route('tags.index', ['sort-by' => 'khong_co_cot_nay', 'sort-type' => 'asc']))
            ->assertOk()
            ->assertViewHas('orderBy', 'tag_id');
    }
}
