<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityAndValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_is_protected_against_sql_injection(): void
    {
        $this->seed();
        $payloads = [
            "' OR '1'='1",
            "admin@siclin.test'--",
            "' UNION SELECT * FROM users--",
            "admin@siclin.test' OR 1=1;--",
        ];

        foreach ($payloads as $payload) {
            $response = $this->post('/login', [
                'email' => $payload,
                'password' => "' OR '1'='1",
            ]);
            $response->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_resource_url_rejects_sql_injection_with_404(): void
    {
        $this->seed();
        $admin = User::where('role', 'administrador')->firstOrFail();
        $this->actingAs($admin);

        $response = $this->get('/gestion/usuarios%27%20OR%201=1--');
        $response->assertNotFound();

        $response = $this->get('/verificar/\' OR 1=1--');
        $response->assertNotFound();
    }

    public function test_date_fields_reject_past_dates(): void
    {
        $this->seed();
        $coord = User::where('role', 'coordinador')->firstOrFail();
        $this->actingAs($coord);

        // Hospital agreement_until cannot be in the past
        $response = $this->post(route('management.save', 'hospitales'), [
            'name' => 'Hospital San Juan',
            'agreement_until' => now()->subDay()->toDateString(),
            'active' => 1,
        ]);
        $response->assertSessionHasErrors('agreement_until');

        // Convocatoria starts_on cannot be in the past
        $specialty = DB::table('specialties')->first();
        $response = $this->post(route('management.save', 'convocatorias'), [
            'name' => 'Convocatoria Pasada Inválida',
            'specialty_id' => $specialty->id,
            'starts_on' => now()->subDays(5)->toDateString(),
            'ends_on' => now()->addDays(20)->toDateString(),
            'opens_at' => now()->subDays(10)->toDateString(),
            'closes_at' => now()->subDays(6)->toDateString(),
        ]);
        $response->assertSessionHasErrors(['starts_on', 'opens_at']);

        // Shift availability cannot start in the past
        $tutor = User::where('role', 'tutor')->firstOrFail();
        $response = $this->post(route('availability.store'), [
            'tutor_id' => $tutor->id,
            'starts_at' => now()->subDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->toDateTimeString(),
        ]);
        $response->assertSessionHasErrors('starts_at');
    }
}
