<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class Settings
{
    public static function rubric(): array
    {
        $value = DB::table('settings')->where('key', 'rubric')->value('value');
        return $value ? json_decode($value, true) : ['conocimiento' => 25, 'procedimientos' => 25, 'comunicacion' => 25, 'profesionalismo' => 25];
    }

    public static function set(string $key, array $value): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value, JSON_THROW_ON_ERROR), 'updated_at' => now()]);
        Audit::record('configuracion.actualizada', ['clave' => $key, 'valor' => $value]);
    }
}
