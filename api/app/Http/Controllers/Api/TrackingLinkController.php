<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\TrackingLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrackingLinkController extends Controller
{
    public function store(Job $job): JsonResponse
    {
        $trackingLink = $job->trackingLinks()->create([
            'code' => $this->generateCode(),
        ]);

        return response()->json([
            'id' => $trackingLink->id,
            'job_id' => $trackingLink->job_id,
            'code' => $trackingLink->code,
            'label' => $trackingLink->label,
            'external_post_url' => $trackingLink->external_post_url,
            'visit_count' => $trackingLink->visit_count,
            'url' => rtrim((string) config('app.frontend_url', env('FRONTEND_URL')), '/').'/'.$trackingLink->code,
        ], 201);
    }

    public function update(Request $request, TrackingLink $trackingLink): JsonResponse
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'external_post_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $trackingLink->update($data);

        return response()->json([
            'id' => $trackingLink->id,
            'job_id' => $trackingLink->job_id,
            'code' => $trackingLink->code,
            'label' => $trackingLink->label,
            'external_post_url' => $trackingLink->external_post_url,
            'visit_count' => $trackingLink->visit_count,
            'url' => rtrim((string) config('app.frontend_url', env('FRONTEND_URL')), '/').'/'.$trackingLink->code,
        ]);
    }

    public function destroy(TrackingLink $trackingLink): JsonResponse
    {
        $trackingLink->delete();

        return response()->json(['message' => 'Tracking link deleted']);
    }

    private function generateCode(): string
    {
        do {
            $code = preg_replace('/[^a-z0-9]/', 'a', Str::lower(Str::random(6)));
        } while (TrackingLink::query()->where('code', $code)->exists());

        return $code;
    }
}
