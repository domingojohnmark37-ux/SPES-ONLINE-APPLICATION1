<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminBackupOperation extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'admin_id',
        'action',
        'status',
        'archive_path',
        'original_filename',
        'archive_type',
        'archive_year',
        'result',
        'failure_message',
    ];

    protected function casts(): array
    {
        return [
            'archive_year' => 'integer',
            'result' => 'array',
        ];
    }
}
