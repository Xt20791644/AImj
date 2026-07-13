<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KlingConfig;

class KlingController extends Controller
{
    /**
     * 获取所有可灵配置选项 (给前端下拉菜单用)
     */
    public function options()
    {
        return response()->json(KlingConfig::getOptions());
    }

    /**
     * 获取默认配置
     */
    public function defaults()
    {
        return response()->json(KlingConfig::defaults());
    }
}
