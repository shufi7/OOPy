<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\ChapterQuizScoring;
use DOMDocument;
use DOMXPath;
use Tests\Feature\Quiz\QuizTestCase;

class ChapterAccessTest extends QuizTestCase
{
    public function test_guests_can_only_open_the_first_chapter_and_locked_cards_have_no_link(): void
    {
        $this->assertAvailableCards(['dasar-pemrograman-oop']);
        $this->get('/materi/dasar-pemrograman-oop')->assertOk();
        foreach (array_slice(array_keys(require resource_path('materi/chapters.php')), 1) as $slug) {
            $this->get('/materi/'.$slug)->assertRedirect(route('materi.index'))
                ->assertSessionHas('chapter_locked');
        }
        $this->get('/materi/unknown')->assertNotFound();
        $this->get('/materi/evaluasi-akhir/tidak-ada')->assertNotFound();
        $this->get('/materi')->assertSee('Selesaikan kuis BAB 1');
    }

    public function test_failing_a_quiz_keeps_the_next_chapter_locked_and_passing_opens_only_one_more(): void
    {
        $this->actingAs(User::factory()->create());
        $this->assertAvailableCards(['dasar-pemrograman-oop']);
        $this->postJson($this->start()['submit_url'], $this->chapterAnswers(passed: false))->assertOk()
            ->assertJsonPath('result.passed', false);
        $this->get('/materi/kelas-dan-objek')->assertRedirect(route('materi.index'));
        $this->postJson($this->start()['submit_url'], $this->chapterAnswers())->assertOk()
            ->assertJsonPath('result.passed', true);
        $this->assertAvailableCards(['dasar-pemrograman-oop', 'kelas-dan-objek']);
        $this->get('/materi/kelas-dan-objek')->assertOk();
        $this->get('/materi/enkapsulasi')->assertRedirect(route('materi.index'));

        $this->postJson($this->start()['submit_url'], $this->chapterAnswers(passed: false))->assertOk();
        $this->get('/materi/dasar-pemrograman-oop')->assertOk();
        $this->get('/materi/kelas-dan-objek')->assertOk();
    }

    public function test_locked_quiz_endpoints_reject_attempts_progress_and_legacy_submissions_without_writing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $quiz = $this->quiz('kelas-dan-objek');
        $attempt = QuizAttempt::create(['user_id' => $user->id, 'quiz_id' => $quiz->id, 'started_at' => now()]);
        $url = '/materi/kelas-dan-objek/kuis';
        $this->postJson($url.'/attempts', ['passed' => true, 'user_id' => $user->id])->assertForbidden()
            ->assertJsonPath('required_chapter', 'dasar-pemrograman-oop');
        $this->getJson($url.'/progress?passed=true&score=100')->assertForbidden();
        $this->postJson($url.'/attempts/'.$attempt->id.'/submit', $this->answers($quiz))->assertForbidden();
        $this->assertDatabaseCount('quiz_attempts', 1);
        $this->assertDatabaseCount('quiz_answers', 0);
        $this->assertDatabaseCount('user_progress', 0);
        $this->assertDatabaseCount('quiz_attempt_questions', 0);
        foreach (['unknown', 'evaluasi-akhir'] as $slug) {
            $this->postJson('/materi/'.$slug.'/kuis/attempts')->assertNotFound();
        }
    }

    public function test_progress_gaps_cannot_be_used_to_skip_earlier_chapters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach (['kelas-dan-objek', 'enkapsulasi', 'pewarisan', 'polimorfisme', 'kelas-abstrak'] as $slug) {
            $user->progress()->create([
                'material_id' => Material::where('slug', $slug)->sole()->id,
                'status' => 'completed', 'completed_at' => now(),
            ]);
        }
        $this->assertAvailableCards(['dasar-pemrograman-oop']);
        $this->getJson('/materi/enkapsulasi')->assertForbidden()->assertJsonPath('required_chapter', 'dasar-pemrograman-oop');
        $this->getJson('/evaluasi/attempts')->assertForbidden();
    }

    public function test_unlocks_persist_after_login_and_are_scoped_to_the_current_account(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->postJson($this->start()['submit_url'], $this->chapterAnswers())->assertOk();
        $this->post('/logout');
        $this->get('/materi/kelas-dan-objek')->assertRedirect(route('materi.index'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/materi');
        $this->get('/materi/kelas-dan-objek')->assertOk();
        $this->actingAs(User::factory()->create());
        $this->getJson('/materi/kelas-dan-objek?user_id='.$user->id.'&passed=true')->assertForbidden();
        $this->assertAvailableCards(['dasar-pemrograman-oop']);
    }

    public function test_final_exam_pages_and_all_api_endpoints_require_six_completed_chapters(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $id = '00000000-0000-4000-8000-000000000001';
        $this->putJson('/evaluasi/attempts/'.$id.'/answers', ['answers' => []])->assertForbidden();
        $this->postJson('/evaluasi/attempts/'.$id.'/submit', ['answers' => []])->assertForbidden();
        $slugs = array_keys(require resource_path('materi/chapters.php'));
        foreach (array_slice($slugs, 0, 6) as $index => $slug) {
            foreach (['', '/ujian', '/hasil'] as $suffix) {
                $this->get('/materi/evaluasi-akhir'.$suffix)->assertRedirect(route('materi.index'));
            }
            $this->getJson('/evaluasi/attempts')->assertForbidden();
            $this->postJson('/evaluasi/attempts')->assertForbidden();
            $this->get('/materi/'.$slug)->assertOk();
            $this->postJson($this->start($slug)['submit_url'], $this->chapterAnswers($slug))->assertOk();
            $this->assertAvailableCards(array_slice($slugs, 0, $index + 2));
        }
        foreach (['', '/ujian', '/hasil'] as $suffix) {
            $this->get('/materi/evaluasi-akhir'.$suffix)->assertOk();
        }
        $this->getJson('/evaluasi/attempts')->assertOk();
        $attempt = $this->postJson('/evaluasi/attempts')->assertOk()->json('attempt.id');
        $this->putJson('/evaluasi/attempts/'.$attempt.'/answers', ['answers' => []])->assertOk();
        $this->postJson('/evaluasi/attempts/'.$attempt.'/submit', ['answers' => []])->assertOk();
        $this->get('/materi/dasar-pemrograman-oop')->assertOk();
    }

    private function chapterAnswers(string $slug = 'dasar-pemrograman-oop', bool $passed = true): array
    {
        $quiz = $this->quiz($slug);
        $minimum = ChapterQuizScoring::minimumCorrect($quiz->questions->count());
        $correct = $passed ? $minimum : $minimum - 1;

        return ['answers' => $quiz->questions->map(fn ($question, $index) => [
            'question_id' => $question->id,
            'answer' => $question->question_type === 'multiple_choice'
                ? ($index < $correct ? (int) $question->correct_answer : ((int) $question->correct_answer + 1) % count($question->options))
                : ($index < $correct ? $question->correct_answer : '__wrong_answer__'),
        ])->all()];
    }

    private function assertAvailableCards(array $slugs): void
    {
        $response = $this->get('/materi')->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $xpath = new DOMXPath($dom);
        $cards = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " materi-card ")]');
        $this->assertSame(7, $cards->length);
        $registry = array_keys(require resource_path('materi/chapters.php'));
        foreach ($registry as $index => $slug) {
            $links = $xpath->query('.//a', $cards->item($index));
            $unlocked = in_array($slug, $slugs, true);
            $this->assertSame($unlocked ? 1 : 0, $links->length, $slug);
            $this->assertSame($unlocked ? 0 : 1, $xpath->query('.//button[@disabled]', $cards->item($index))->length, $slug);
        }
    }
}
