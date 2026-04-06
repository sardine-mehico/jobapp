<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Job;
use App\Models\TrackingLink;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        Application::query()->delete();
        TrackingLink::query()->delete();
        Job::query()->delete();

        $suburbs = [
            'Perth',
            'Joondalup',
            'Midland',
            'Cannington',
            'Fremantle',
            'Baldivis',
            'Morley',
            'Victoria Park',
            'Parkwood',
            'Belmont',
        ];

        $visaStatuses = [
            'australian_citizen',
            'permanent_resident',
            'student_visa',
            'work_visa',
            'other',
        ];

        $availability = [
            'Weekdays 6am to 2pm',
            'Weekdays after 5pm',
            'Weekends only',
            'Monday to Friday all day',
            'Tuesday, Thursday and Saturday mornings',
        ];

        $educations = [
            '2025 Diploma in Human Resources',
            '2024 Certificate III in Cleaning Operations',
            '2023 Certificate II in Hospitality',
            '2022 TAFE Certificate in Business',
            '2021 High School Graduation',
        ];

        $jobTemplates = [
            'Office Cleaner - Morning Shift',
            'School Cleaner - Afternoon Shift',
            'Commercial Cleaner - Night Shift',
            'Warehouse Cleaner - Flexible Hours',
            'Medical Centre Cleaner - Weekend Shift',
        ];

        $firstNames = [
            'Anthony', 'Sarah', 'Michael', 'Emily', 'Jason', 'Rebecca', 'Daniel', 'Chloe', 'Sam', 'Jasmine',
            'Luke', 'Alicia', 'Matthew', 'Isabella', 'Nathan', 'Olivia', 'Ryan', 'Grace', 'Tyler', 'Mia',
        ];

        $lastNames = [
            'Smith', 'Brown', 'Jones', 'Taylor', 'Wilson', 'Thomas', 'White', 'Martin', 'Anderson', 'Walker',
        ];

        $jobs = collect($jobTemplates)->map(function (string $title, int $index) {
            $jobNumber = $index + 1;

            $job = Job::query()->create([
                'job_id' => sprintf('CLN-%03d', $jobNumber),
                'advertisement' => sprintf(
                    '<h2>%s</h2><p>We are seeking a reliable cleaner to join our team for site %d.</p><ul><li>Immediate start available</li><li>Supportive team environment</li><li>Ongoing shifts for the right applicant</li></ul>',
                    $title,
                    $jobNumber
                ),
                'positive_points' => '<ul><li>Attention to detail</li><li>Reliable attendance</li><li>Customer-friendly attitude</li></ul>',
                'contact_email' => sprintf('jobs%d@officepc.online', $jobNumber),
            ]);

            collect(range(1, 3))->each(function (int $linkIndex) use ($job, $jobNumber): void {
                TrackingLink::query()->create([
                    'job_id' => $job->id,
                    'code' => strtolower(substr(md5($job->job_id.'-'.$linkIndex), 0, 6)),
                    'label' => sprintf('Source %d', $linkIndex),
                    'external_post_url' => sprintf('https://example.com/jobs/%s/source-%d', strtolower($job->job_id), $linkIndex),
                    'visit_count' => $jobNumber * $linkIndex * 3,
                ]);
            });

            return $job->fresh('trackingLinks');
        });

        collect(range(1, 50))->each(function (int $index) use (
            $jobs,
            $suburbs,
            $visaStatuses,
            $availability,
            $educations,
            $firstNames,
            $lastNames
        ): void {
            $job = $jobs[($index - 1) % $jobs->count()];
            $trackingLink = $job->trackingLinks[($index - 1) % $job->trackingLinks->count()];
            $firstName = $firstNames[($index - 1) % count($firstNames)];
            $lastName = $lastNames[(int) floor(($index - 1) / 2) % count($lastNames)];
            $name = sprintf('%s %s', $firstName, $lastName);
            $visaStatus = $visaStatuses[($index - 1) % count($visaStatuses)];

            Application::query()->create([
                'job_id' => $job->id,
                'tracking_link_id' => $trackingLink->id,
                'name' => $name,
                'suburb' => $suburbs[($index - 1) % count($suburbs)],
                'contact_no' => sprintf('04%08d', 100000 + $index),
                'email' => sprintf('applicant%02d@example.com', $index),
                'availability' => $availability[($index - 1) % count($availability)],
                'visa_status' => $visaStatus,
                'visa_other' => $visaStatus === 'other' ? 'Bridging visa with work rights' : null,
                'reliable_transport' => $index % 4 !== 0,
                'driving_licence' => $index % 4 !== 0,
                'has_abn' => $index % 3 === 0,
                'criminal_conviction' => $index % 11 === 0,
                'police_clearance' => $index % 5 !== 0,
                'workers_comp' => $index % 9 === 0,
                'education' => $educations[($index - 1) % count($educations)],
                'work_exp_1' => sprintf('June 2025 to March 2025, %s Cleaning, Duties performed', ['Sparkle', 'Northside', 'Prime', 'Everfresh', 'Metro'][($index - 1) % 5]),
                'work_exp_2' => sprintf('January 2024 to May 2025, %s Services, Duties performed', ['Allied', 'Bluewave', 'Rapid', 'Coastal', 'Summit'][($index - 1) % 5]),
                'references' => sprintf('%s, 0452 112 %03d, Sparkle Cleaning Company -Manager', $firstName, 200 + $index),
                'employer_ranking' => $index % 6 === 0 ? (($index % 5) + 1) : null,
                'employer_notes' => $index % 6 === 0 ? 'Test review note for seeded data.' : null,
                'submitted_at' => Carbon::now()->subDays($index % 21)->subHours($index),
            ]);
        });
    }
}
