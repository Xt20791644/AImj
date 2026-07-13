<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\KlingService::class);

try {
    $result = $service->generateImage('一只可爱的橘猫坐在窗台上，阳光洒在它身上，真人写实风格', [
        'n' => 1,
        'size' => '1024x1024',
    ]);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
