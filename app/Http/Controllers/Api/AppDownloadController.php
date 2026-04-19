<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppHarfosh;
use App\Services\AppUpdateService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppDownloadController extends Controller
{
    public function __construct(protected AppUpdateService $updateService)
    {
    }

    /**
     * Show the app download landing page.
     */
    public function index()
    {
        $config = $this->updateService->fetchConfig();

        $latestVersion = $config['latest_version'] ?? null;
        $downloads     = $config['downloads']       ?? [];
        $updateNotes   = $config['update_notes']    ?? [];

        $download64 = $downloads['arm64-v8a']   ?? null;
        $download32 = $downloads['armeabi-v7a'] ?? null;

        // Use 64-bit as the primary / default download link
        $primaryDownload = $download64 ?? $download32 ?? null;

        $fetchFailed = ($config === null);

        return view('app-download', compact(
            'latestVersion',
            'download64',
            'download32',
            'primaryDownload',
            'updateNotes',
            'fetchFailed'
        ));
    }

    public function add_review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'app_name'  => 'required|string|max:255',
            'stars'     => 'required|integer|min:1|max:5',
            'comment'   => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        $app = AppHarfosh::where('device_id', $request->device_id)
            ->where('app_name', $request->app_name)
            ->first();

        if (!$app) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }
        $app->update([
            'stars'   => $request->stars,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success'     => true,
        ], 200);
    }
}

