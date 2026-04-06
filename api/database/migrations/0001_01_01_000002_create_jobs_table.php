<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('job_id')->unique();
            $table->longText('advertisement');
            $table->longText('positive_points');
            $table->string('contact_email');
            $table->timestamps();
        });

        Schema::create('tracking_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('job_id');
            $table->string('code', 6)->unique();
            $table->string('label')->nullable();
            $table->string('external_post_url')->nullable();
            $table->unsignedInteger('visit_count')->default(0);
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('jobs')->cascadeOnDelete();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('job_id');
            $table->uuid('tracking_link_id')->nullable();
            $table->string('name');
            $table->string('suburb');
            $table->string('contact_no');
            $table->string('email');
            $table->text('availability');
            $table->string('visa_status');
            $table->string('visa_other')->nullable();
            $table->boolean('reliable_transport');
            $table->boolean('driving_licence');
            $table->boolean('has_abn');
            $table->boolean('criminal_conviction');
            $table->boolean('police_clearance');
            $table->boolean('workers_comp');
            $table->text('education');
            $table->text('work_exp_1');
            $table->text('work_exp_2');
            $table->text('references');
            $table->unsignedTinyInteger('employer_ranking')->nullable();
            $table->text('employer_notes')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->foreign('job_id')->references('id')->on('jobs')->cascadeOnDelete();
            $table->foreign('tracking_link_id')->references('id')->on('tracking_links')->nullOnDelete();
            $table->index('submitted_at');
            $table->index('employer_ranking');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('tokenable_type');
            $table->uuid('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('tracking_links');
        Schema::dropIfExists('jobs');
    }
};
