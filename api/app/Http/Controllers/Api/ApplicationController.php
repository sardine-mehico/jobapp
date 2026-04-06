<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string'],
            'suburb' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'job_id' => ['nullable', 'string'],
            'ranking' => ['nullable', 'integer', 'between:1,6'],
            'sort' => ['nullable', 'in:submitted_at,name,suburb,employer_ranking'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $sort = $validated['sort'] ?? 'submitted_at';
        $direction = $validated['direction'] ?? 'desc';
        $search = trim((string) ($validated['search'] ?? ''));
        $searchPattern = '%'.$search.'%';
        $booleanSearch = match (strtolower($search)) {
            'yes', 'true' => true,
            'no', 'false' => false,
            default => null,
        };

        $applications = Application::query()
            ->with(['job:id,job_id', 'trackingLink:id,code,label'])
            ->when($search !== '', function (Builder $query) use ($searchPattern, $booleanSearch, $search): void {
                $query->where(function (Builder $searchQuery) use ($searchPattern, $booleanSearch, $search): void {
                    $searchQuery
                        ->where('name', 'ilike', $searchPattern)
                        ->orWhere('suburb', 'ilike', $searchPattern)
                        ->orWhere('contact_no', 'ilike', $searchPattern)
                        ->orWhere('email', 'ilike', $searchPattern)
                        ->orWhere('availability', 'ilike', $searchPattern)
                        ->orWhere('visa_status', 'ilike', $searchPattern)
                        ->orWhere('visa_other', 'ilike', $searchPattern)
                        ->orWhere('education', 'ilike', $searchPattern)
                        ->orWhere('work_exp_1', 'ilike', $searchPattern)
                        ->orWhere('work_exp_2', 'ilike', $searchPattern)
                        ->orWhere('references', 'ilike', $searchPattern)
                        ->orWhere('employer_notes', 'ilike', $searchPattern)
                        ->orWhereRaw('CAST(employer_ranking AS TEXT) ILIKE ?', [$searchPattern])
                        ->orWhereHas('job', fn (Builder $jobQuery) => $jobQuery->where('job_id', 'ilike', $searchPattern))
                        ->orWhereHas('trackingLink', function (Builder $trackingQuery) use ($searchPattern): void {
                            $trackingQuery
                                ->where('code', 'ilike', $searchPattern)
                                ->orWhere('label', 'ilike', $searchPattern);
                        });

                    if ($booleanSearch !== null) {
                        $searchQuery
                            ->orWhere('reliable_transport', $booleanSearch)
                            ->orWhere('driving_licence', $booleanSearch)
                            ->orWhere('has_abn', $booleanSearch)
                            ->orWhere('criminal_conviction', $booleanSearch)
                            ->orWhere('police_clearance', $booleanSearch)
                            ->orWhere('workers_comp', $booleanSearch);
                    }

                    if (is_numeric($search)) {
                        $searchQuery->orWhere('employer_ranking', (int) $search);
                    }
                });
            })
            ->when($validated['suburb'] ?? null, fn (Builder $query, string $suburb) => $query->where('suburb', 'like', "%{$suburb}%"))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('submitted_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('submitted_at', '<=', $date))
            ->when($validated['job_id'] ?? null, fn (Builder $query, string $jobId) => $query->where('job_id', $jobId))
            ->when($validated['ranking'] ?? null, fn (Builder $query, int $ranking) => $query->where('employer_ranking', $ranking))
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->through(fn (Application $application) => $this->transformApplication($application));

        return response()->json($applications);
    }

    public function show(Application $application): JsonResponse
    {
        $application->load(['job:id,job_id', 'trackingLink:id,code,label']);

        return response()->json($this->transformApplication($application));
    }

    public function update(Request $request, Application $application): JsonResponse
    {
        $data = Validator::make(
            $request->all(),
            $this->applicationRules(includeEmployerFields: true, partial: true)
        )->validate();

        $effectiveVisaStatus = $data['visa_status'] ?? $application->visa_status;
        $effectiveVisaOther = array_key_exists('visa_other', $data)
            ? $data['visa_other']
            : $application->visa_other;

        if ($effectiveVisaStatus === 'other' && blank($effectiveVisaOther)) {
            Validator::make([], [])->errors()->add('visa_other', 'The visa other field is required when visa status is other.')->throwResponse();
        }

        if ($effectiveVisaStatus !== 'other') {
            $data['visa_other'] = null;
        }

        $application->update($data);
        $application->load(['job:id,job_id', 'trackingLink:id,code,label']);

        return response()->json($this->transformApplication($application));
    }

    public function exportPdf(Application $application): StreamedResponse
    {
        $application->load(['job:id,job_id', 'trackingLink:id,code,label']);

        $pdf = Pdf::loadView('pdf.application', [
            'application' => $application,
        ])->setPaper('a4');

        return response()->streamDownload(
            static fn () => print($pdf->output()),
            sprintf('%s Job Application.pdf', $application->name),
            ['Content-Type' => 'application/pdf']
        );
    }

    private function transformApplication(Application $application): array
    {
        return [
            'id' => $application->id,
            'job_id' => $application->job_id,
            'tracking_link_id' => $application->tracking_link_id,
            'job' => $application->job ? [
                'id' => $application->job->id,
                'job_id' => $application->job->job_id,
            ] : null,
            'tracking_link' => $application->trackingLink ? [
                'id' => $application->trackingLink->id,
                'code' => $application->trackingLink->code,
                'label' => $application->trackingLink->label,
            ] : null,
            'name' => $application->name,
            'suburb' => $application->suburb,
            'contact_no' => $application->contact_no,
            'email' => $application->email,
            'availability' => $application->availability,
            'visa_status' => $application->visa_status,
            'visa_other' => $application->visa_other,
            'reliable_transport' => $application->reliable_transport,
            'driving_licence' => $application->driving_licence,
            'has_abn' => $application->has_abn,
            'criminal_conviction' => $application->criminal_conviction,
            'police_clearance' => $application->police_clearance,
            'workers_comp' => $application->workers_comp,
            'education' => $application->education,
            'work_exp_1' => $application->work_exp_1,
            'work_exp_2' => $application->work_exp_2,
            'references' => $application->references,
            'employer_ranking' => $application->employer_ranking,
            'employer_notes' => $application->employer_notes,
            'submitted_at' => $application->submitted_at,
            'created_at' => $application->created_at,
            'updated_at' => $application->updated_at,
        ];
    }

    private function applicationRules(bool $includeEmployerFields = false, bool $partial = false): array
    {
        $presence = $partial ? ['sometimes'] : ['required'];

        $rules = [
            'name' => [...$presence, 'string', 'max:255'],
            'suburb' => [...$presence, 'string', 'max:255'],
            'contact_no' => [...$presence, 'string', 'max:255'],
            'email' => [...$presence, 'email', 'max:255'],
            'availability' => [...$presence, 'string'],
            'visa_status' => [...$presence, 'in:student_visa,partner_visa,tr,australian_pr,australian_citizen,other'],
            'visa_other' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:255'],
            'reliable_transport' => [...$presence, 'boolean'],
            'driving_licence' => [...$presence, 'boolean'],
            'has_abn' => [...$presence, 'boolean'],
            'criminal_conviction' => [...$presence, 'boolean'],
            'police_clearance' => [...$presence, 'boolean'],
            'workers_comp' => [...$presence, 'boolean'],
            'education' => [...$presence, 'string'],
            'work_exp_1' => [...$presence, 'string'],
            'work_exp_2' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'string'],
            'references' => [...$presence, 'string'],
        ];

        if ($includeEmployerFields) {
            $rules['employer_ranking'] = ['nullable', 'integer', 'between:1,6'];
            $rules['employer_notes'] = ['nullable', 'string'];
        }

        return $rules;
    }
}
