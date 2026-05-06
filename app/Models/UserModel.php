<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Carbon;

class UserModel extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;
    protected $table = 'users';
    protected $primaryKey = "user_id";

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'user_img',
        'email',
        'password',
        'user_phone',
        'user_role',
        'google_id',
        'facebook_id',
        'locked_at',
        'login_attempts'
    ];

    /** 
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function lockAccount()
    {
        $this->save([
            'locked_at' => now(),
            'login_attempts' => 0,
        ]);
    }
    protected $castss = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    static public function getUsersingle($email){
        return UserModel::where('email','=',$email)->first();
    }
    static public function getTokenSingle($remember_token){
        return UserModel::where('remember_token','=',$remember_token)->first();
    }
    public function Order()
    {
        return $this->hasMany(OrderModel::class,'user_id', 'user_id');
    }
    public function Delivery()
    {
        return $this->belongsTo(DeliveryInfoModel::class, 'info_id');
    }
    public function News()
    {
        return $this->hasMany(NewsModel::class,'user_id');
    }
}

