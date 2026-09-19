<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteService extends Model
{
    protected $table = 'favorites_services';

    protected $fillable = ['user_id',  'service_type', 'price', 'quantity'];
}