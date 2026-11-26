<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->unsignedInteger('entry_year'); $table->boolean('active')->default(true); $table->timestamps();
        });
        Schema::table('users', fn (Blueprint $table) => $table->foreignId('cohort_id')->nullable()->constrained());
        Schema::table('cycles', fn (Blueprint $table) => $table->foreignId('cohort_id')->nullable()->constrained());
        Schema::create('academic_requirements', function (Blueprint $table) {
            $table->id(); $table->foreignId('cohort_id')->constrained(); $table->foreignId('specialty_id')->constrained(); $table->unsignedInteger('min_days'); $table->unsignedInteger('required_blocks')->default(1); $table->timestamps(); $table->unique(['cohort_id','specialty_id']);
        });
        Schema::create('application_versions', function (Blueprint $table) {
            $table->id(); $table->foreignId('application_id')->constrained(); $table->json('preferences'); $table->uuid('receipt')->unique(); $table->foreignId('changed_by')->constrained('users'); $table->text('reason'); $table->timestamp('created_at');
        });
        Schema::table('shift_batches', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable(); $table->string('execution_status')->default('completada'); $table->json('input')->nullable(); $table->string('input_hash',64)->nullable(); $table->json('diagnostic')->nullable();
        });
        Schema::table('scenario_studies', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable(); $table->string('execution_status')->default('completada'); $table->json('input')->nullable(); $table->text('diagnostic')->nullable();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('application_versions'); Schema::dropIfExists('academic_requirements');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('cohort_id'));
        Schema::table('cycles', fn (Blueprint $table) => $table->dropConstrainedForeignId('cohort_id'));
        Schema::dropIfExists('cohorts');
        Schema::table('shift_batches', fn (Blueprint $table) => $table->dropColumn(['execution_status','input','input_hash','diagnostic']));
        Schema::table('scenario_studies', fn (Blueprint $table) => $table->dropColumn(['execution_status','input','diagnostic']));
    }
};
