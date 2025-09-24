<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailCampaignLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient',
        'success',
        'error_message',
    ];
}
