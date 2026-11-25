<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Empties an admin trash without tripping a restrict foreign key. A row that
 * another row still points at, even one sitting in its own trash, stays until
 * that row is gone: the soft-delete guards only ever counted live rows.
 *
 * $references maps each referencing table to its column holding our key.
 */
class TrashPurge
{
    /**
     * @param  array<string, string>  $references
     */
    public function isReferenced(Model $row, array $references): bool
    {
        foreach ($references as $table => $column) {
            if (DB::table($table)->where($column, $row->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Purges every trashed row nothing points at, children before their
     * parents, and returns how many had to stay.
     *
     * @param  Closure(): EloquentBuilder<Model>  $trashed  a fresh query over the trash
     * @param  array<string, string>  $references
     */
    public function purgeAll(Closure $trashed, array $references): int
    {
        do {
            $query = $trashed();
            $key = $query->getModel()->getQualifiedKeyName();

            foreach ($references as $table => $column) {
                $query->whereNotExists(fn (Builder $ref) => $ref->from($table.' as ref')->whereColumn('ref.'.$column, $key));
            }

            // Keys first: MySQL refuses a DELETE whose subquery reads the same table.
            $ids = $query->toBase()->pluck($key);
            $purged = $ids->isEmpty() ? 0 : $trashed()->toBase()->whereIn($key, $ids)->delete();
        } while ($purged > 0);

        return $trashed()->count();
    }
}
