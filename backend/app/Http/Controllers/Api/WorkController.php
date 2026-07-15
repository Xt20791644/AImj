<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WorkController extends Controller
{
    private function ensureVisibleColumn(): void
    {
        if (Schema::hasTable('works') && !Schema::hasColumn('works', 'visible')) {
            Schema::table('works', fn($t) => $t->boolean('visible')->default(true)->after('duration'));
        }
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('works')) {
            Schema::create('works', function($t) {
                $t->id(); $t->foreignId('user_id')->nullable()->constrained('users');
                $t->string('title'); $t->text('content');
                $t->string('style')->default('realistic'); $t->string('status')->default('pending');
                $t->integer('progress')->default(0); $t->text('status_text')->nullable();
                $t->text('output_video')->nullable(); $t->text('output_cover')->nullable();
                $t->integer('duration')->default(0); $t->boolean('visible')->default(true); $t->json('meta')->nullable(); $t->timestamps();
            });
        }

        $user = $request->user();

        $work = Work::create([
            'user_id' => $user ? $user->id : 1,
            'title' => $request->title ?? 'AI创作',
            'content' => $request->content ?? '',
            'style' => $request->style ?? 'realistic',
            'status' => 'pending',
            'meta' => ['kling_config' => $request->kling_config ?? []],
        ]);

        // 扣积分 + 记录
        if ($user) {
            $cost = 20;
            $user->consumeCredits($cost, "创作《{$work->title}》", $work);
        }

        return response()->json($work, 201);
    }

    public function index() {
        $this->ensureVisibleColumn();
        return response()->json(Work::where('visible', true)->latest()->get());
    }

    public function destroy($id) {
        $this->ensureVisibleColumn();
        $work = Work::findOrFail($id);
        $work->update(['visible' => false]);
        return response()->json(['message' => '已删除']);
    }
}
