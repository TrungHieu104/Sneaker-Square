<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class MenuModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table ="menu"; 
    public $primaryKey = "menu_id";
    protected $dates = ['deleted_at'];
    public $timestamps = true;  
    protected $fillable = ['menu_name','menu_link','menu_hidden','menu_position','menu_parent_id'];
    public function children()
    {
        return $this->hasMany(MenuModel::class, 'menu_parent_id');
    }
}
