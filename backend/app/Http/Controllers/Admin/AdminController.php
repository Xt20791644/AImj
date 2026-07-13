<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function stats()
    {
        return response()->json([
            'users' => User::count(),
            'works' => Work::count(),
            'credits' => User::sum('credits'),
            'revenue' => CreditTransaction::where('type', 'recharge')->sum('amount'),
        ]);
    }

    public function users(Request $request)
    {
        $users = User::latest()->paginate(20);
        return response()->json($users);
    }

    public function recharge(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:100000',
            'note' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($id);
        $user->rechargeCredits($request->amount, $request->note ?? '管理员充值');

        return response()->json([
            'message' => "已为用户 {$user->name} 充值 {$request->amount} 积分",
            'user' => $user->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => '用户已删除']);
    }

    public function transactions(Request $request)
    {
        $transactions = CreditTransaction::with('user:id,name,email')
            ->latest()
            ->paginate(30);

        return response()->json($transactions);
    }

    /**
     * 获取指定用户的所有作品
     */
    public function userWorks($id)
    {
        $user = User::findOrFail($id);
        $works = Work::where('user_id', $id)->with('timelines')->latest()->get();

        return response()->json([
            'user' => $user->only(['id', 'name', 'email']),
            'works' => $works,
        ]);
    }

    /**
     * 管理员重置用户密码
     */
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|max:255',
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => "用户 {$user->name} 的密码已重置"]);
    }
}
