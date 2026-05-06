<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactFormModel extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'contact_forms';
    public $primaryKey = "id";
    public $timestamp = true;
    protected $dates = ['deleted_at'];
    protected $fillable = ['name', 'email', 'phone', 'title', 'status', 'content'];
}
