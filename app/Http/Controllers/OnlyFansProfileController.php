<?php

namespace App\Http\Controllers;

use App\Jobs\ScrapeOnlyFansProfile;
use App\Models\OnlyFansProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnlyFansProfileController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $profiles = OnlyFansProfile::search($request->query)
            ->orderBy('likes_count', 'desc')
            ->get();

        return response()->json([
            'profiles' => $profiles,
        ]);
    }

    public function scrape(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|min:2',
        ]);

        ScrapeOnlyFansProfile::dispatch($request->username)
            ->onQueue('high-priority');

        return response()->json([
            'message' => 'Profile scraping job has been queued.',
        ]);
    }

    public function show(string $username): JsonResponse
    {
        $profile = OnlyFansProfile::where('username', $username)->firstOrFail();

        return response()->json([
            'profile' => $profile,
        ]);
    }
}