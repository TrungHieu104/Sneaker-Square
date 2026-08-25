<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills the `rating` column on databases created before it existed.
 *
 * The star-rating feature is fully implemented — the review form has a star
 * picker, CommentRequest validates `rating` between 1 and 5, CommentController
 * assigns it and CommentModel lists it as fillable. The column was later added
 * to create_comments_table, but no follow-up migration was written, so any
 * database migrated before that edit is still missing it and every review
 * submission fails with "Unknown column 'rating' in 'field list'".
 *
 * The guard makes this a no-op on databases that already have the column, so
 * fresh installs are unaffected.
 *
 * The column is nullable rather than defaulting to 5: reviews written before
 * ratings existed carry no score, and counting them as five stars would invent
 * data. ProductModel::getAverageRating() leaves nulls out of the average.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('comments', 'rating')) {
            return;
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedTinyInteger('rating')->nullable()->after('comment_content');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('comments', 'rating')) {
            return;
        }

        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('rating');
        });
    }
};
