<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Application</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; }
        h1 { margin-bottom: 8px; font-size: 24px; }
        h2 { margin-top: 24px; margin-bottom: 8px; font-size: 16px; color: #1e3a5f; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; vertical-align: top; text-align: left; }
        th { width: 28%; background: #f8fafc; }
        .meta { margin-bottom: 16px; color: #475569; }
    </style>
</head>
<body>
    <h1>Job Application</h1>
    <div class="meta">Application for {{ $application->job?->job_id ?? 'Unknown Job' }} submitted {{ $application->submitted_at?->format('Y-m-d H:i') }}</div>

    <h2>Applicant Basics</h2>
    <table>
        <tr><th>Name</th><td>{{ $application->name }}</td></tr>
        <tr><th>Suburb</th><td>{{ $application->suburb }}</td></tr>
        <tr><th>Contact No</th><td>{{ $application->contact_no }}</td></tr>
        <tr><th>Email</th><td>{{ $application->email }}</td></tr>
        <tr><th>Availability</th><td>{{ $application->availability }}</td></tr>
        <tr><th>Visa Status</th><td>{{ $application->visa_status }}</td></tr>
        <tr><th>Visa Other</th><td>{{ $application->visa_other ?: '-' }}</td></tr>
    </table>

    <h2>Checks</h2>
    <table>
        <tr><th>Reliable Transport</th><td>{{ $application->reliable_transport ? 'Yes' : 'No' }}</td></tr>
        <tr><th>Driving Licence</th><td>{{ $application->driving_licence ? 'Yes' : 'No' }}</td></tr>
        <tr><th>Has ABN</th><td>{{ $application->has_abn ? 'Yes' : 'No' }}</td></tr>
        <tr><th>Criminal Conviction</th><td>{{ $application->criminal_conviction ? 'Yes' : 'No' }}</td></tr>
        <tr><th>Police Clearance</th><td>{{ $application->police_clearance ? 'Yes' : 'No' }}</td></tr>
        <tr><th>Workers Comp</th><td>{{ $application->workers_comp ? 'Yes' : 'No' }}</td></tr>
    </table>

    <h2>Education, Work & References</h2>
    <table>
        <tr><th>Education</th><td>{{ $application->education }}</td></tr>
        <tr><th>Work Experience 1</th><td>{{ $application->work_exp_1 }}</td></tr>
        <tr><th>Work Experience 2</th><td>{{ $application->work_exp_2 }}</td></tr>
        <tr><th>References</th><td>{{ $application->references }}</td></tr>
        <tr><th>I declare that the information I have provided is true and correct</th><td>Yes</td></tr>
    </table>
</body>
</html>
