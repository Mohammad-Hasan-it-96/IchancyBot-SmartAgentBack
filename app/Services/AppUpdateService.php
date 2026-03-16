<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AppUpdateService
{
    /**
     * The Google Drive JSON config file ID.
     * Sharing URL: https://drive.google.com/open?id=1aMv_VNEFff1XzQeiG80s0aEL4r5_c9ao
     */
    protected string $fileId = '1aMv_VNEFff1XzQeiG80s0aEL4r5_c9ao';

    /**
     * Cache duration in seconds (5 minutes).
     */
    protected int $cacheTtl = 300;

    /**
     * Fetch and return the app config JSON from Google Drive.
     * Returns null if the fetch fails.
     */
    public function fetchConfig(): ?array
    {
        $cacheKey = 'smart_agent_app_config';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            return $this->fetchFromDrive();
        });
    }

    /**
     * Perform the actual HTTP request to Google Drive.
     */
    private function fetchFromDrive(): ?array
    {
        // Direct download URL for Google Drive files (bypasses preview page)
        $url = "https://drive.google.com/uc?export=download&id={$this->fileId}";

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'SmartAgentLandingPage/1.0',
                ])
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (is_array($data)) {
                    return $data;
                }
            }

            Log::warning('AppUpdateService: Failed to fetch config. Status: ' . $response->status());
            return null;

        } catch (\Exception $e) {
            Log::error('AppUpdateService: Exception while fetching config: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Force-refresh the cached config.
     */
    public function refreshConfig(): ?array
    {
        Cache::forget('smart_agent_app_config');
        return $this->fetchConfig();
    }
}

