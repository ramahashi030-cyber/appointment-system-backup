<?php

namespace App\Models;

use Database\Factories\AdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Administrator account used for the admin panel.
 *
 * Staff and doctor records remain in the separate staff table.
 */
class Admin extends Authenticatable
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory;

    protected $table = 'admin';

    protected $fillable = [
        'firstname',
        'lastname',
        'username',
        'password',
        'email',
        'contact_no',
        'role',
        'triage_channel',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
