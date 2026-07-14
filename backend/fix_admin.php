<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::find(1);
if ($u) {
    $u->password = bcrypt('admin123');
    $u->save();
    echo "Admin password reset\n";
    // 加积分记录
    $u->rechargeCredits(200, '注册赠送积分');
    echo "Added test transaction\n";
} else {
    echo "User 1 not found\n";
}
