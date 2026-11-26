<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable();
            $table->unsignedInteger('security_version')->default(1);
            $table->string('phone', 40)->nullable();
            $table->unsignedInteger('supervision_limit')->default(10);
        });
        DB::table('users')->update(['activated_at' => now()]);
        Schema::create('otp_limits', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('window_start');
            $table->timestamp('last_request_at')->nullable();
        });
        Schema::create('otp_challenges', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('account_hash', 64)->index();
            $table->string('purpose', 20);
            $table->string('verifier', 64);
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('grant_verifier', 64)->nullable();
            $table->timestamp('grant_expires_at')->nullable();
            $table->timestamps();
        });
        Schema::table('optimization_runs', function (Blueprint $table) {
            $table->string('execution_status')->default('completada');
            $table->string('plan_status')->default('calculado');
            $table->uuid('request_id')->nullable()->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('diagnostic')->nullable();
            $table->unsignedInteger('attempts')->default(0);
        });
        Schema::create('assignment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained();
            $table->foreignId('run_id')->constrained('optimization_runs');
            $table->foreignId('place_id')->constrained();
            $table->foreignId('tutor_id')->constrained('users');
            $table->foreignId('changed_by')->constrained('users');
            $table->string('reason');
            $table->timestamp('created_at');
        });
        Schema::table('clinical_records', fn (Blueprint $table) => $table->json('rubric_snapshot')->nullable());
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('run_id')->nullable()->constrained('optimization_runs');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('status')->default('vigente');
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamp('updated_at');
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX users_email_normalized ON users (lower(trim(email)))');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_valid CHECK (role IN ('administrador','coordinador','interno','tutor'))");
            DB::statement('ALTER TABLE places ADD CONSTRAINT places_capacity_valid CHECK (capacity >= 0 AND reserve >= 0 AND reserve <= capacity)');
            DB::statement('ALTER TABLE cycles ADD CONSTRAINT cycles_dates_valid CHECK (ends_on >= starts_on AND closes_at > opens_at)');
            DB::statement('ALTER TABLE availabilities ADD CONSTRAINT availability_dates_valid CHECK (ends_at > starts_at)');
            DB::statement('ALTER TABLE shifts ADD CONSTRAINT shift_dates_valid CHECK (ends_at > starts_at)');
            DB::statement('ALTER TABLE users ADD CONSTRAINT academic_values_valid CHECK (academic_average BETWEEN 0 AND 100 AND merit BETWEEN 0 AND 100 AND supervision_limit > 0)');
            DB::statement('ALTER TABLE clinical_records ADD CONSTRAINT grade_valid CHECK (grade IS NULL OR grade BETWEEN 0 AND 100)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX users_email_normalized');
            foreach (['users' => ['users_role_valid', 'academic_values_valid'], 'places' => ['places_capacity_valid'], 'cycles' => ['cycles_dates_valid'], 'availabilities' => ['availability_dates_valid'], 'shifts' => ['shift_dates_valid'], 'clinical_records' => ['grade_valid']] as $table => $constraints) {
                foreach ($constraints as $constraint) { DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraint}"); }
            }
        }
        Schema::dropIfExists('settings');
        Schema::dropIfExists('assignment_history');
        Schema::dropIfExists('otp_challenges');
        Schema::dropIfExists('otp_limits');
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('run_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('status');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['activated_at', 'security_version', 'phone', 'supervision_limit']));
        Schema::table('optimization_runs', fn (Blueprint $table) => $table->dropColumn(['execution_status', 'plan_status', 'request_id', 'started_at', 'finished_at', 'duration_ms', 'diagnostic', 'attempts']));
    }
};
