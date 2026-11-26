<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('interno');
            $table->boolean('active')->default(true);
            $table->boolean('eligible')->default(false);
            $table->string('matricula')->nullable()->unique();
            $table->unsignedInteger('merit')->default(0);
        });
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->date('agreement_until');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('specialty_id')->constrained();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->dateTime('opens_at');
            $table->dateTime('closes_at');
            $table->string('status')->default('borrador');
            $table->timestamps();
        });
        Schema::create('places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained();
            $table->foreignId('hospital_id')->constrained();
            $table->foreignId('tutor_id')->constrained('users');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('reserve')->default(0);
            $table->timestamps();
            $table->unique(['cycle_id', 'hospital_id']);
        });
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->json('preferences');
            $table->uuid('receipt')->unique();
            $table->timestamps();
            $table->unique(['cycle_id', 'user_id']);
        });
        Schema::create('optimization_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->json('input');
            $table->json('result');
            $table->string('input_hash', 64);
            $table->string('status')->default('propuesta');
            $table->timestamps();
        });
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('place_id')->constrained();
            $table->foreignId('tutor_id')->constrained('users');
            $table->foreignId('run_id')->constrained('optimization_runs');
            $table->string('status')->default('aprobada');
            $table->timestamps();
            $table->unique(['cycle_id', 'user_id']);
        });
        Schema::create('clinical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained();
            $table->date('date');
            $table->string('attendance');
            $table->unsignedInteger('grade')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
            $table->unique(['assignment_id', 'date']);
        });
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->string('type');
            $table->text('description');
            $table->string('status')->default('abierta');
            $table->text('resolution')->nullable();
            $table->timestamps();
        });
        Schema::create('scenario_studies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('matrix');
            $table->json('result');
            $table->decimal('alpha', 4, 3);
            $table->string('chosen')->nullable();
            $table->text('justification')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained('users');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->timestamps();
        });
        Schema::create('shift_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('rest_hours');
            $table->string('status')->default('propuesta');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('shift_batches');
            $table->foreignId('assignment_id')->constrained();
            $table->foreignId('tutor_id')->nullable()->constrained('users');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('performed')->default(false);
            $table->text('reason')->nullable();
            $table->timestamps();
        });
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained();
            $table->uuid('token')->unique();
            $table->json('snapshot');
            $table->string('sha256', 64);
            $table->string('path');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action');
            $table->json('details');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'reports', 'shifts', 'shift_batches', 'availabilities', 'scenario_studies', 'incidents', 'clinical_records', 'assignments', 'optimization_runs', 'applications', 'places', 'cycles', 'specialties', 'hospitals'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'active', 'eligible', 'matricula', 'merit']);
        });
    }
};
