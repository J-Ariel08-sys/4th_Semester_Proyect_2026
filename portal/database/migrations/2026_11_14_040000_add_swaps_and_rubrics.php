<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical_records', function (Blueprint $table) {
            $table->json('competencies')->nullable();
        });
        Schema::create('swap_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('assignments');
            $table->foreignId('target_id')->constrained('assignments');
            $table->foreignId('source_place_id')->constrained('places');
            $table->foreignId('target_place_id')->constrained('places');
            $table->text('reason');
            $table->string('status')->default('solicitada');
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swap_requests');
        Schema::table('clinical_records', fn (Blueprint $table) => $table->dropColumn('competencies'));
    }
};
