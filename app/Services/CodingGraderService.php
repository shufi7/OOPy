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
            if (! is_array($report) || ($report['version'] ?? null) !== 1 || ($report['exercise_key'] ?? null) !== $exerciseKey
                || ! in_array($report['status'] ?? null, ['graded', 'error'], true)
                || ! is_array($report['checks'] ?? null) || ! array_is_list($report['checks'])
                || count($report['checks']) > 50 || ! is_string($report['output'] ?? null)) {
                throw new RuntimeException('Format grader tidak valid.');
            }
            foreach ($report['checks'] as $check) {
                if (! is_array($check) || ! is_bool($check['passed'] ?? null)
                    || ! is_string($check['label'] ?? null) || ! is_string($check['feedback'] ?? null)) {
                    throw new RuntimeException('Hasil pemeriksaan tidak valid.');
                }
            }
            if ($report['status'] === 'graded' && $report['checks'] === []) {
                throw new RuntimeException('Pemeriksaan kosong.');
            }
            // Derive official status and score here, never from browser fields or a grader score.
            $passedCount = count(array_filter($report['checks'], fn ($check) => $check['passed']));
            $passed = $report['status'] === 'graded' && $passedCount === count($report['checks']);

            return [
                'status' => $report['status'] === 'error' ? 'error' : ($passed ? 'passed' : 'failed'),
                'score' => $report['status'] === 'error' ? 0 : (int) round($passedCount / count($report['checks']) * 100),
                'checks' => $report['checks'],
                'output' => mb_substr($report['output'], 0, 12000),
            ];
        } catch (Throwable $error) {
            throw new RuntimeException('Verifikasi resmi tidak tersedia.', previous: $error);
        }
    }
}
