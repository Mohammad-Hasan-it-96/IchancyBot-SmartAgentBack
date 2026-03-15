<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

class AppHarfosh extends Model
{
    
    protected $table = "app_harfoshs";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'app_name','device_id','full_name','phone','is_verified','expires_at','plan_id','fcm_token'
    ];

}
