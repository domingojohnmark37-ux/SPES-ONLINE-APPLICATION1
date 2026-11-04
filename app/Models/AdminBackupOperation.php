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
        'file_size_bytes',
        'duration_ms',
        'started_at',
        'completed_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'archive_year' => 'integer',
            'file_size_bytes' => 'integer',
            'duration_ms' => 'integer',
            'result' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
