<?php

namespace Tests\Feature\Quiz;

use App\Models\Material;
use App\Models\MaterialContent;
use App\Models\User;
use App\Services\Admin\MaterialContentService;
use PHPUnit\Framework\Attributes\DataProvider;

class PublishedQuizTest extends QuizTestCase
{
    public static function missingQuiz(): array
    {
        return ['missing field' => [[]], 'empty field' => [['quiz' => []]]];
    }

    #[DataProvider('missingQuiz')]
    public function test_legacy_published_content_keeps_its_edits_and_shows_the_chapter_quiz(array $quizField): void
    {
        $published = $quizField + [
            'description' => 'Deskripsi materi yang sudah dipublikasikan.',
            'sections' => [['id' => 'bagian-terbit', 'title' => 'Bagian yang disunting', 'paragraphs' => ['Teks terbit tetap digunakan.']]],
        ];
        $record = MaterialContent::create([
            'material_id' => Material::where('slug', 'dasar-pemrograman-oop')->sole()->id,
            'draft_title' => 'Judul BAB 1 yang disunting',
            'draft' => $published,
            'published' => $published,
            'published_title' => 'Judul BAB 1 yang disunting',
            'published_at' => now(),
        ]);

        $this->get('/materi/dasar-pemrograman-oop')->assertOk()
            ->assertSee('Kuis BAB 1')->assertSee('href="#kuis"', false)
            ->assertSee('Masuk untuk Mengerjakan Kuis');

        $this->actingAs(User::factory()->create());
        $response = $this->get('/materi/dasar-pemrograman-oop')->assertOk()
            ->assertSee('Judul BAB 1 yang disunting')->assertSee('Bagian yang disunting')
            ->assertSee('Teks terbit tetap digunakan.')->assertSee('Kuis BAB 1')
            ->assertSee('data-oopy-quiz', false)->assertSee('js/oopy-quiz.js');
        $quiz = $this->quiz();
        $this->assertCount($quiz->questions->count(), $response->viewData('quizQuestions'));
        foreach ($response->viewData('quizQuestions') as $question) {
            $this->assertArrayNotHasKey('correct_answer', $question);
            $this->assertArrayNotHasKey('answer', $question);
        }
        $this->postJson($this->start()['submit_url'], $this->answers($quiz, $quiz->questions->count()))
            ->assertOk()->assertJsonPath('result.passed', true)->assertJsonPath('progress.next_unlocked', true);
        $this->get('/materi/kelas-dan-objek')->assertOk();
        $this->assertSame($published, $record->fresh()->published);
    }

    public function test_an_existing_published_quiz_is_preserved(): void
    {
        $contents = app(MaterialContentService::class);
        $source = $contents->source('dasar-pemrograman-oop');
        $publishedQuiz = array_slice($source['quiz'], 0, 1);
        MaterialContent::create([
            'material_id' => Material::where('slug', 'dasar-pemrograman-oop')->sole()->id,
            'draft_title' => 'Kuis BAB 1',
            'draft' => ['quiz' => $publishedQuiz],
            'published' => ['quiz' => $publishedQuiz],
            'published_at' => now(),
        ]);

        [$content] = $contents->published('dasar-pemrograman-oop', $source);
        $this->assertSame($publishedQuiz, $content['quiz']);
    }
}
