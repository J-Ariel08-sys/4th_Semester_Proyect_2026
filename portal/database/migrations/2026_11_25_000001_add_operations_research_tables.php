<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            if (! Schema::hasColumn('hospitals', 'tutors_count')) {
                $table->unsignedInteger('tutors_count')->default(3);
            }
            if (! Schema::hasColumn('hospitals', 'lambda_e1')) {
                $table->decimal('lambda_e1', 6, 2)->default(8.00);
            }
            if (! Schema::hasColumn('hospitals', 'lambda_e2')) {
                $table->decimal('lambda_e2', 6, 2)->default(10.00);
            }
            if (! Schema::hasColumn('hospitals', 'lambda_e3')) {
                $table->decimal('lambda_e3', 6, 2)->default(11.00);
            }
            if (! Schema::hasColumn('hospitals', 'mu_without_interns')) {
                $table->decimal('mu_without_interns', 6, 2)->default(4.00);
            }
            if (! Schema::hasColumn('hospitals', 'mu_with_interns')) {
                $table->decimal('mu_with_interns', 6, 2)->default(5.00);
            }
        });

        if (! Schema::hasTable('monthly_network_states')) {
            Schema::create('monthly_network_states', function (Blueprint $table) {
                $table->id();
                $table->string('period_month', 7)->unique();
                $table->string('state', 4);
                $table->text('notes')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_network_states');

        Schema::table('hospitals', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['tutors_count', 'lambda_e1', 'lambda_e2', 'lambda_e3', 'mu_without_interns', 'mu_with_interns'],
                fn (string $col) => Schema::hasColumn('hospitals', $col)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
