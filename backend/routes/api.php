<?php

use Illuminate\Support\Facades\Route;

Route::post('/video/reference', [App\Http\Controllers\Api\VideoController::class, 'uploadReference']);

Route::get('/', function () {
    return response()->json(['name' => 'AI短剧 API', 'version' => '2.0']);
});
