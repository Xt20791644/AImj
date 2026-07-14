<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = [
        'work_id', 'name', 'role_type', 'gender', 'age',
        'description', 'appearance', 'personality', 'voice_tone',
        'image_url', 'ref_image_url', 'tags', 'sort',
    ];

    protected function casts(): array
    {
        return ['tags' => 'array', 'age' => 'integer', 'sort' => 'integer'];
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }
}
