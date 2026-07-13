<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkTimeline extends Model
{
    protected $fillable = [
        'work_id',
        'step',
        'status',
        'message',
        'output',
    ];

    protected function casts(): array
    {
        return [
            'output' => 'array',
        ];
    }

    public function work()
    {
        return $this->belongsTo(Work::class);
    }
}
