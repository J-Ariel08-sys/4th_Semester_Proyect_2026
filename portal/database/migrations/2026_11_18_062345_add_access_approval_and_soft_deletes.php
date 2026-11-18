<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['users', 'hospitals', 'specialties', 'cohorts', 'academic_requirements', 'cycles', 'places'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->softDeletes());
        }
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('access_approved_at')->nullable();
            $table->foreignId('access_approved_by')->nullable()->constrained('users');
        });
        DB::table('users')->where('active', true)->where(fn ($query) => $query->where('role', '!=', 'interno')->orWhere('eligible', true))->update(['access_approved_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('access_approved_by');
            $table->dropColumn('access_approved_at');
        });
        foreach (['users', 'hospitals', 'specialties', 'cohorts', 'academic_requirements', 'cycles', 'places'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropSoftDeletes());
        }
    }
};
