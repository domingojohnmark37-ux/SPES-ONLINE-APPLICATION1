<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'filters_json',
        'archive_path',
        'generated_by',
    ];

    protected $casts = [
        'filters_json' => 'array',
    ];

    /**
     * Relationship with User
     */
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Many-to-many relationship with Applications
     */
    public function applications()
    {
        return $this->belongsToMany(Application::class, 'master_list_applications');
    }
}
