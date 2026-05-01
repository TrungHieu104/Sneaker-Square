<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    protected function performSearch($model, $keyword, $searchableFields) {
        return $model->when($keyword, function ($query) use ($keyword, $searchableFields) {
            $query->where(function ($query) use ($keyword, $searchableFields) {
                foreach ($searchableFields as $field) {
                    $query->orWhere($field, 'like', "%$keyword%");
                }
            });
        });
    }    
}
