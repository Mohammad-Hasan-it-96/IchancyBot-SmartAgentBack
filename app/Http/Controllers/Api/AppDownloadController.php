<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppUpdateService;

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
}

