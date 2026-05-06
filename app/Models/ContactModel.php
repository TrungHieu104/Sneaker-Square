<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ContactModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table ="contact"; 
    public $primaryKey = "contact_id";
    protected $dates = ['deleted_at'];
    public $timestamps = true;  
    protected $fillable = ['contact_email', 'contact_phone', 'contact_address', 'map_link', 'fanpage_link', 'contact_hidden','contact_created_by','contact_updated_by'];
}