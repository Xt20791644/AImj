<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WorkController extends Controller
{
    public function store(Request $request)
    {
        // 确保表存在
        if (!Schema::hasTable('works')) {
            Schema::create('works', function($t) {
                $t->id(); $t->foreignId('user_id')->nullable()->constrained('users');
                $t->string('title'); $t->text('content');
                $t->string('style')->default('realistic'); $t->string('status')->default('pending');
                $t->integer('progress')->default(0); $t->text('status_text')->nullable();
                $t->text('output_video')->nullable(); $t->text('output_cover')->nullable();
                $t->integer('duration')->default(0); $t->json('meta')->nullable(); $t->timestamps();
            });
        }

        $work = Work::create([
            'user_id' => 1,
            'title' => $request->title ?? 'AI创作',
            'content' => $request->content ?? '',
            'style' => $request->style ?? 'realistic',
            'status' => 'pending',
            'meta' => ['kling_config' => $request->kling_config ?? []],
        ]);

        return response()->json($work, 201);
    }
}
