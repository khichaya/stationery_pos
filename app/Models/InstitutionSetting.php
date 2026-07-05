<?php

namespace App\Models;

use Illuminate\database\Eloquent\Model;

class InstitutionSetting extends Model
{
    protected $fillable = [
        'name', 'manager_name', 'phone', 'email', 'address',
        'nif', 'nis', 'rc', 'ai', 'invoice_footer', 'logo_path'
    ];
}