<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Storyboard extends Model
{
    protected $fillable = [
        'episode_id', 'scene_number', 'shot_number', 'shot_type',
        'camera_movement', 'prompt', 'description', 'dialogue',
        'character_ids', 'duration', 'status', 'output_video',
        'output_image', 'config', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array', 'duration' => 'integer', 'sort' => 'integer',
            'scene_number' => 'integer', 'shot_number' => 'integer',
        ];
    }

    public function episode()
    {
        return $this->belongsTo(Episode::class);
    }
}
