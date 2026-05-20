<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seuilsData' => 'nullable|array',
            'ratioSeuils' => 'nullable|array',
        ]);

        if ($request->has('seuilsData')) {
            Setting::updateOrCreate(
                ['key' => 'seuilsData'],
                ['value' => $validated['seuilsData']]
            );
        }

        if ($request->has('ratioSeuils')) {
            Setting::updateOrCreate(
                ['key' => 'ratioSeuils'],
                ['value' => $validated['ratioSeuils']]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Paramètres sauvegardés avec succès.',
        ]);
    }
}
