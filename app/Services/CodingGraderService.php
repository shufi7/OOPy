<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class CodingGraderService
{
    public function grade(string $exerciseKey, array $files): array
    {
        $url = config('coding.grader_url');
        $token = config('coding.grader_token');
        if (! $url || ! $token) {
            throw new RuntimeException('Grader belum dikonfigurasi.');
        }
        try {
            $response = Http::withToken($token)->acceptJson()->connectTimeout(2)
                ->timeout(config('coding.grader_timeout'))->withOptions(['allow_redirects' => false])
                ->post(rtrim($url, '/').'/grade', ['exercise_key' => $exerciseKey, 'files' => $files]);
            if (! $response->successful() || strlen($response->body()) > 50000) {
                throw new RuntimeException('Respons grader tidak tersedia.');
            }
            $report = $response->json();
            if (! is_array($report) || ($report['version'] ?? null) !== 2 || ($report['exercise_key'] ?? null) !== $exerciseKey
                || ! in_array($report['status'] ?? null, ['graded', 'error'], true)
                || ! is_array($report['checks'] ?? null) || ! array_is_list($report['checks'])
                || count($report['checks']) > 50 || ! is_string($report['output'] ?? null)) {
                throw new RuntimeException('Format grader tidak valid.');
            }
            foreach ($report['checks'] as $check) {
                if (! is_array($check) || ! is_bool($check['passed'] ?? null)
                    || ! is_string($check['label'] ?? null) || ! is_string($check['feedback'] ?? null)
                    || ! is_string($check['id'] ?? null) || ! is_bool($check['required'] ?? null)
                    || ! is_int($check['weight'] ?? null)) {
                    throw new RuntimeException('Hasil pemeriksaan tidak valid.');
                }
            }
            $catalog = require resource_path('coding/exercises.php');
            $exercise = collect($catalog)->firstWhere('key', $exerciseKey);
            if (! $exercise) {
                throw new RuntimeException('Latihan tidak dikenal.');
            }
            $expected = $report['status'] === 'error' ? ['execution'] : [...$exercise['core_checks'], 'presentation'];
            if (array_column($report['checks'], 'id') !== $expected) {
                throw new RuntimeException('Pemeriksaan wajib tidak lengkap atau berulang.');
            }
            $passed = $report['status'] === 'graded';
            $score = 0;
            foreach ($report['checks'] as $check) {
                $required = $check['id'] !== 'presentation';
                $weight = $check['id'] === 'execution' ? 0 : ($required ? 15 : 10);
                if ($check['required'] !== $required || $check['weight'] !== $weight
                    || ($check['id'] === 'execution' && $check['passed'])) {
                    throw new RuntimeException('Rubrik grader tidak sesuai.');
                }
                $passed = $passed && (! $required || $check['passed']);
                $score += $check['passed'] ? $weight : 0;
            }

            return [
                'status' => $report['status'] === 'error' ? 'error' : ($passed ? 'passed' : 'failed'),
                'score' => $score,
                'checks' => $report['checks'],
                'output' => mb_substr($report['output'], 0, 12000),
            ];
        } catch (Throwable $error) {
            throw new RuntimeException('Verifikasi resmi tidak tersedia.', previous: $error);
        }
    }
}
