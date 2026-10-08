<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['sqlite', 'mysql', 'mariadb'], true)) {
            throw new RuntimeException('OOPy learning integrity supports MySQL/MariaDB and SQLite testing.');
        }
        if ($driver !== 'sqlite') {
            $version = DB::connection()->getServerVersion();
            $minimum = str_contains(strtolower($version), 'mariadb') ? '10.2.1' : '8.0.16';
            if (version_compare($version, $minimum, '<')) {
                throw new RuntimeException('This server does not enforce the required CHECK constraints.');
            }
        }

        foreach ($this->checks($driver === 'sqlite') as $table => $expression) {
            if ($driver === 'sqlite') {
                $expression = preg_replace('/\b(role|type|chapter_order|material_order|question_order|options|correct_answer|code_snippet|question_type|passing_score|duration_seconds|status|completed_at|score|started_at|is_correct)\b/', 'NEW.$1', $expression);
                foreach (['INSERT', 'UPDATE'] as $event) {
                    $name = 'oopy_'.$table.'_'.strtolower($event).'_check';
                    DB::unprepared("CREATE TRIGGER $name BEFORE $event ON $table
                        WHEN NOT COALESCE(($expression), 0)
                        BEGIN SELECT RAISE(ABORT, '$table integrity violation'); END");
                }
            } else {
                DB::statement("ALTER TABLE $table ADD CONSTRAINT oopy_{$table}_check CHECK (COALESCE(($expression), 0) = 1)");
            }
        }

        foreach (['INSERT', 'UPDATE'] as $event) {
            $name = 'oopy_answers_essay_'.strtolower($event);
            if ($driver === 'sqlite') {
                DB::unprepared("CREATE TRIGGER $name BEFORE $event ON quiz_answers
                    WHEN NEW.is_correct IS NOT NULL AND EXISTS
                        (SELECT 1 FROM questions WHERE id = NEW.question_id AND question_type = 'essay')
                    BEGIN SELECT RAISE(ABORT, 'Ungraded essay correctness must be NULL'); END");
            } else {
                DB::unprepared("CREATE TRIGGER $name BEFORE $event ON quiz_answers FOR EACH ROW
                    BEGIN
                        IF NEW.is_correct IS NOT NULL AND EXISTS
                            (SELECT 1 FROM questions WHERE id = NEW.question_id AND question_type = 'essay') THEN
                            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Ungraded essay correctness must be NULL';
                        END IF;
                    END");
            }
        }
    }

    public function down(): void
    {
        foreach (['user_progress', 'exercise_submissions', 'quiz_attempts', 'quiz_answers'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Export user learning records before rolling back integrity constraints.');
            }
        }
        if (DB::table('materials')->whereNotNull('content')->exists() || DB::table('users')->where('role', 'admin')->exists()) {
            throw new RuntimeException('Export authored content/admin roles before rolling back the learning schema.');
        }
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        foreach (['insert', 'update'] as $event) {
            DB::unprepared("DROP TRIGGER IF EXISTS oopy_answers_essay_$event");
        }
        foreach (array_keys($this->checks($sqlite)) as $table) {
            if ($sqlite) {
                foreach (['insert', 'update'] as $event) {
                    DB::unprepared("DROP TRIGGER IF EXISTS oopy_{$table}_{$event}_check");
                }
            } else {
                $maria = str_contains(strtolower(DB::connection()->getServerVersion()), 'mariadb');
                DB::statement("ALTER TABLE $table DROP ".($maria ? 'CONSTRAINT' : 'CHECK')." oopy_{$table}_check");
            }
        }
    }

    private function checks(bool $sqlite): array
    {
        $array = $sqlite ? "json_valid(options) AND json_type(options) = 'array'" : "JSON_TYPE(options) = 'ARRAY'";
        $length = $sqlite ? 'json_array_length(options)' : 'JSON_LENGTH(options)';
        $index = $sqlite
            ? "correct_answer <> '' AND correct_answer NOT GLOB '*[^0-9]*' AND (correct_answer = '0' OR substr(correct_answer, 1, 1) BETWEEN '1' AND '9')"
            : "correct_answer REGEXP '^(0|[1-9][0-9]*)$'";
        $number = $sqlite ? 'CAST(correct_answer AS INTEGER)' : 'CAST(correct_answer AS UNSIGNED)';

        return [
            'users' => "role IN ('user', 'admin')",
            'chapters' => 'chapter_order > 0',
            'materials' => 'material_order > 0',
            'user_progress' => "status IN ('not_started', 'in_progress', 'completed') AND ((status = 'completed' AND completed_at IS NOT NULL) OR (status <> 'completed' AND completed_at IS NULL))",
            'exercise_submissions' => "status IN ('submitted', 'passed', 'failed', 'error') AND (score IS NULL OR score BETWEEN 0 AND 100)",
            'quizzes' => "type IN ('chapter_quiz', 'final_exam') AND passing_score BETWEEN 0 AND 100 AND (duration_seconds IS NULL OR duration_seconds > 0)",
            'questions' => "question_order > 0 AND CASE
                WHEN question_type = 'multiple_choice' THEN
                    options IS NOT NULL AND $array AND $length >= 2 AND correct_answer IS NOT NULL AND $index AND $number < $length
                WHEN question_type = 'code_fill' THEN
                    options IS NULL AND correct_answer IS NOT NULL AND LENGTH(TRIM(correct_answer)) > 0
                    AND code_snippet IS NOT NULL AND LENGTH(TRIM(code_snippet)) > 0
                WHEN question_type = 'essay' THEN options IS NULL AND correct_answer IS NULL
                ELSE 0 END",
            'quiz_attempts' => "status IN ('in_progress', 'completed', 'expired') AND (score IS NULL OR score BETWEEN 0 AND 100) AND "
                ."((status = 'in_progress' AND completed_at IS NULL) OR (status <> 'in_progress' AND completed_at IS NOT NULL AND completed_at >= started_at))",
            'quiz_answers' => 'is_correct IS NULL OR is_correct IN (0, 1)',
        ];
    }
};
