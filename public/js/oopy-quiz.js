// Quiz state exists only while this page is open; no storage or server submission.
document.querySelectorAll('[data-oopy-quiz]').forEach((root) => {
    const find = (name) => root.querySelector(`[data-quiz="${name}"]`);
    const questions = JSON.parse(find('questions').textContent);
    let answers = Array(questions.length).fill(null);
    let current = 0;
    let completed = false;
    const letter = (index) => String.fromCharCode(65 + index);
    const isCodeFill = (question) => question.type === 'code_fill';
    const hasAnswer = (question, answer) => isCodeFill(question)
        ? typeof answer === 'string' && answer.trim() !== ''
        : answer !== null;
    const isCorrect = (question, answer) => isCodeFill(question)
        ? typeof answer === 'string' && answer.trim() === question.answer
        : answer === question.correct;
    const answerText = (question, answer) => isCodeFill(question)
        ? answer.trim()
        : `${letter(answer)}. ${question.options[answer]}`;
    const element = (tag, text, className) => {
        const node = document.createElement(tag);
        node.textContent = text;
        if (className) node.className = className;
        return node;
    };

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
            const action = isCodeFill(questions[missing]) ? 'Lengkapi kode' : 'Pilih jawaban';
            find('validation').textContent = `Jawaban belum lengkap. ${action} untuk soal ${missing + 1} sebelum menyelesaikan kuis.`;
            return;
        }
        completed = true;
        const correct = questions.filter((question, index) => isCorrect(question, answers[index])).length;
        find('score').textContent = `${correct} / ${questions.length}`;
        find('percentage').textContent = `${Math.round(correct / questions.length * 100)}%`;
        find('totals').textContent = `Jawaban benar: ${correct} · Jawaban salah: ${questions.length - correct}`;
        find('review').replaceChildren();
        questions.forEach((question, index) => {
            const passed = isCorrect(question, answers[index]);
            const item = document.createElement('li');
            item.className = passed ? 'is-correct' : 'is-incorrect';
            item.append(
                element('h4', `Soal ${index + 1}`),
                element('strong', passed ? 'Benar' : 'Salah', 'oopy-quiz-verdict'),
                element('p', question.question),
            );
            if (question.code) {
                const pre = element('pre', '');
                pre.tabIndex = 0;
                pre.setAttribute('aria-label', `Kode soal ${index + 1}`);
                const code = element('code', question.code, 'language-python');
                pre.append(code);
                item.append(pre);
                window.OopySyntax?.highlight(code);
            }
            item.append(
                element('p', `Jawaban kamu: ${answerText(question, answers[index])}`),
                element('p', `Jawaban benar: ${answerText(question, isCodeFill(question) ? question.answer : question.correct)}`),
                element('p', `Penjelasan: ${question.explanation}`),
            );
            find('review').append(item);
        });
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
        find('review').replaceChildren();
        find('results').hidden = true;
        find('form').hidden = false;
        find('validation').textContent = '';
        renderQuestion();
    });
    find('instructions-toggle').addEventListener('click', () => {
        const instructions = root.querySelector('#quiz-instructions');
        instructions.hidden = !instructions.hidden;
        find('instructions-toggle').setAttribute('aria-expanded', String(!instructions.hidden));
    });
    renderQuestion(false);
    find('loading').hidden = true;
    find('instructions-toggle').hidden = false;
    find('form').hidden = false;
});
