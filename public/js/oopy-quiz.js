// Official results and navigation progress come from Laravel. Direct material URLs remain public.

document.querySelectorAll('[data-oopy-quiz]').forEach((root) => {
    const find = (name) => root.querySelector(`[data-quiz="${name}"]`);
    const config = JSON.parse(find('config').textContent);
    const TOTAL_QUESTIONS = config.total_questions;
    const MIN_CORRECT_TO_PASS = config.minimum_correct;
    const navigation = root.closest('.material-article')?.querySelector('.material-navigation');
    const nextLink = navigation?.querySelector('[data-quiz-next-link]');
    const nextLocked = navigation?.querySelector('[data-quiz-next-locked]');
    const continueLink = find('continue');
    let previouslyPassed = config.authenticated && config.progress.next_unlocked === true;
    function updateNavigation() {
        if (nextLink) nextLink.hidden = !previouslyPassed;
        if (nextLocked) nextLocked.hidden = previouslyPassed;
    }
    updateNavigation();
    if (!config.authenticated) {
        find('loading').hidden = true;
        return;
    }
    const questions = JSON.parse(find('questions').textContent);
    if (questions.length !== TOTAL_QUESTIONS) {
        find('loading').textContent = 'Kuis belum tersedia. Silakan coba lagi nanti.';
        return;
    }
    let answers = Array(questions.length).fill(null);
    let current = 0;
    let completed = false;
    let submittedOnThisPage = false;
    let busy = false;
    let attempt = null;
    let starting = null;
    const letter = (index) => String.fromCharCode(65 + index);
    const isCodeFill = (question) => question.type === 'code_fill';
    const hasAnswer = (question, answer) => isCodeFill(question)
        ? typeof answer === 'string' && answer.trim() !== ''
        : answer !== null;
    const element = (tag, text, className) => {
        const node = document.createElement(tag);
        node.textContent = text;
        if (className) node.className = className;
        return node;
    };

    async function request(url, body) {
        let response;
        try {
            response = await fetch(url, {
                method: body === undefined ? 'GET' : 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                ...(body === undefined ? {} : { body: JSON.stringify(body) }),
            });
        } catch {
            throw new Error('Koneksi gagal. Jawaban tetap tersedia di halaman ini; silakan coba lagi.');
        }
        if (response.status === 401 || response.status === 419) {
            previouslyPassed = false;
            updateNavigation();
            if (continueLink) continueLink.hidden = true;
            throw new Error('Sesi berakhir. Silakan masuk kembali sebelum mengumpulkan kuis.');
        }
        const data = await response.json().catch(() => null);
        if (!response.ok) {
            const validation = data?.errors && Object.values(data.errors).flat()[0];
            throw new Error(validation || 'Kuis belum berhasil disimpan. Silakan coba lagi.');
        }
        if (!data) throw new Error('Respons server belum dapat dibaca. Silakan coba lagi.');
        return data;
    }

    function startAttempt() {
        if (attempt) return Promise.resolve(attempt);
        if (starting) return starting;
        starting = request(config.start_url, {}).then((data) => {
            attempt = data;
            return data;
        }).finally(() => { starting = null; });
        return starting;
    }

    function beginInteraction() {
        // Start lazily, never merely because a page was loaded/refreshed.
        startAttempt().catch((error) => { find('validation').textContent = error.message; });
    }

    function setBusy(value) {
        busy = value;
        find('form').setAttribute('aria-busy', String(value));
        find('next').disabled = value;
        find('previous').disabled = value || current === 0;
        find('form').querySelectorAll('input').forEach((input) => {
            if (input.type !== 'hidden') input.disabled = value || (input === find('code-fill') && !isCodeFill(questions[current]));
        });
        find('next').textContent = value ? 'Menyimpan hasil…' : (current === questions.length - 1 ? 'Selesai Kuis' : 'Soal Selanjutnya');
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

    function sizeCodeFill() {
        const input = find('code-fill');
        const minimum = input.classList.contains('is-inline') ? 12 : 18;
        input.style.setProperty('--answer-width', `${Math.max(minimum, input.value.length + 3)}ch`);
    }

    function renderQuestion(focus = true) {
        const question = questions[current];
        find('counter').textContent = `Soal ${current + 1} dari ${questions.length} soal`;
        find('question').textContent = question.question;
        find('code-card').hidden = !question.code;
        const codeFill = isCodeFill(question);
        const source = question.code || '';
        const blank = codeFill ? /_{3,}/.exec(source) : null;
        const before = blank ? source.slice(0, blank.index) : source;
        const after = blank ? source.slice(blank.index + blank[0].length) : '';
        find('code-before').textContent = before;
        find('code-after').textContent = after;
        // Highlight source fragments, preserving the input and its event listener.
        window.OopySyntax?.highlight(find('code-before'));
        window.OopySyntax?.highlight(find('code-after'));
        find('options').replaceChildren();
        find('options-group').hidden = codeFill;
        find('code-fill-group').hidden = !codeFill;
        find('code-fill').disabled = !codeFill;
        find('code-fill').hidden = !codeFill;
        find('code-fill').classList.toggle('is-inline', /\S/.test(after.split('\n')[0]));
        find('code-fill').value = codeFill ? answers[current] ?? '' : '';
        sizeCodeFill();
        if (!codeFill) question.options.forEach((option, index) => {
            const label = document.createElement('label');
            label.className = 'oopy-quiz-option';
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = `${root.id}-answer`;
            input.value = String(index);
            input.checked = answers[current] === index;
            input.addEventListener('change', () => {
                if (busy || completed) return;
                answers[current] = index;
                find('validation').textContent = '';
                updateAnswered();
                beginInteraction();
            });
            label.append(input, element('span', `${letter(index)}.`, 'oopy-quiz-letter'), element('span', option));
            find('options').append(label);
        });
        find('previous').disabled = current === 0;
        find('next').textContent = current === questions.length - 1 ? 'Selesai Kuis' : 'Soal Selanjutnya';
        updateAnswered();
        if (focus) find('counter').focus();
    }

    async function finish() {
        const missing = answers.findIndex((answer, index) => !hasAnswer(questions[index], answer));
        if (missing !== -1) {
            current = missing;
            renderQuestion();
            find('validation').textContent = 'Masih ada soal yang belum dijawab.';
            return;
        }
        setBusy(true);
        find('validation').textContent = 'Menyimpan jawaban dan hasil kuis…';
        try {
            const active = await startAttempt();
            const data = await request(active.submit_url, {
                answers: questions.map((question, index) => ({ question_id: question.question_id, answer: answers[index] })),
            });
            const result = data.result;
            if (!result || typeof result.passed !== 'boolean' || typeof data.progress?.next_unlocked !== 'boolean'
                || !Number.isFinite(result.score) || !Number.isInteger(result.correct_count) || !Number.isInteger(result.incorrect_count)) {
                throw new Error('Respons hasil belum dapat dibaca. Jawaban tetap tersedia; silakan coba lagi.');
            }
            completed = true;
            submittedOnThisPage = true;
            find('score').textContent = String(result.score);
            find('correct').textContent = String(result.correct_count);
            find('incorrect').textContent = String(result.incorrect_count);
            find('status').textContent = result.passed ? 'Lulus' : 'Belum Lulus';
            previouslyPassed = data.progress.next_unlocked === true;
            updateNavigation();
            find('message').textContent = result.passed
                ? 'Selamat, kamu dapat melanjutkan ke BAB berikutnya.'
                : `Kamu harus menjawab minimal ${MIN_CORRECT_TO_PASS} dari ${TOTAL_QUESTIONS} soal dengan benar untuk melanjutkan.`;
            if (continueLink) continueLink.hidden = !previouslyPassed;
            find('form').hidden = true;
            find('results').hidden = false;
            find('result-title').focus();
        } catch (error) {
            find('validation').textContent = error.message;
        } finally {
            setBusy(false);
        }
    }

    find('code-fill').addEventListener('input', (event) => {
        if (completed || busy || !isCodeFill(questions[current])) return;
        answers[current] = event.target.value;
        sizeCodeFill();
        find('validation').textContent = '';
        updateAnswered();
        beginInteraction();
    });

    find('form').addEventListener('submit', (event) => {
        event.preventDefault();
        if (completed || busy) return;
        find('validation').textContent = '';
        beginInteraction();
        if (current === questions.length - 1) finish();
        else { current += 1; renderQuestion(); }
    });
    find('previous').addEventListener('click', () => {
        if (current === 0 || completed || busy) return;
        current -= 1;
        find('validation').textContent = '';
        renderQuestion();
    });
    find('retry').addEventListener('click', () => {
        answers = Array(questions.length).fill(null);
        current = 0;
        completed = false;
        attempt = null;
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
    // Read current account progress on activation too, without touching browser storage.
    request(config.progress_url).then((progress) => {
        if (submittedOnThisPage) return;
        previouslyPassed = progress.next_unlocked === true;
        updateNavigation();
    }).catch((error) => { find('validation').textContent = error.message; });
});
