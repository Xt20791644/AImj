<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'credits',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'credits' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function works()
    {
        return $this->hasMany(Work::class);
    }

    // 消费积分
    public function consumeCredits(int $amount, string $description, $reference = null): void
    {
        if ($this->credits < $amount) {
            throw new \Exception('积分不足');
        }

        $this->credits -= $amount;
        $this->save();

        $this->creditTransactions()->create([
            'amount' => -$amount,
            'balance_after' => $this->credits,
            'type' => 'consume',
            'description' => $description,
            'reference_type' => $reference ? get_class($reference) : null,
            'reference_id' => $reference ? $reference->id : null,
        ]);
    }

    // 充值积分
    public function rechargeCredits(int $amount, string $description = '管理员充值'): void
    {
        $this->credits += $amount;
        $this->save();

        $this->creditTransactions()->create([
            'amount' => $amount,
            'balance_after' => $this->credits,
            'type' => 'recharge',
            'description' => $description,
        ]);
    }
}
