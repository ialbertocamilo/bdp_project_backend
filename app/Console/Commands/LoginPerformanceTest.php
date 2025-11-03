<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LoginPerformanceTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:login-performance {iterations=10 : Número de iteraciones de prueba}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Realiza pruebas de rendimiento en el endpoint de login y muestra estadísticas detalladas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $iterations = (int)$this->argument('iterations');

        $this->line('╔════════════════════════════════════════════════════════════╗');
        $this->line('║      ANÁLISIS DE RENDIMIENTO - Endpoint de Login           ║');
        $this->line('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $baseUrl = config('app.url');
        $endpoint = "{$baseUrl}/api/login";

        $this->info('Configuración:');
        $this->line("  - URL: {$endpoint}");
        $this->line('  - Método: POST');
        $this->line("  - Iteraciones: {$iterations}");
        $this->newLine();

        // Credenciales de prueba - Cambia según tu base de datos
        $credentials = [
            'email' => 'supervisor@example.com',
            'password' => 'password'
        ];

        $results = [
            'successful' => [],
            'failed' => [],
            'durations' => []
        ];

        $this->line('Ejecutando pruebas...');
        $this->newLine();

        for ($i = 1; $i <= $iterations; $i++) {
            try {
                $startTime = microtime(true);

                $response = Http::post($endpoint, $credentials);

                $endTime = microtime(true);
                $duration = ($endTime - $startTime) * 1000; // Convertir a milisegundos

                $results['successful'][] = [
                    'statusCode' => $response->status(),
                    'duration' => $duration,
                    'timestamp' => now()->toIso8601String()
                ];
                $results['durations'][] = $duration;

                $statusEmoji = in_array($response->status(), [200, 202]) ? '✓' : '✗';
                $this->line("[{$i}/{$iterations}] {$statusEmoji} {$response->status()} - " . number_format($duration, 2) . "ms");

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'error' => $e->getMessage(),
                    'iteration' => $i,
                    'timestamp' => now()->toIso8601String()
                ];
                $this->line("[{$i}/{$iterations}] ✗ ERROR - " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════╗');
        $this->line('║                    RESULTADOS                              ║');
        $this->line('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Calcular estadísticas
        if (!empty($results['durations'])) {
            $stats = $this->calculateStatistics($results['durations']);

            $this->info('Estadísticas de Tiempo de Respuesta (en milisegundos):');
            $this->line("  - Peticiones exitosas: " . count($results['successful']) . "/{$iterations}");
            $this->line("  - Peticiones fallidas: " . count($results['failed']) . "/{$iterations}");
            $this->line("  - Mínimo: " . number_format($stats['min'], 2) . "ms");
            $this->line("  - Máximo: " . number_format($stats['max'], 2) . "ms");
            $this->line("  - Promedio: " . number_format($stats['average'], 2) . "ms");
            $this->line("  - Mediana: " . number_format($stats['median'], 2) . "ms");
            $this->line("  - Percentil 95: " . number_format($stats['p95'], 2) . "ms");
            $this->line("  - Percentil 99: " . number_format($stats['p99'], 2) . "ms");
            $this->line("  - Desviación estándar: " . number_format($stats['standardDeviation'], 2) . "ms");
            $this->newLine();

            // Distribución por rangos
            $this->info('Distribución de tiempos de respuesta:');
            $ranges = [
                ['name' => '< 50ms', 'min' => 0, 'max' => 50],
                ['name' => '50-100ms', 'min' => 50, 'max' => 100],
                ['name' => '100-200ms', 'min' => 100, 'max' => 200],
                ['name' => '200-500ms', 'min' => 200, 'max' => 500],
                ['name' => '> 500ms', 'min' => 500, 'max' => PHP_INT_MAX]
            ];

            foreach ($ranges as $range) {
                $count = count(array_filter($results['durations'], function ($d) use ($range) {
                    return $d >= $range['min'] && $d < $range['max'];
                }));
                $percentage = number_format(($count / count($results['durations'])) * 100, 1);
                $barLength = intval($count / 2);
                $bar = str_repeat('█', $barLength);
                $this->line("  " . str_pad($range['name'], 15) . " │ " . str_pad($bar, 25) . " {$count} ({$percentage}%)");
            }
        }

        // Detalles de errores si los hay
        if (!empty($results['failed'])) {
            $this->newLine();
            $this->error('Errores encontrados:');
            foreach ($results['failed'] as $index => $error) {
                $this->line("  [" . ($index + 1) . "] " . $error['error'] . " (Iteración " . $error['iteration'] . ")");
            }
        }

        $this->newLine();
        $this->line('╔════════════════════════════════════════════════════════════╗');
        $this->line('║                Prueba finalizada                            ║');
        $this->line('╚════════════════════════════════════════════════════════════╝');

        // Guardar resultados en log
        Log::channel('single')->info('Login Performance Test Results', [
            'iterations' => $iterations,
            'successful' => count($results['successful']),
            'failed' => count($results['failed']),
            'statistics' => $this->calculateStatistics($results['durations'])
        ]);

        return 0;
    }

    /**
     * Calcula estadísticas de un arreglo de duraciones
     *
     * @param array $durations
     * @return array
     */
    private function calculateStatistics(array $durations): array
    {
        if (empty($durations)) {
            return [];
        }

        sort($durations);
        $count = count($durations);
        $sum = array_sum($durations);
        $avg = $sum / $count;

        // Calcular desviación estándar
        $variance = array_reduce($durations, function ($carry, $item) use ($avg) {
            return $carry + pow($item - $avg, 2);
        }, 0) / $count;
        $standardDeviation = sqrt($variance);

        return [
            'count' => $count,
            'min' => min($durations),
            'max' => max($durations),
            'average' => $avg,
            'median' => $durations[floor($count / 2)],
            'p95' => $durations[ceil($count * 0.95) - 1],
            'p99' => $durations[ceil($count * 0.99) - 1],
            'standardDeviation' => $standardDeviation
        ];
    }
}
