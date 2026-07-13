<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'style',
        'status',
        'progress',
        'status_text',
        'output_video',
        'output_cover',
        'duration',
        'views',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'progress' => 'integer',
            'duration' => 'integer',
            'views' => 'integer',
            'meta' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timelines()
    {
        return $this->hasMany(WorkTimeline::class)->orderBy('id');
    }
}
