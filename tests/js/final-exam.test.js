import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { emptyState, createAttempt, codeTokens, decodeState, finishAttempt, gradeAttempt, isCorrect, hasAnswer, remainingSeconds, mergeStates } from '../../public/js/evaluasi/exam-model.js';
import { ExamStorage } from '../../public/js/evaluasi/exam-storage.js';
import { ExamTicker } from '../../public/js/evaluasi/exam-timer.js';

const source = spawnSync(process.env.OOPY_PHP || 'php', ['-d', 'display_errors=stderr', '-r',
    "$data = require 'resources/materi/evaluasi-akhir.php'; echo json_encode(['questions' => $data['questions'], 'settings' => require 'config/evaluasi.php']);",
], { encoding: 'utf8' });
assert.equal(source.status, 0, source.stderr);
const { questions, settings } = JSON.parse(source.stdout);
const now = 1_000_000;
const attempt = () => createAttempt(questions, settings, now, 'attempt-1');
const answers = (session, correct) => {
    questions.filter((q) => q.type !== 'essay').forEach((q, index) => {
        session.answers[q.id] = index < correct ? q.type === 'multiple_choice' ? q.correct : q.answer
            : q.type === 'multiple_choice' ? (q.correct + 1) % q.options.length : 'salah';
    });
    return session;
};

test('objective scores use exactly 15 questions and the unrounded threshold; essays remain ungraded', () => {
    assert.equal(questions.length, 20);
    for (const [correct, passed] of [[0, false], [10, false], [11, true], [15, true]]) {
        const session = answers(attempt(), correct);
        session.answers.q16 = 'x'; // A short essay counts as filled, never as correct.
        session.answers.q17 = '   ';
        const result = gradeAttempt(session, questions, now + 1000);
        assert.equal(result.correct, correct);
        assert.equal(result.incorrect, 15 - correct);
        assert.equal(result.objectiveTotal, 15);
        assert.equal(result.score, correct / 15 * 100);
        assert.equal(result.metThreshold, passed);
        assert.equal(result.essaysFilled, 1);
        assert.equal(result.essayTotal, 5);
    }
    const session = answers(attempt(), 10);
    session.policy.passThreshold = 66.67;
    assert.equal(Number(gradeAttempt(session, questions, now).score.toFixed(2)), 66.67);
    assert.equal(gradeAttempt(session, questions, now).metThreshold, false);
});

test('code fill normalization preserves token boundaries and case, rejecting substrings and extra code', () => {
    for (const q of questions.filter((q) => q.type === 'code_fill')) {
        assert.equal(isCorrect(q, `  ${q.answer}  `), true);
        assert.equal(isCorrect(q, q.answer.toUpperCase()), false);
        assert.equal(isCorrect(q, `${q.answer}; print(1)`), false);
        assert.equal(isCorrect(q, `prefix ${q.answer}`), false);
        assert.equal(isCorrect(q, ''), false);
    }
    assert.equal(isCorrect(questions[10], 'self.nilai=nilai'), true);
    assert.equal(isCorrect(questions[10], 's e l f.nilai = nilai'), false);
    assert.equal(isCorrect(questions[11], 'return\nself._ph'), false);
    assert.equal(isCorrect(questions[12], 'super ( ).__init__( nama , lokasi )'), true);
    assert.equal(codeTokens('baca_data + anything'), null);
    assert.equal(hasAnswer(questions[15], '  '), false);
    assert.equal(hasAnswer(questions[0], 0), true);
});

test('deadline persists across serialization; expired attempts finish once with elapsed time capped', () => {
    const session = answers(attempt(), 11);
    session.current = 19; session.review = ['q20']; session.answers.q20 = 'ABC membuat kontrak eksplisit.';
    const saved = { ...emptyState(), active: session };
    const loaded = decodeState(JSON.stringify(saved), questions, settings);
    assert.equal(loaded.invalid, false);
    assert.deepEqual(loaded.state.active, session);
    assert.equal(remainingSeconds(loaded.state.active, now + 100_000), 2300);
    const ended = finishAttempt(loaded.state, questions, session.deadline + 30_000);
    assert.equal(ended.active, null);
    assert.equal(ended.history.length, 1);
    assert.equal(ended.history[0].timedOut, true);
    assert.equal(ended.history[0].finishedAt, session.deadline);
    assert.equal(ended.history[0].result.durationMs, settings.duration_seconds * 1000);
    assert.deepEqual(finishAttempt(ended, questions, session.deadline + 100_000), ended);
    const stale = { ...saved, history: ended.history };
    assert.equal(mergeStates(ended, stale).active, null);
    assert.equal(mergeStates(ended, stale).history.length, 1);
});

test('legacy retry delays are discarded without losing failed history or active answers', () => {
    const failed = finishAttempt({ ...emptyState(), active: answers(attempt(), 10) }, questions, now + 1000);
    failed.history[0].policy.cooldownSeconds = 3600;
    failed.active = createAttempt(questions, settings, now + 2000, 'attempt-2');
    failed.active.policy.cooldownSeconds = 3600;
    failed.active.answers.q11 = 'self.nilai=nilai';
    const decoded = decodeState(JSON.stringify(failed), questions, settings);
    assert.equal(decoded.invalid, false);
    assert.equal(decoded.state.history.length, 1);
    assert.equal(decoded.state.history[0].result.correct, 10);
    assert.equal(decoded.state.active.answers.q11, 'self.nilai=nilai');
    assert.equal(decoded.state.active.deadline, failed.active.deadline);
    assert.equal(Object.hasOwn(decoded.state.active.policy, 'cooldownSeconds'), false);
    assert.equal(Object.hasOwn(decoded.state.history[0].policy, 'cooldownSeconds'), false);
    assert.equal(Object.hasOwn(attempt().policy, 'cooldownSeconds'), false);
});

test('malformed storage fails safely, invalid records are removed and stored totals are recalculated', () => {
    for (const raw of ['{broken', '[]', 'null', '42', JSON.stringify({ version: 2, active: null, history: [] })]) {
        const decoded = decodeState(raw, questions, settings);
        assert.equal(decoded.invalid, true);
        assert.deepEqual(decoded.state, emptyState());
    }
    const ended = finishAttempt({ ...emptyState(), active: answers(attempt(), 11) }, questions, now + 5000);
    ended.history[0].result = { score: 0, metThreshold: false };
    const recovered = decodeState(JSON.stringify(ended), questions, settings);
    assert.equal(recovered.state.history[0].result.correct, 11);
    assert.equal(recovered.state.history[0].result.metThreshold, true);
    ended.history.push({ id: 'invalid' });
    assert.equal(decodeState(JSON.stringify(ended), questions, settings).state.history.length, 1);
    const active = attempt(); active.deadline += 1;
    assert.equal(decodeState(JSON.stringify({ ...emptyState(), active }), questions, settings).state.active, null);
});

test('storage keeps unrelated quiz keys and merges immutable history without duplicates', () => {
    const data = new Map([['oopy.quiz.progress', '{"kelas-abstrak":{"bestCorrect":4}}']]);
    const driver = { getItem: (key) => data.get(key) ?? null, setItem: (key, value) => data.set(key, value) };
    const storage = new ExamStorage(driver, questions, settings);
    const first = finishAttempt({ ...emptyState(), active: answers(attempt(), 11) }, questions, now + 1000);
    assert.equal(storage.save(first), true);
    const second = createAttempt(questions, settings, now + 2000, 'attempt-2');
    const other = finishAttempt({ ...emptyState(), active: second }, questions, now + 3000);
    driver.setItem(settings.storage_key, JSON.stringify(other));
    assert.equal(storage.save(first), true);
    assert.deepEqual(storage.state.history.map((entry) => entry.id), ['attempt-2', 'attempt-1']);
    storage.save(first);
    assert.equal(storage.state.history.length, 2);
    assert.equal(data.get('oopy.quiz.progress'), '{"kelas-abstrak":{"bestCorrect":4}}');
});

test('denied storage reads and writes notify clearly while preserving in-memory answers', () => {
    const messages = [];
    const storage = new ExamStorage({ getItem() { throw new Error('blocked'); }, setItem() { throw new Error('blocked'); } }, questions, settings, (message) => messages.push(message));
    const active = attempt(); active.answers.q16 = 'Jawaban yang tetap ada.';
    assert.equal(storage.save({ ...emptyState(), active }), false);
    assert.equal(storage.state.active.answers.q16, 'Jawaban yang tetap ada.');
    assert.ok(messages.some((message) => message.includes('tidak dapat dibaca')));
    assert.ok(messages.some((message) => message.includes('belum dapat disimpan')));
});

test('timer owns one interval and clears it on restart, stop and immediate expiry', () => {
    const timers = new Map(); let sequence = 0; let ticks = 0;
    const ticker = new ExamTicker((fn) => { timers.set(++sequence, fn); return sequence; }, (id) => timers.delete(id));
    ticker.start(() => ticks++); ticker.start(() => ticks++);
    assert.equal(timers.size, 1);
    assert.equal(ticks, 2);
    [...timers.values()][0]();
    assert.equal(ticks, 3);
    ticker.stop(); assert.equal(timers.size, 0);
    ticker.start(() => ticker.stop()); assert.equal(timers.size, 0);
});

test('default browser timers are called with the global receiver', () => {
    const original = { schedule: globalThis.setInterval, cancel: globalThis.clearInterval };
    let cancelled = false;
    try {
        globalThis.setInterval = function (fn) { assert.equal(this, globalThis); return 7; };
        globalThis.clearInterval = function (id) { assert.equal(this, globalThis); assert.equal(id, 7); cancelled = true; };
        const ticker = new ExamTicker();
        ticker.start(() => {});
        ticker.stop();
        assert.equal(cancelled, true);
    } finally {
        globalThis.setInterval = original.schedule;
        globalThis.clearInterval = original.cancel;
    }
});
