<?php

namespace App\Models;

use App\Models\Concerns\ClearsResponseCache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A heading on the FAQ page, such as "Giao hàng", that its questions sit under.
 */
class FaqGroupModel extends Model
{
    use ClearsResponseCache;

    protected $table = 'faq_groups';

    public $primaryKey = 'group_id';

    protected $fillable = ['group_name', 'group_position', 'group_hidden'];

    /** @return HasMany<FaqModel, $this> */
    public function faqs(): HasMany
    {
        return $this->hasMany(FaqModel::class, 'group_id', 'group_id');
    }

    /**
     * What the storefront FAQ page shows: shown groups in order, each with
     * its shown questions in order. A group left with none is not printed.
     *
     * @return Collection<int, FaqGroupModel>
     */
    public static function published(): Collection
    {
        return self::query()
            ->where('group_hidden', 1)
            ->with(['faqs' => fn ($faqs) => $faqs->where('faq_hidden', 1)->orderBy('faq_position')->orderBy('faq_id')])
            ->orderBy('group_position')
            ->orderBy('group_id')
            ->get()
            ->filter(fn (FaqGroupModel $group) => $group->faqs->isNotEmpty())
            ->values();
    }

    public static function nextPosition(): int
    {
        return (int) self::query()->max('group_position') + 1;
    }
}
