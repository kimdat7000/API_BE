<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FRONTEND API
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/settings
     * Lấy toàn bộ cấu hình website (frontend)
     */
    public function index()
    {
        return response()->json([
            'status' => true,
            'data' => Setting::pluck('value', 'key')
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN API
    |--------------------------------------------------------------------------
    */

    /**
     * POST /api/admin/settings
     * Thêm setting mới
     */
    public function store(Request $request)
    {
        $request->validate([
            'key' => 'required|string|max:255|unique:settings,key',
            'value' => 'required'
        ]);

        $setting = Setting::create([
            'key' => $request->key,
            'value' => $request->value
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Thêm setting thành công',
            'data' => $setting
        ], 201);
    }

    /**
     * PUT /api/admin/settings/{id}
     * Cập nhật setting
     */
    public function update(Request $request, $id)
    {
        $setting = Setting::find($id);

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting không tồn tại'
            ], 404);
        }

        $request->validate([
            'key' => 'required|string|max:255|unique:settings,key,' . $id,
            'value' => 'required'
        ]);

        $setting->update([
            'key' => $request->key,
            'value' => $request->value
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật setting thành công',
            'data' => $setting
        ]);
    }

    /**
     * DELETE /api/admin/settings/{id}
     * Xoá setting
     */
    public function destroy($id)
    {
        $setting = Setting::find($id);

        if (!$setting) {
            return response()->json([
                'status' => false,
                'message' => 'Setting không tồn tại'
            ], 404);
        }

        $setting->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xoá setting'
        ]);
    }
}
