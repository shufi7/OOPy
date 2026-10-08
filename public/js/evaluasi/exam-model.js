// Pure browser practice rules. No learner answer is executed as code.
export const emptyState = () => ({ version: 1, active: null, history: [] });

export function hasAnswer(question, answer) {
    return question.type === 'multiple_choice'
        ? Number.isInteger(answer) && answer >= 0 && answer < question.options.length
        : typeof answer === 'string' && answer.trim() !== '';
}

export function codeTokens(source) {
    if (typeof source !== 'string') return null;
    const tokens = [];
    let rest = source.trim();
    if (/[\r\n]/.test(rest)) return null;
    while (rest) {
        const match = rest.match(/^(?:[A-Za-z_]\w*|[.@=(),])/);
        if (!match) return null;
        tokens.push(match[0]);
        rest = rest.slice(match[0].length).trimStart();
    }
    return tokens.length ? JSON.stringify(tokens) : null;
}

export function isCorrect(question, answer) {
    if (!hasAnswer(question, answer) || question.type === 'essay') return false;
    if (question.type === 'multiple_choice') return answer === question.correct;
    const actual = codeTokens(answer);
    return actual !== null && actual === codeTokens(question.answer);
}

export function createAttempt(questions, settings, now, id) {
    return {
        id, contentVersion: settings.content_version, startedAt: now,
        deadline: now + settings.duration_seconds * 1000,
        policy: { durationSeconds: settings.duration_seconds, passThreshold: settings.pass_threshold },
        status: 'active', current: 0, review: [],
        answers: Object.fromEntries(questions.map((q) => [q.id, null])),
    };
}

export function remainingSeconds(attempt, now) {
    return Math.max(0, Math.ceil((attempt.deadline - now) / 1000));
}

export function formatSeconds(value) {
    const seconds = Math.max(0, Math.ceil(value));
    return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
}

export function gradeAttempt(attempt, questions, finishedAt) {
    const objective = questions.filter((q) => q.type !== 'essay');
    const correct = objective.filter((q) => isCorrect(q, attempt.answers[q.id])).length;
    const score = correct / objective.length * 100;
    return {
        correct, incorrect: objective.length - correct, objectiveTotal: objective.length,
        score, metThreshold: score >= attempt.policy.passThreshold,
        essaysFilled: questions.filter((q) => q.type === 'essay' && hasAnswer(q, attempt.answers[q.id])).length,
        essayTotal: questions.filter((q) => q.type === 'essay').length,
        durationMs: Math.max(0, Math.min(finishedAt, attempt.deadline) - attempt.startedAt),
    };
}

export function finishAttempt(state, questions, now) {
    const attempt = state.active;
    if (!attempt) return state;
    if (state.history.some((record) => record.id === attempt.id)) return { ...state, active: null };
    const finishedAt = Math.max(attempt.startedAt, Math.min(now, attempt.deadline));
    const record = { ...attempt, status: 'completed', finishedAt, timedOut: now >= attempt.deadline,
        result: gradeAttempt(attempt, questions, finishedAt) };
    return { ...state, active: null, history: [record, ...state.history] };
}

const object = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);
const timestamp = (value) => Number.isSafeInteger(value) && value >= 0;

function validAttempt(value, questions, settings, completed) {
    if (!object(value) || typeof value.id !== 'string' || !/^[A-Za-z0-9_-]{1,100}$/.test(value.id)
        || value.contentVersion !== settings.content_version || !timestamp(value.startedAt) || !timestamp(value.deadline)
        || !object(value.policy) || !Number.isInteger(value.policy.durationSeconds) || value.policy.durationSeconds <= 0
        || !Number.isFinite(value.policy.passThreshold) || value.policy.passThreshold < 0 || value.policy.passThreshold > 100
        || value.deadline - value.startedAt !== value.policy.durationSeconds * 1000
        || !Number.isInteger(value.current) || value.current < 0 || value.current >= questions.length
        || !object(value.answers) || !Array.isArray(value.review)
        || value.status !== (completed ? 'completed' : 'active')) return false;
    const ids = questions.map((q) => q.id);
    if (value.review.some((id) => !ids.includes(id)) || new Set(value.review).size !== value.review.length
        || Object.keys(value.answers).length !== ids.length) return false;
    if (questions.some((q) => {
        const answer = value.answers[q.id];
        if (answer === null) return false;
        return q.type === 'multiple_choice' ? !hasAnswer(q, answer)
            : typeof answer !== 'string' || answer.length > (q.type === 'essay' ? 10000 : 500);
    })) return false;
    if (completed && (!timestamp(value.finishedAt) || value.finishedAt < value.startedAt
        || value.finishedAt > value.deadline || typeof value.timedOut !== 'boolean')) return false;
    return true;
}

export function decodeState(raw, questions, settings) {
    if (raw === null) return { state: emptyState(), invalid: false };
    try {
        const stored = JSON.parse(raw);
        if (!object(stored) || stored.version !== settings.schema_version || !Array.isArray(stored.history)) throw new Error('Invalid schema');
        let invalid = false;
        const records = new Map();
        for (const record of stored.history) {
            if (!validAttempt(record, questions, settings, true)) { invalid = true; continue; }
            if (records.has(record.id)) { invalid = true; continue; }
            records.set(record.id, { ...record, policy: currentPolicy(record.policy), result: gradeAttempt(record, questions, record.finishedAt) });
        }
        let active = stored.active;
        if (active !== null && !validAttempt(active, questions, settings, false)) { active = null; invalid = true; }
        if (active && records.has(active.id)) { active = null; invalid = true; }
        if (active) active = { ...active, policy: currentPolicy(active.policy) };
        return { state: { version: 1, active, history: [...records.values()].sort((a, b) => b.finishedAt - a.finishedAt) }, invalid };
    } catch {
        return { state: emptyState(), invalid: true };
    }
}

// Existing v1 sessions/history remain usable; retired retry delays are discarded.
function currentPolicy(policy) {
    return { durationSeconds: policy.durationSeconds, passThreshold: policy.passThreshold };
}

export function mergeStates(latest, incoming) {
    const records = new Map();
    // Completed records are immutable; repeated Submit/refresh keeps one record.
    for (const record of [...latest.history, ...incoming.history]) if (!records.has(record.id)) records.set(record.id, record);
    const active = incoming.active || latest.active;
    return { version: 1, active: active && !records.has(active.id) ? active : null,
        history: [...records.values()].sort((a, b) => b.finishedAt - a.finishedAt) };
}
