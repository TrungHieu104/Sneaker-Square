<?php

namespace App\Console\Commands;

use App\Enums\PageType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Moves the policy and about pages of a database built before `pages`
 * existed. The renamed migration creates `pages` on such a database; this
 * fills it from the old `faq` table and drops that table.
 */
class MovePagesFromFaq extends Command
{
    private const OLD_TABLE = 'faq';

    private const OLD_MIGRATION = '2026_05_25_093443_create_faq_table';

    private const OLD_PERMISSION = 'Quản trị FAQ';

    private const NEW_PERMISSION = 'Quản trị Chính sách & FAQ';

    protected $signature = 'pages:move-from-faq';

    protected $description = 'Chuyển trang chính sách và trang giới thiệu từ bảng faq cũ sang bảng pages';

    public function handle(): int
    {
        if (! Schema::hasTable(self::OLD_TABLE)) {
            $this->info('Không còn bảng faq, không có gì để chuyển.');

            return self::SUCCESS;
        }

        if (DB::table('pages')->exists()) {
            $this->error('Bảng pages đã có dữ liệu. Kiểm tra lại trước khi chuyển để không ghi đè.');

            return self::FAILURE;
        }

        $moved = DB::transaction(function () {
            $rows = DB::table(self::OLD_TABLE)->orderBy('faq_id')->get()->map(fn (object $row) => [
                'page_id' => $row->faq_id,
                'page_type' => $row->faq_about ? PageType::About->value : PageType::Policy->value,
                'page_name' => $row->faq_name,
                'page_description' => $row->faq_description,
                'page_content' => $row->faq_content,
                'page_slug' => $row->faq_slug,
                'page_hidden' => $row->faq_hidden,
                'page_meta_keywords' => $row->faq_meta_keywords,
                'page_meta_description' => $row->faq_meta_description,
                'page_created_by' => $row->faq_created_by,
                'page_updated_by' => $row->faq_updated_by,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
                'deleted_at' => $row->deleted_at,
            ]);

            DB::table('pages')->insert($rows->all());

            // Renamed in place, so every role and admin that had it keeps it.
            if (Permission::where('name', self::NEW_PERMISSION)->exists()) {
                $this->warn('Quyền "'.self::NEW_PERMISSION.'" đã có sẵn, giữ nguyên quyền "'.self::OLD_PERMISSION.'" để bạn tự gộp.');
            } else {
                Permission::where('name', self::OLD_PERMISSION)->update(['name' => self::NEW_PERMISSION]);
            }
            DB::table('migrations')->where('migration', self::OLD_MIGRATION)->delete();

            return $rows->count();
        });

        // MySQL commits a DROP on its own, so it stays out of the transaction above.
        Schema::drop(self::OLD_TABLE);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        ResponseCache::clear();

        $this->info("Đã chuyển {$moved} trang sang bảng pages và xoá bảng faq.");

        return self::SUCCESS;
    }
}
