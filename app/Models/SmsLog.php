<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    const CREATED_AT = 'sent_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'recipient',
        'message',
        'sent_to_all',
    ];

    protected function casts(): array
    {
        return [
            'sent_to_all' => 'boolean',
        ];
    }
}
