<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    protected $fillable = [
        'work_id', 'episode_number', 'title', 'script', 'summary',
        'status', 'duration', 'output_video', 'output_cover', 'sort',
    ];

    protected function casts(): array
    {
        return ['episode_number' => 'integer', 'duration' => 'integer', 'sort' => 'integer'];
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }

    public function storyboards()
    {
        return $this->hasMany(Storyboard::class)->orderBy('sort');
    }
}
