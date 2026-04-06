<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'job_id',
        'tracking_link_id',
        'name',
        'suburb',
        'contact_no',
        'email',
        'availability',
        'visa_status',
        'visa_other',
        'reliable_transport',
        'driving_licence',
        'has_abn',
        'criminal_conviction',
        'police_clearance',
        'workers_comp',
        'education',
        'work_exp_1',
        'work_exp_2',
        'references',
        'employer_ranking',
        'employer_notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'reliable_transport' => 'boolean',
            'driving_licence' => 'boolean',
            'has_abn' => 'boolean',
            'criminal_conviction' => 'boolean',
            'police_clearance' => 'boolean',
            'workers_comp' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function trackingLink(): BelongsTo
    {
        return $this->belongsTo(TrackingLink::class);
    }
}
