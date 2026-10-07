// Browser learning-flow progress only; this does not restrict direct chapter URLs.
const TOTAL_QUESTIONS = 5;
const MIN_CORRECT_TO_PASS = 4;
const PROGRESS_KEY = 'oopy.quiz.progress';

document.querySelectorAll('[data-oopy-quiz]').forEach((root) => {
    const find = (name) => root.querySelector(`[data-quiz="${name}"]`);
    const questions = JSON.parse(find('questions').textContent);
    if (questions.length !== TOTAL_QUESTIONS) {
        find('loading').textContent = 'Kuis belum tersedia. Silakan coba lagi nanti.';
        return;
    }
    let answers = Array(questions.length).fill(null);
    let current = 0;
    let completed = false;
    const chapterSlug = root.dataset.chapterSlug;
    const navigation = root.closest('.material-article')?.querySelector('.material-navigation');
    const nextLink = navigation?.querySelector('[data-quiz-next-link]');
    const nextLocked = navigation?.querySelector('[data-quiz-next-locked]');
    const continueLink = find('continue');
    function readProgress() {
        try {
            const stored = JSON.parse(localStorage.getItem(PROGRESS_KEY));
            if (stored && typeof stored === 'object' && !Array.isArray(stored)) return stored;
        } catch {
            // Missing, malformed, or unavailable storage starts with no progress.
        }
        return {};
    }
    let progress = readProgress();
    const storedCorrect = progress[chapterSlug]?.bestCorrect;
    let bestCorrect = Number.isInteger(storedCorrect) && storedCorrect >= 0 && storedCorrect <= TOTAL_QUESTIONS ? storedCorrect : 0;
    let previouslyPassed = bestCorrect >= MIN_CORRECT_TO_PASS;
    const letter = (index) => String.fromCharCode(65 + index);
    const isCodeFill = (question) => question.type === 'code_fill';
    const hasAnswer = (question, answer) => isCodeFill(question)
        ? typeof answer === 'string' && answer.trim() !== ''
        : answer !== null;
    const isCorrect = (question, answer) => isCodeFill(question)
        ? typeof answer === 'string' && answer.trim() === question.answer
        : answer === question.correct;
    const element = (tag, text, className) => {
        const node = document.createElement(tag);
        node.textContent = text;
        if (className) node.className = className;
        return node;
    };

    function updateNavigation() {
        if (nextLink) nextLink.hidden = !previouslyPassed;
        if (nextLocked) nextLocked.hidden = previouslyPassed;
    }

    function saveResult(correct) {
        // Merge the latest records so attempts in another chapter/tab are retained.
        progress = { ...progress, ...readProgress() };
        const latestCorrect = progress[chapterSlug]?.bestCorrect;
        if (Number.isInteger(latestCorrect) && latestCorrect >= 0 && latestCorrect <= TOTAL_QUESTIONS) {
            bestCorrect = Math.max(bestCorrect, latestCorrect);
        }
        bestCorrect = Math.max(bestCorrect, correct);
        previouslyPassed = bestCorrect >= MIN_CORRECT_TO_PASS;
        progress[chapterSlug] = {
            passed: previouslyPassed,
            bestCorrect,
            bestScore: Math.round(bestCorrect / TOTAL_QUESTIONS * 100),
        };
        try {
            localStorage.setItem(PROGRESS_KEY, JSON.stringify(progress));
        } catch {
            // Passing still unlocks this page when browser storage is unavailable.
        }
        updateNavigation();
    }

    function clearResults() {
        ['score', 'correct', 'incorrect', 'status', 'message'].forEach((name) => {
            find(name).textContent = '';
        });
        if (continueLink) continueLink.hidden = true;
        find('results').hidden = true;
    }

    function updateAnswered() {
        find('answered').textContent = `${answers.filter((answer, index) => hasAnswer(questions[index], answer)).length} dari ${questions.length} soal dijawab`;
    }

    function renderQuestion(focus = true) {
        const question = questions[current];
        find('counter').textContent = `Soal ${current + 1} dari ${questions.length} soal`;
        find('question').textContent = question.question;
        find('code-card').hidden = !question.code;
        find('code').textContent = question.code || '';
        window.OopySyntax?.highlight(find('code'));
        find('options').replaceChildren();
        const codeFill = isCodeFill(question);
        find('options-group').hidden = codeFill;
        find('code-fill-group').hidden = !codeFill;
        find('code-fill').disabled = !codeFill;
        find('code-fill').value = codeFill ? answers[current] ?? '' : '';
        if (!codeFill) question.options.forEach((option, index) => {
            const label = document.createElement('label');
            label.className = 'oopy-quiz-option';
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = `${root.id}-answer`;
            input.value = String(index);
            input.checked = answers[current] === index;
            input.addEventListener('change', () => {
                answers[current] = index;
                find('validation').textContent = '';
                updateAnswered();
            });
            label.append(input, element('span', `${letter(index)}.`, 'oopy-quiz-letter'), element('span', option));
            find('options').append(label);
        });
        find('previous').disabled = current === 0;
        find('next').textContent = current === questions.length - 1 ? 'Selesai Kuis' : 'Soal Selanjutnya';
        updateAnswered();
        if (focus) find('counter').focus();
    }

    function finish() {
        const missing = answers.findIndex((answer, index) => !hasAnswer(questions[index], answer));
        if (missing !== -1) {
            current = missing;
            renderQuestion();
            find('validation').textContent = 'Masih ada soal yang belum dijawab.';
            return;
        }
        completed = true;
        const correct = questions.filter((question, index) => isCorrect(question, answers[index])).length;
        const passed = correct >= MIN_CORRECT_TO_PASS;
        find('score').textContent = String(Math.round(correct / questions.length * 100));
        find('correct').textContent = String(correct);
        find('incorrect').textContent = String(questions.length - correct);
        find('status').textContent = passed ? 'Lulus' : 'Belum Lulus';
        find('message').textContent = passed
            ? (continueLink ? 'Selamat, kamu dapat melanjutkan ke BAB berikutnya.' : 'Evaluasi selesai.')
            : `Kamu harus menjawab minimal ${MIN_CORRECT_TO_PASS} dari ${TOTAL_QUESTIONS} soal dengan benar ${continueLink ? 'untuk melanjutkan ke BAB berikutnya.' : 'untuk lulus kuis ini.'}`;
        saveResult(correct);
        if (continueLink) continueLink.hidden = !passed;
        find('form').hidden = true;
        find('results').hidden = false;
        find('result-title').focus();
    }

    find('code-fill').addEventListener('input', (event) => {
        if (completed || !isCodeFill(questions[current])) return;
        answers[current] = event.target.value;
        find('validation').textContent = '';
        updateAnswered();
    });

    find('form').addEventListener('submit', (event) => {
        event.preventDefault();
        if (completed) return;
        find('validation').textContent = '';
        if (current === questions.length - 1) finish();
        else { current += 1; renderQuestion(); }
    });
    find('previous').addEventListener('click', () => {
        if (current === 0 || completed) return;
        current -= 1;
        find('validation').textContent = '';
        renderQuestion();
    });
    find('retry').addEventListener('click', () => {
        answers = Array(questions.length).fill(null);
        current = 0;
        completed = false;
        clearResults();
        find('form').hidden = false;
        find('validation').textContent = '';
        renderQuestion();
    });
    find('instructions-toggle').addEventListener('click', () => {
        const instructions = root.querySelector('#quiz-instructions');
        instructions.hidden = !instructions.hidden;
        find('instructions-toggle').setAttribute('aria-expanded', String(!instructions.hidden));
    });
    const lockedReason = navigation?.querySelector('[data-quiz-next-reason]');
    if (lockedReason) lockedReason.textContent = `Kamu harus menjawab minimal ${MIN_CORRECT_TO_PASS} dari ${TOTAL_QUESTIONS} soal dengan benar untuk melanjutkan.`;
    updateNavigation();
    clearResults();
    renderQuestion(false);
    find('loading').hidden = true;
    find('instructions-toggle').hidden = false;
    find('form').hidden = false;
});
