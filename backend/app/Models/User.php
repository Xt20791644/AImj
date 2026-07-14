<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    protected $fillable = ['name','email','password','role','credits'];
    protected $hidden = ['password','remember_token'];
    protected function casts(): array { return ['password'=>'hashed','credits'=>'integer']; }
    public function works() { return $this->hasMany(Work::class); }
    public function creditTransactions() { return $this->hasMany(CreditTransaction::class)->latest(); }

    public function consumeCredits(int $amount, string $desc, $ref=null) {
        $this->credits -= $amount; $this->save();
        $this->creditTransactions()->create(['amount'=>-$amount,'balance_after'=>$this->credits,'type'=>'consume','description'=>$desc,'reference_type'=>$ref?get_class($ref):null,'reference_id'=>$ref?$ref->id:null]);
    }
    public function rechargeCredits(int $amount, string $desc='充值') {
        $this->credits += $amount; $this->save();
        $this->creditTransactions()->create(['amount'=>$amount,'balance_after'=>$this->credits,'type'=>'recharge','description'=>$desc]);
    }
}
