<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function publicIndex(): JsonResponse
    {
        $jobs = Job::query()
            ->where('is_active', true)
            ->with('trackingLinks')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Job $job): array {
                return $this->transformPublicJob($job);
            })
            ->filter(fn (array $job): bool => filled($job['apply_url']))
            ->values();

        return response()->json($jobs);
    }

    public function publicShow(Job $job): JsonResponse
    {
        $job->load('trackingLinks');

        if (! $job->is_active || ! $job->trackingLinks->first()) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        return response()->json($this->transformPublicJob($job) + [
            'advertisement' => $job->advertisement,
        ]);
    }

    public function index(): JsonResponse
    {
        $search = trim((string) request()->query('search', ''));
        $searchPattern = '%'.$search.'%';

        $jobs = Job::query()
            ->withCount('applications')
            ->with('trackingLinks')
            ->when($search !== '', function ($query) use ($searchPattern): void {
                $query->where(function ($searchQuery) use ($searchPattern): void {
                    $searchQuery
                        ->where('job_id', 'ilike', $searchPattern)
                        ->orWhere('advertisement', 'ilike', $searchPattern)
                        ->orWhere('positive_points', 'ilike', $searchPattern)
                        ->orWhere('contact_email', 'ilike', $searchPattern)
                        ->orWhereHas('trackingLinks', function ($trackingQuery) use ($searchPattern): void {
                            $trackingQuery
                                ->where('code', 'ilike', $searchPattern)
                                ->orWhere('label', 'ilike', $searchPattern)
                                ->orWhere('external_post_url', 'ilike', $searchPattern);
                        })
                        ->orWhereHas('applications', function ($applicationQuery) use ($searchPattern): void {
                            $applicationQuery
                                ->where('name', 'ilike', $searchPattern)
                                ->orWhere('email', 'ilike', $searchPattern)
                                ->orWhere('suburb', 'ilike', $searchPattern);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Job $job) => $this->transformJob($job));

        return response()->json($jobs);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'advertisement' => ['required', 'string'],
            'positive_points' => ['nullable', 'string'],
            'contact_email' => ['required', 'email'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $job = Job::query()->create([
            ...$data,
            'positive_points' => $data['positive_points'] ?? '<p></p>',
            'is_active' => $data['is_active'] ?? true,
            'job_id' => $this->nextJobId(),
        ]);

        $job->loadCount('applications')->load('trackingLinks');

        return response()->json($this->transformJob($job), 201);
    }

    public function show(Job $job): JsonResponse
    {
        $job->loadCount('applications')->load('trackingLinks');

        return response()->json($this->transformJob($job));
    }

    public function update(Request $request, Job $job): JsonResponse
    {
        $data = $request->validate([
            'advertisement' => ['required', 'string'],
            'positive_points' => ['nullable', 'string'],
            'contact_email' => ['required', 'email'],
            'is_active' => ['required', 'boolean'],
        ]);

        $job->update([
            ...$data,
            'positive_points' => $data['positive_points'] ?? $job->positive_points ?? '<p></p>',
        ]);
        $job->loadCount('applications')->load('trackingLinks');

        return response()->json($this->transformJob($job));
    }

    public function destroy(Job $job): JsonResponse
    {
        $job->delete();

        return response()->json(['message' => 'Job deleted']);
    }

    private function nextJobId(): string
    {
        $last = Job::query()->select('job_id')->latest('created_at')->value('job_id');
        $number = $last ? ((int) substr($last, 4)) + 1 : 1;

        return sprintf('CLN-%03d', $number);
    }

    private function transformJob(Job $job): array
    {
        $rankingCounts = $job->applications()
            ->selectRaw('employer_ranking, COUNT(*) as aggregate')
            ->whereIn('employer_ranking', [1, 2, 3])
            ->groupBy('employer_ranking')
            ->pluck('aggregate', 'employer_ranking');

        return [
            'id' => $job->id,
            'job_id' => $job->job_id,
            'created_at' => $job->created_at,
            'advertisement' => $job->advertisement,
            'positive_points' => $job->positive_points,
            'contact_email' => $job->contact_email,
            'is_active' => (bool) $job->is_active,
            'applications_count' => $job->applications_count ?? $job->applications()->count(),
            'ranking_counts' => [
                '1' => (int) ($rankingCounts[1] ?? 0),
                '2' => (int) ($rankingCounts[2] ?? 0),
                '3' => (int) ($rankingCounts[3] ?? 0),
            ],
            'tracking_links' => $job->trackingLinks->map(fn ($link) => [
                'id' => $link->id,
                'job_id' => $link->job_id,
                'code' => $link->code,
                'label' => $link->label,
                'external_post_url' => $link->external_post_url,
                'visit_count' => $link->visit_count,
                'url' => rtrim((string) config('app.frontend_url', env('FRONTEND_URL')), '/').'/'.$link->code,
                'created_at' => $link->created_at,
                'updated_at' => $link->updated_at,
            ])->values(),
        ];
    }

    private function transformPublicJob(Job $job): array
    {
        $firstLink = $job->trackingLinks->first();

        return [
            'id' => $job->id,
            'job_id' => $job->job_id,
            'summary_line' => $this->firstAdvertisementLine($job->advertisement),
            'preview_text' => $this->advertisementPreview($job->advertisement, 4),
            'apply_url' => $firstLink
                ? rtrim((string) config('app.frontend_url', env('FRONTEND_URL')), '/').'/'.$firstLink->code
                : null,
        ];
    }

    private function firstAdvertisementLine(string $advertisement): string
    {
        $lines = $this->advertisementLines($advertisement);

        foreach ($lines as $line) {
            if ($line !== '') {
                return $line;
            }
        }

        return '';
    }

    private function advertisementPreview(string $advertisement, int $maxLines): string
    {
        return implode("\n", array_slice($this->advertisementLines($advertisement), 0, $maxLines));
    }

    private function advertisementLines(string $advertisement): array
    {
        $text = preg_replace('/<(br|\/p|\/div|\/li|\/h[1-6])[^>]*>/i', "\n", $advertisement) ?? $advertisement;
        $text = trim(html_entity_decode(strip_tags($text)));

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text) ?: [])));
    }
}
