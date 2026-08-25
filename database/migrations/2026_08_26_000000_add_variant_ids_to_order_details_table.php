<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records which size and colour an order line was for, by id.
 *
 * The line only stored the size and colour as display text, which is enough to
 * print an invoice but not to find the matching row in `products_quantity`.
 * Cancelling a payment therefore had no reliable way to put the stock back,
 * and fell back on reading the session cart — which by then had already been
 * cleared.
 *
 * Existing rows are backfilled from the text columns. Both `size.size` and
 * `color.color_vn` are unique, so the mapping is unambiguous.
 *
 * No foreign keys: SQLite cannot add a constraint to an existing table, and
 * the test suite runs on SQLite. Plain indexed columns are enough for the
 * lookups this supports.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('order_details', 'size_id')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->unsignedInteger('size_id')->nullable()->after('size')->index();
            });
        }

        if (! Schema::hasColumn('order_details', 'color_id')) {
            Schema::table('order_details', function (Blueprint $table) {
                $table->unsignedInteger('color_id')->nullable()->after('color')->index();
            });
        }

        $this->backfill('size', 'size_id', 'size', 'size');
        $this->backfill('color', 'color_id', 'color_vn', 'color');
    }

    public function down(): void
    {
        foreach (['size_id', 'color_id'] as $column) {
            if (Schema::hasColumn('order_details', $column)) {
                Schema::table('order_details', function (Blueprint $table) use ($column) {
                    $table->dropIndex([$column]);
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Fills an id column by matching the text the order line already stored.
     *
     * Done one lookup row at a time rather than as a joined UPDATE so it runs
     * identically on MySQL and SQLite. The `size` and `color` tables hold a few
     * dozen rows at most.
     */
    private function backfill(string $lookupTable, string $idColumn, string $labelColumn, string $orderColumn): void
    {
        $primaryKey = $lookupTable . '_id';

        foreach (DB::table($lookupTable)->get() as $row) {
            DB::table('order_details')
                ->whereNull($idColumn)
                ->where($orderColumn, $row->{$labelColumn})
                ->update([$idColumn => $row->{$primaryKey}]);
        }
    }
};
