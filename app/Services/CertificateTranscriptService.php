<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CertificateTranscriptService
{
    public const VERSION = 'oopy-transkrip-v1';

    public function __construct(private CertificateEligibilityService $eligibility) {}

    public function captureForArchivedCertificate(User $owner, string $publicId): void
    {
        DB::transaction(function () use ($owner, $publicId) {
            $owner = User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
            $certificate = Certificate::where('user_id', $owner->id)->where('program_key', MidtransPaymentService::PROGRAM)
                ->where('public_id', $publicId)->with('order')->lockForUpdate()->firstOrFail();
            abort_unless($certificate->isDownloadable(), 409, 'Transkrip hanya tersedia untuk sertifikat aktif.');
            abort_unless(array_key_exists('transcript_snapshot', $certificate->getAttributes()), 503, 'Penyimpanan transkrip belum tersedia.');

            if ($certificate->transcript_snapshot !== null) {
                return;
            }

            $snapshot = $this->fromEligibility($this->eligibility->forUser($owner));
            $snapshot['captured_after_issuance_at'] = now()->toIso8601String();
            Certificate::withoutTimestamps(fn () => $certificate->update(['transcript_snapshot' => $snapshot]));
        }, 5);
    }

    public static function archivedCaptureDate(?array $transcript): ?string
    {
        $capturedAt = $transcript['captured_after_issuance_at'] ?? null;

        return $capturedAt === null ? null : Carbon::parse($capturedAt)->timezone('Asia/Makassar')->locale('id')->translatedFormat('j F Y');
    }

    public function fromEligibility(array $eligibility): array
    {
        $quizThreshold = ChapterQuizScoring::passingScore();
        $rows = [];
        foreach ($eligibility['chapters'] as $chapter) {
            $score = $chapter['best_score'];
            $rows[] = $this->row($chapter['slug'], $chapter['bab'].' · '.$chapter['title'], $score,
                $score === null ? 'Belum dinilai' : ($score >= $quizThreshold ? 'Lulus' : 'Belum lulus'), $quizThreshold);
        }
        foreach ($eligibility['coding'] as $exercise) {
            $status = match ($exercise['status']) {
                'completed' => 'Lulus',
                'in_progress' => 'Belum lulus',
                default => 'Belum dimulai',
            };
            $rows[] = $this->row($exercise['key'], $exercise['title'], $exercise['best_score'], $status);
        }

        $finalScore = $eligibility['transcript_final_score'];
        $finalThreshold = $eligibility['transcript_final_threshold'];
        $rows[] = $this->row('evaluasi-akhir', 'Evaluasi Akhir', $finalScore, $eligibility['final_status'], $finalThreshold);

        return [
            'policy_version' => self::VERSION,
            'scale' => ['min' => 0, 'max' => 100],
            'rows' => $rows,
            'summary' => [
                'chapters' => [$eligibility['completed_chapters'], $eligibility['total_chapters']],
                'quizzes' => [$eligibility['passed_quizzes'], $eligibility['total_quizzes']],
                'coding' => [$eligibility['passed_coding'], $eligibility['total_coding']],
                'final_score' => $finalScore,
                'graduation' => $eligibility['eligible'] ? 'Lulus' : 'Belum memenuhi syarat',
            ],
            'policy' => [
                'Kuis: nilai resmi terbaik, batas lulus '.self::score($quizThreshold).'.',
                'Coding: nilai terbaik dari grader. Status lulus mengikuti hasil grader.',
                'Evaluasi Akhir: nilai terbaik dari percobaan lulus, atau percobaan selesai jika belum lulus.'
                    .($finalThreshold === null ? '' : ' Batas lulus percobaan yang ditampilkan: '.self::score($finalThreshold).'.'),
            ],
        ];
    }

    public static function score(?float $score): string
    {
        return $score === null ? '–' : ChapterQuizScoring::format($score);
    }

    private function row(string $key, string $label, ?float $score, string $status, ?float $passingScore = null): array
    {
        return ['key' => $key, 'label' => $label, 'score' => $score, 'status' => $status, 'passing_score' => $passingScore];
    }
}
