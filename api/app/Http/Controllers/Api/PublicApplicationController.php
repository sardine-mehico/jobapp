<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\TrackingLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicApplicationController extends Controller
{
    public function show(string $code): JsonResponse
    {
        $trackingLink = TrackingLink::query()->with('job')->where('code', $code)->first();

        if (! $trackingLink || ! $trackingLink->job || ! $trackingLink->job->is_active) {
            return response()->json(['message' => 'Invalid link'], 404);
        }

        $trackingLink->increment('visit_count');

        return response()->json([
            'job_id' => $trackingLink->job->job_id,
            'positive_points' => $trackingLink->job->positive_points,
            'contact_email' => $trackingLink->job->contact_email,
        ]);
    }

    public function store(Request $request, string $code): JsonResponse
    {
        $trackingLink = TrackingLink::query()->with('job')->where('code', $code)->first();

        if (! $trackingLink || ! $trackingLink->job || ! $trackingLink->job->is_active) {
            return response()->json(['message' => 'Invalid link'], 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'suburb' => ['required', 'string', 'max:255'],
            'contact_no' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'availability' => ['required', 'string'],
            'visa_status' => ['required', 'in:student_visa,partner_visa,tr,australian_pr,australian_citizen,other'],
            'visa_other' => ['nullable', 'required_if:visa_status,other', 'string', 'max:255'],
            'reliable_transport' => ['required', 'boolean'],
            'driving_licence' => ['required', 'boolean'],
            'has_abn' => ['required', 'boolean'],
            'criminal_conviction' => ['required', 'boolean'],
            'police_clearance' => ['required', 'boolean'],
            'workers_comp' => ['required', 'boolean'],
            'education' => ['required', 'string'],
            'work_exp_1' => ['required', 'string'],
            'work_exp_2' => ['nullable', 'string'],
            'references' => ['required', 'string'],
        ]);

        $application = Application::query()->create([
            ...$data,
            'work_exp_2' => $data['work_exp_2'] ?? '',
            'job_id' => $trackingLink->job_id,
            'tracking_link_id' => $trackingLink->id,
            'submitted_at' => now(),
        ]);

        return response()->json([
            'id' => $application->id,
            'message' => 'Application submitted',
        ], 201);
    }
}
