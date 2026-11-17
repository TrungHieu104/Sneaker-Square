<?php

namespace App\Actions;

use App\Models\FaqGroupModel;
use App\Models\FaqModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Saves the order an admin dragged the FAQ groups, or the questions of one
 * group, into: positions become 1, 2, 3… so two rows never share a number.
 */
class ReorderFaqAction
{
    public const STALE = 'Danh sách đã thay đổi, hãy tải lại trang rồi kéo lại.';

    /**
     * @param  list<int>  $ids  every group, in the new order
     * @return bool false when the ids are not exactly the groups that exist
     */
    public function groups(array $ids): bool
    {
        return $this->renumber(FaqGroupModel::query(), 'group_id', 'group_position', $ids);
    }

    /**
     * @param  list<int>  $ids  every question of the group, in the new order
     * @return bool false when the ids are not exactly the group's questions
     */
    public function questions(FaqGroupModel $group, array $ids): bool
    {
        return $this->renumber(FaqModel::where('group_id', $group->group_id), 'faq_id', 'faq_position', $ids);
    }

    /**
     * A list that misses a row or names one from elsewhere was dragged on a
     * stale page, so nothing is saved.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $rows
     * @param  list<int>  $ids
     */
    private function renumber(Builder $rows, string $key, string $column, array $ids): bool
    {
        $stored = $rows->pluck($key)->map(fn ($id) => (int) $id)->sort()->values()->all();
        $sent = $ids;
        sort($sent);

        if ($stored !== $sent) {
            return false;
        }

        DB::transaction(function () use ($rows, $key, $column, $ids) {
            foreach ($ids as $index => $id) {
                (clone $rows)->where($key, $id)->update([$column => $index + 1]);
            }
        });

        // A query update fires no model events, so the page cache is emptied here.
        ResponseCache::clear();

        return true;
    }
}
