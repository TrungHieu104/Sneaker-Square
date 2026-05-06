<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class FaqModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table ="faq"; 
    public $primaryKey = "faq_id";
    public $timestamps = true;  
    protected $fillable = ['faq_name', 'faq_description', 'faq_content', 'faq_hidden','faq_slug','faq_about','faq_meta_keywords','faq_meta_description','faq_created_by','faq_updated_by'];
}

