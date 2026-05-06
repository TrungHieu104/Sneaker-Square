<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorModel extends Model
{
    use HasFactory;
    protected $table = "visitors";
    protected $primaryKey = "visitor_id";
    public $timestamps = true;
    protected $fillable = [
        'visitor_ip',
        'visitor_date',
    ];
}
