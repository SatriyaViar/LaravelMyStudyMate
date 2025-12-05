<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'material_type',
        'material_content',
        'material_file_url',
        'material_file_name',
        'material_file_type',
        'material_file_size',
    ];

    protected $casts = [
        'material_file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class);
    }

    // Accessor: Get file size in human readable format
    public function getFileSizeFormattedAttribute()
    {
        if (!$this->material_file_size) return null;
        
        $bytes = $this->material_file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Check if material is file type
    public function isFileType()
    {
        return $this->material_type === 'file';
    }

    // Check if material is text type
    public function isTextType()
    {
        return $this->material_type === 'text';
    }
}