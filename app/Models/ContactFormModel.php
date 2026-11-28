<?php

namespace App\Models;

use App\Events\AdminNoticesChanged;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactFormModel extends Model
{
    use HasFactory,SoftDeletes;

    public const PENDING = 0;

    public const PROCESSING = 1;

    public const HANDLED = 2;

    protected $table = 'contact_forms';

    public $primaryKey = 'id';

    public $timestamp = true;

    protected $dates = ['deleted_at'];

    protected $fillable = ['name', 'email', 'phone', 'title', 'status', 'content'];

    protected static function booted(): void
    {
        static::created(fn () => AdminNoticesChanged::announce());
        static::updated(fn () => AdminNoticesChanged::announce());
        static::deleted(fn () => AdminNoticesChanged::announce());
        static::restored(fn () => AdminNoticesChanged::announce());
    }
}
