<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Schema;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function performSearch($model, $keyword, $searchableFields)
    {
        return $model->when($keyword, function ($query) use ($keyword, $searchableFields) {
            $query->where(function ($query) use ($keyword, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $query->orWhere($field, 'like', "%$keyword%");
                }
            });
        });
    }

    /**
     * The column and direction an admin listing is sorted by, read from its
     * `sort-by` and `sort-type` links.
     *
     * The direction comes back flipped: the page hands it straight to its
     * column headers, so clicking the same header again sorts the other way.
     * Only a real column of the listed table is accepted; anything else in
     * the URL falls back to the default instead of failing the query.
     *
     * @param  class-string<Model>  $model
     * @return array{0: string, 1: string}
     */
    protected function listingSort(Request $request, string $model, string $default, bool $flip = true, string $byKey = 'sort-by', string $typeKey = 'sort-type'): array
    {
        $column = (string) $request->input($byKey, $default);

        if (! Schema::hasColumn((new $model)->getTable(), $column)) {
            $column = $default;
        }

        $direction = $request->input($typeKey, $flip ? 'asc' : 'desc') === 'asc' ? 'asc' : 'desc';

        if ($flip) {
            $direction = $direction === 'asc' ? 'desc' : 'asc';
        }

        return [$column, $direction];
    }
}
