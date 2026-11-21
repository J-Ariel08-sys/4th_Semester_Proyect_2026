<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class ScenarioWorkbook
{
    private const HEADER = ['Alternativa', 'E1', 'E2', 'E3'];

    private const CRITERIA = ['Maximin', 'Maximax', 'Laplace', 'Hurwicz', 'Savage'];

    public function __construct(private readonly XlsxRows $xlsx) {}

    public function template(): string
    {
        return $this->xlsx->xlsx([self::HEADER, ['A1', 90, 60, 20], ['A2', 75, 65, 40], ['A3', 80, 70, 60]], 'Matriz de escenarios');
    }

    public function matrix(string $path): array
    {
        $rows = $this->xlsx->read($path, 4, 4);
        if (array_shift($rows) !== self::HEADER || count($rows) !== 3) {
            throw ValidationException::withMessages(['file' => 'Usa la plantilla Excel con las columnas Alternativa, E1, E2 y E3.']);
        }

        $matrix = [];
        foreach ($rows as $row) {
            $policy = array_shift($row);
            if (! isset(DecisionAnalysis::POLICIES[$policy]) || isset($matrix[$policy]) || count($row) !== 3) {
                throw ValidationException::withMessages(['file' => 'Incluye A1, A2 y A3 una vez cada una, con tres valores por alternativa.']);
            }
            $matrix[$policy] = array_map(fn (string $value) => str_replace(',', '.', trim($value)), $row);
        }

        return $matrix;
    }

    public function results(object $study, array $result): array
    {
        $matrix = json_decode($study->matrix, true);
        $scenarios = $result['scenarios'] ?? array_keys(reset($matrix));
        $input = [['Alternativa', ...array_map(fn ($column) => is_int($column) ? 'E'.($column + 1) : $column, $scenarios)]];
        foreach ($matrix as $policy => $row) {
            $input[] = [$policy, ...array_map('floatval', array_values($row))];
        }
        $scores = [['Alternativa', ...self::CRITERIA]];
        foreach ($result['scores'] as $policy => $row) {
            $scores[] = [$policy, ...array_values($row)];
        }
        $scores[] = ['Recomendación', ...array_map(fn ($winners) => implode(' / ', $winners), array_values($result['winners']))];

        $sensitivity = [['Optimismo', 'Alternativas por Hurwicz']];
        foreach ($result['sensitivity'] as $row) {
            $sensitivity[] = [$row['alpha'], implode(' / ', $row['alternativas'])];
        }

        return [
            'Datos del estudio' => [
                ['Nombre', $study->name],
                ['Optimismo', (float) $study->alpha],
                ['Fuente', $result['source'] ?? $result['simulation']['semantics'] ?? 'Datos del estudio'],
                ['Conversión', $result['conversion'] ?? ''],
                ['Política elegida', $study->chosen ?? 'Pendiente'],
                ['Justificación', $study->justification ?? ''],
            ],
            'Matriz de entrada' => $input,
            'Criterios de decisión' => $scores,
            'Sensibilidad de Hurwicz' => $sensitivity,
        ];
    }

    public function export(array $sections): string
    {
        $rows = [];
        foreach ($sections as $title => $section) {
            array_push($rows, [$title], ...$section);
            $rows[] = [];
        }

        return $this->xlsx->xlsx($rows, 'Resultados');
    }
}
