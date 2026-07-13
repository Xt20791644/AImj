<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount'); // 正数=充值，负数=消费
            $table->integer('balance_after'); // 操作后余额
            $table->string('type'); // recharge | consume | refund
            $table->string('description')->nullable();
            $table->nullableMorphs('reference'); // 关联的作品/订单等
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
