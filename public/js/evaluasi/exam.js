import { createAttempt, finishAttempt, formatSeconds, hasAnswer, isCorrect, remainingSeconds } from './exam-model.js';
import { ExamStorage } from './exam-storage.js';
import { ExamTicker } from './exam-timer.js';

const root = document.querySelector('.oopy-final-exam');
if (root) initialize();

function initialize() {
    const find = (role) => root.querySelector(`[data-exam="${role}"]`);
    const config = JSON.parse(find('config').textContent);
    const { questions, settings, urls } = config;
    const page = root.dataset.examPage;
    const node = (tag, text, className) => {
        const element = document.createElement(tag);
        element.textContent = text;
        if (className) element.className = className;
        return element;
    };
    const warning = (message) => {
        find('storage-warning').hidden = false;
        find('storage-warning').textContent = message;
    };
    // Access through a driver so even a denied localStorage getter is caught.
    const storage = new ExamStorage({
        getItem: (key) => window.localStorage.getItem(key),
        setItem: (key, value) => window.localStorage.setItem(key, value),
    }, questions, settings, warning);
    let state = storage.state;
    let completing = false;
    let localResult = null;
    const ticker = new ExamTicker();
    const scoreText = (score) => score.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    const objectiveStatus = (record) => record.result.metThreshold ? 'Memenuhi ambang objektif' : 'Belum memenuhi ambang objektif';
    const resultUrl = (id) => {
        const target = new URL(urls.results, location.href);
        target.searchParams.set('attempt', id);
        return target.href;
    };
    const save = () => {
        const success = storage.save(state);
        state = storage.state;
        if (find('save-status')) find('save-status').textContent = success ? 'Jawaban disimpan otomatis.' : 'Perubahan terbaru belum tersimpan.';
        return success;
    };

    function complete() {
        if (completing || !state.active) return;
        completing = true;
        const id = state.active.id;
        ticker.stop();
        find('finish-dialog')?.close();
        state = finishAttempt(state, questions, Date.now());
        const saved = save();
        localResult = state.history.find((record) => record.id === id);
        if (page === 'exam') {
            if (saved) location.assign(resultUrl(id));
            else {
                find('workspace').hidden = true;
                find('local-results').hidden = false;
                const r = localResult.result;
                find('local-summary').textContent = `Nilai objektif ${scoreText(r.score)}; ${r.correct} dari ${r.objectiveTotal} benar. ${objectiveStatus(localResult)}. Uraian ${r.essaysFilled} dari ${r.essayTotal} terisi — Belum dinilai.`;
                find('retry-save').focus();
            }
        } else {
            warning(saved ? 'Sesi yang melewati deadline telah diselesaikan otomatis. Hasil tersedia di riwayat.' : 'Sesi telah selesai, tetapi hasil terbaru belum tersimpan. Pertahankan halaman ini tetap terbuka.');
            completing = false;
            renderPage();
            ticker.start(tick);
        }
    }

    function ensureActive() {
        if (!state.active) return false;
        if (remainingSeconds(state.active, Date.now()) === 0) { complete(); return false; }
        return true;
    }

    function availability() {
        const active = Boolean(state.active);
        const start = find('start');
        if (start) {
            start.hidden = active;
            start.disabled = active;
            start.textContent = state.history.length ? 'Ulangi Evaluasi' : 'Mulai Evaluasi';
        }
        root.querySelectorAll('[data-exam="resume"]').forEach((link) => { link.hidden = !active; });
        if (find('availability')) {
            const text = active ? 'Masih ada sesi aktif. Lanjutkan pengerjaan; deadline tidak diulang.' : '';
            find('availability').textContent = text;
        }
    }

    function openStart() {
        state = storage.state;
        if (state.active) { location.assign(urls.exam); return; }
        find('start-dialog').showModal();
    }
    find('start')?.addEventListener('click', openStart);
    find('start-cancel').addEventListener('click', () => { find('start-dialog').close(); find('start')?.focus(); });
    find('start-confirm').addEventListener('click', () => {
        // Recheck after the dialog: another tab may have started/completed a session.
        state = storage.read();
        if (state.active) { location.assign(urls.exam); return; }
        const previous = state;
        state = { ...state, active: createAttempt(questions, settings, Date.now(), `session-${crypto.randomUUID()}`) };
        if (save()) location.assign(urls.exam);
        else {
            // Starting requires persistent handoff to the exam URL.
            storage.state = previous;
            state = previous;
            find('start-dialog').close();
            warning('Evaluasi belum dimulai karena sesi tidak dapat disimpan. Izinkan penyimpanan browser, kosongkan ruang jika penuh, lalu coba lagi.');
            availability();
        }
    });

    function history() {
        const rows = state.history;
        find('history-empty').hidden = rows.length > 0;
        find('history-table').hidden = rows.length === 0;
        find('history').replaceChildren();
        for (const record of rows) {
            const row = document.createElement('tr');
            const date = document.createElement('td');
            const link = node('a', new Date(record.finishedAt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }));
            link.href = resultUrl(record.id);
            date.append(link);
            row.append(date, node('td', String(record.result.correct)), node('td', String(record.result.incorrect)),
                node('td', scoreText(record.result.score)), node('td', `${record.result.essaysFilled} / ${record.result.essayTotal} — Belum dinilai`));
            const status = document.createElement('td');
            status.append(node('span', record.timedOut ? 'Waktu habis' : objectiveStatus(record), 'exam-tag'));
            if (record.timedOut) status.append(node('p', objectiveStatus(record), 'exam-muted'));
            row.append(status);
            find('history').append(row);
        }
    }

    function progress() {
        const attempt = state.active;
        if (!attempt) return;
        const answered = questions.filter((q) => hasAnswer(q, attempt.answers[q.id])).length;
        find('progress-text').textContent = `${answered} dari ${questions.length} soal terjawab`;
        find('progress').value = answered;
        find('progress').max = questions.length;
        for (const [index, button] of [...find('numbers').children].entries()) {
            const q = questions[index];
            const filled = hasAnswer(q, attempt.answers[q.id]);
            const marked = attempt.review.includes(q.id);
            button.classList.toggle('answered', filled);
            button.classList.toggle('marked', marked);
            if (index === attempt.current) button.setAttribute('aria-current', 'step');
            else button.removeAttribute('aria-current');
            const status = `${filled ? 'Sudah dijawab' : 'Belum dijawab'}${marked ? ', ditandai untuk ditinjau' : ''}`;
            button.setAttribute('aria-label', `Soal ${index + 1}, ${status}${index === attempt.current ? ', sedang aktif' : ''}`);
            button.title = status;
            button.querySelector('small').textContent = marked ? '⚑' : filled ? '✓' : '';
        }
        const q = questions[attempt.current];
        const marked = attempt.review.includes(q.id);
        find('mark').setAttribute('aria-pressed', String(marked));
        find('mark').textContent = marked ? 'Hapus Tanda Tinjauan' : 'Tandai untuk Ditinjau';
        find('answer-status').textContent = `${hasAnswer(q, attempt.answers[q.id]) ? 'Soal sudah dijawab' : 'Soal belum dijawab'}${marked ? ' · Ditandai untuk ditinjau' : ''}`;
    }

    function sizeCodeAnswer() {
        const input = find('code-answer');
        const minimum = input.classList.contains('is-inline') ? 12 : 18;
        input.style.setProperty('--answer-width', `${Math.max(minimum, input.value.length + 3)}ch`);
    }

    function question(focus = true) {
        if (!ensureActive()) return;
        const attempt = state.active;
        const q = questions[attempt.current];
        find('counter').textContent = `Soal ${attempt.current + 1} dari ${questions.length}`;
        find('type').textContent = { multiple_choice: 'Pilihan Ganda', code_fill: 'Isian Kode', essay: 'Uraian Singkat' }[q.type];
        find('question').textContent = q.question;
        find('code-card').hidden = !q.code;
        const source = q.code || '';
        const blank = /_{3,}/.exec(source);
        const before = blank ? source.slice(0, blank.index) : source;
        const after = blank ? source.slice(blank.index + blank[0].length) : '';
        find('code-before').textContent = before;
        find('code-after').textContent = after;
        find('code-answer').classList.toggle('is-inline', /\S/.test(after.split('\n')[0]));
        // Highlight the source on either side; never replace the live input node.
        window.OopySyntax?.highlight(find('code-before'));
        window.OopySyntax?.highlight(find('code-after'));
        find('options-group').hidden = q.type !== 'multiple_choice';
        find('code-group').hidden = q.type !== 'code_fill';
        find('essay-group').hidden = q.type !== 'essay';
        find('code-answer').disabled = q.type !== 'code_fill';
        find('essay-answer').disabled = q.type !== 'essay';
        find('code-answer').value = q.type === 'code_fill' ? attempt.answers[q.id] || '' : '';
        sizeCodeAnswer();
        find('essay-answer').value = q.type === 'essay' ? attempt.answers[q.id] || '' : '';
        find('options').replaceChildren();
        if (q.type === 'multiple_choice') for (const [index, option] of q.options.entries()) {
            const label = node('label', '', 'exam-option');
            const input = document.createElement('input');
            input.type = 'radio'; input.name = 'final-exam-answer'; input.value = String(index);
            input.checked = attempt.answers[q.id] === index;
            input.addEventListener('change', () => answer(index));
            label.append(input, node('span', `${String.fromCharCode(65 + index)}. ${option}`));
            find('options').append(label);
        }
        find('previous').disabled = attempt.current === 0;
        find('next').textContent = attempt.current === questions.length - 1 ? 'Selesai Evaluasi' : 'Soal Selanjutnya';
        progress();
        if (focus) find('counter').focus();
    }

    function answer(value) {
        if (!ensureActive()) return;
        const q = questions[state.active.current];
        state.active.answers[q.id] = value;
        save();
        progress();
    }

    function go(index) {
        if (!ensureActive() || index < 0 || index >= questions.length) return;
        state.active.current = index;
        save();
        if (innerWidth < 992) find('number-panel').open = false;
        question();
        find('counter').scrollIntoView({ block: 'center', behavior: 'instant' });
    }

    function openFinish() {
        if (!ensureActive()) return;
        const answered = questions.filter((q) => hasAnswer(q, state.active.answers[q.id])).length;
        find('finish-summary').textContent = `${answered} soal terjawab, ${questions.length - answered} soal belum dijawab. Sisa waktu ${formatSeconds(remainingSeconds(state.active, Date.now()))}. Yakin ingin mengumpulkan?`;
        find('finish-dialog').showModal();
    }

    function exam() {
        const active = Boolean(state.active);
        find('workspace').hidden = !active;
        find('no-session').hidden = active || Boolean(localResult);
        if (!active) return;
        const grid = find('numbers');
        grid.replaceChildren();
        questions.forEach((q, index) => {
            const button = node('button', String(index + 1), 'exam-number');
            button.type = 'button';
            const symbol = node('small', ''); symbol.setAttribute('aria-hidden', 'true'); button.append(symbol);
            button.addEventListener('click', () => go(index));
            grid.append(button);
        });
        find('number-panel').open = innerWidth >= 992;
        question(false);
    }

    if (page === 'exam') {
        find('previous').addEventListener('click', () => go(state.active?.current - 1));
        find('next').addEventListener('click', () => state.active?.current === questions.length - 1 ? openFinish() : go(state.active?.current + 1));
        find('code-answer').addEventListener('input', (event) => { sizeCodeAnswer(); answer(event.target.value); });
        find('essay-answer').addEventListener('input', (event) => answer(event.target.value));
        find('mark').addEventListener('click', () => {
            if (!ensureActive()) return;
            const id = questions[state.active.current].id;
            state.active.review = state.active.review.includes(id) ? state.active.review.filter((item) => item !== id) : [...state.active.review, id];
            save(); progress();
        });
        find('finish').addEventListener('click', openFinish);
        find('finish-confirm').addEventListener('click', complete);
        find('finish-cancel').addEventListener('click', () => { find('finish-dialog').close(); find('finish').focus(); });
        find('retry-save').addEventListener('click', () => { if (save()) location.assign(resultUrl(localResult.id)); });
        const desktop = matchMedia('(min-width: 992px)');
        desktop.addEventListener('change', () => { find('number-panel').open = desktop.matches; });
    }

    function results() {
        const id = new URL(location.href).searchParams.get('attempt');
        const record = id ? state.history.find((entry) => entry.id === id) : state.history[0];
        find('no-result').hidden = Boolean(record);
        find('results').hidden = !record;
        if (!record) return;
        const r = record.result;
        find('completion-reason').textContent = record.timedOut ? 'Waktu habis' : 'Dikumpulkan';
        find('score').textContent = scoreText(r.score);
        find('objective-status').textContent = objectiveStatus(record);
        find('correct').textContent = `${r.correct} dari ${r.objectiveTotal}`;
        find('incorrect').textContent = `${r.incorrect} dari ${r.objectiveTotal}`;
        find('essays').textContent = `${r.essaysFilled} dari ${r.essayTotal} — Belum dinilai`;
        find('duration').textContent = formatSeconds(r.durationMs / 1000);
        const review = find('review-list'); review.replaceChildren();
        for (const [index, q] of questions.entries()) {
            const item = node('section', '', 'exam-review-item');
            item.append(node('h3', `Soal ${index + 1}`), node('p', q.question));
            const value = record.answers[q.id];
            const text = q.type === 'multiple_choice' ? q.options[value] : value;
            item.append(node('p', `Jawaban kamu: ${hasAnswer(q, value) ? text : 'Belum diisi'}`));
            if (q.type === 'essay') item.append(node('span', 'Belum dinilai', 'exam-tag'));
            else {
                item.append(node('span', isCorrect(q, value) ? 'Benar' : 'Tidak benar / belum dijawab', 'exam-tag'));
                item.append(node('p', `Jawaban benar: ${q.type === 'multiple_choice' ? q.options[q.correct] : q.answer}`));
            }
            review.append(item);
        }
    }
    find('review-open')?.addEventListener('click', () => {
        find('review').open = true;
        find('review').querySelector('summary').focus();
        find('review').scrollIntoView({ behavior: 'instant' });
    });

    function renderPage() {
        if (page === 'intro') history();
        if (page === 'exam') exam();
        if (page === 'results') results();
        availability();
    }

    function tick() {
        if (state.active && remainingSeconds(state.active, Date.now()) === 0) { complete(); return; }
        if (page === 'exam' && state.active) {
            const seconds = remainingSeconds(state.active, Date.now());
            find('timer').textContent = formatSeconds(seconds);
            find('timer').setAttribute('aria-label', `Sisa waktu ${formatSeconds(seconds)}`);
            const level = seconds <= 60 ? 'one' : seconds <= 300 ? 'five' : '';
            find('timer').closest('.exam-timer').dataset.warning = level;
            const message = level === 'one' ? 'Satu menit terakhir. Tinjau dan selesaikan jawabanmu.' : level === 'five' ? 'Sisa waktu lima menit atau kurang.' : '';
            if (find('timer-warning').textContent !== message) find('timer-warning').textContent = message;
        }
        availability();
    }

    window.addEventListener('storage', (event) => {
        if (event.key !== settings.storage_key) return;
        const prior = state.active?.id;
        state = storage.read(); storage.state = state;
        if (page === 'exam' && prior && state.history.some((record) => record.id === prior)) { location.assign(resultUrl(prior)); return; }
        renderPage(); tick();
    });
    window.addEventListener('pagehide', () => ticker.stop());
    window.addEventListener('pageshow', (event) => { if (event.persisted) { state = storage.read(); storage.state = state; renderPage(); ticker.start(tick); } });
    root.querySelectorAll('.exam-code code.language-python').forEach((code) => window.OopySyntax?.highlight(code));
    if (state.active && remainingSeconds(state.active, Date.now()) === 0) complete();
    renderPage();
    ticker.start(tick);
}
