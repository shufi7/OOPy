import { formatSeconds, hasAnswer, remainingSeconds } from './exam-model.js';
import { ExamTicker } from './exam-timer.js';

const root = document.querySelector('.oopy-final-exam');
if (root) initialize().catch((error) => {
    const warning = root.querySelector('[data-exam="storage-warning"]');
    warning.hidden = false;
    warning.textContent = error.message || 'Evaluasi belum dapat dimuat. Muat ulang untuk mencoba lagi.';
});

async function initialize() {
    const find = (role) => root.querySelector(`[data-exam="${role}"]`);
    const config = JSON.parse(find('config').textContent);
    let { questions } = config;
    const { settings, urls } = config;
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
    const api = async (url, method = 'GET', body) => {
        const response = await fetch(url, {
            method, credentials: 'same-origin', cache: 'no-store',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            ...(body === undefined ? {} : { body: JSON.stringify(body) }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.message || 'Penyimpanan server belum tersedia. Jawaban tetap berada pada browser; coba lagi.');
        return data;
    };
    const initial = await api(urls.state);
    let state = { active: initial.active, history: initial.history };
    let serverOffset = initial.serverNow - Date.now();
    const serverTime = () => Date.now() + serverOffset;
    const cacheKey = (id) => `${settings.storage_key}.draft.${id}`;
    const restoreDraft = () => {
        if (!state.active) return;
        questions = state.active.questions;
        try {
            const draft = JSON.parse(localStorage.getItem(cacheKey(state.active.id)) || 'null');
            if (!draft) return;
            if (remainingSeconds(state.active, serverTime()) > 0 && draft.answers && typeof draft.answers === 'object') {
                for (const q of questions) {
                    const value = draft.answers[q.id];
                    if (value === null || (q.type === 'multiple_choice' ? Number.isInteger(value) && value >= 0 && value < q.options.length : typeof value === 'string' && value.length <= (q.type === 'essay' ? 10000 : 500))) state.active.answers[q.id] = value;
                }
            }
            state.active.current = Number.isInteger(draft.current) && draft.current >= 0 && draft.current < questions.length ? draft.current : 0;
            state.active.review = Array.isArray(draft.review) ? draft.review.filter((id) => questions.some((q) => q.id === id)) : [];
        } catch { warning('Draf browser tidak dapat dibaca. Jawaban yang sudah tersimpan pada server tetap tersedia.'); }
    };
    restoreDraft();
    let completing = false;
    let localResult = null;
    const ticker = new ExamTicker();
    const scoreText = (score) => Number(score).toLocaleString('id-ID', { maximumFractionDigits: 2 });
    const objectiveStatus = (record) => record.result.metThreshold ? 'Memenuhi ambang objektif' : 'Belum memenuhi ambang objektif';
    const resultUrl = (id) => {
        const target = new URL(urls.results, location.href);
        target.searchParams.set('attempt', id);
        return target.href;
    };
    let saveTimer;
    let saveChain = Promise.resolve();
    const persistAnswers = () => {
        if (!state.active) return Promise.resolve();
        const id = state.active.id;
        const answers = { ...state.active.answers };
        saveChain = saveChain.catch(() => {}).then(() => api(`${urls.start}/${encodeURIComponent(id)}/answers`, 'PUT', { answers }));
        return saveChain.then(() => {
            if (find('save-status')) find('save-status').textContent = 'Jawaban tersimpan pada server.';
        }).catch((error) => {
            if (find('save-status')) find('save-status').textContent = 'Perubahan belum tersimpan pada server.';
            warning(error.message);
        });
    };
    const save = () => {
        if (!state.active) return true;
        try { localStorage.setItem(cacheKey(state.active.id), JSON.stringify({ answers: state.active.answers, current: state.active.current, review: state.active.review })); }
        catch { warning('Draf browser belum tersimpan. Tetap di halaman ini sampai jawaban tersimpan pada server.'); }
        if (find('save-status')) find('save-status').textContent = 'Menyimpan jawaban…';
        clearTimeout(saveTimer);
        saveTimer = setTimeout(persistAnswers, 500);
        return true;
    };

    async function complete() {
        if (completing || !state.active) return;
        completing = true;
        const id = state.active.id;
        ticker.stop();
        find('finish-dialog')?.close();
        clearTimeout(saveTimer);
        try {
            await saveChain.catch(() => {});
            const data = await api(`${urls.start}/${encodeURIComponent(id)}/submit`, 'POST', { answers: state.active.answers });
            localResult = data.attempt;
            state.history = [localResult, ...state.history.filter((record) => record.id !== id)];
            state.active = null;
            try { localStorage.removeItem(cacheKey(id)); } catch {}
        } catch (error) {
            completing = false;
            warning(error.message);
            if (page === 'exam') {
                find('workspace').hidden = true;
                find('local-results').hidden = false;
                find('local-summary').textContent = 'Evaluasi belum dikumpulkan. Nilai belum diterbitkan.';
                find('retry-save').focus();
            }
            return;
        }
        if (page === 'exam') {
            location.assign(resultUrl(id));
        } else {
            warning('Sesi yang melewati deadline telah diselesaikan otomatis. Hasil tersedia di riwayat.');
            completing = false;
            renderPage();
            ticker.start(tick);
        }
    }

    function ensureActive() {
        if (!state.active) return false;
        if (remainingSeconds(state.active, serverTime()) === 0) { complete(); return false; }
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
        if (state.active) { location.assign(urls.exam); return; }
        find('start-dialog').showModal();
    }
    find('start')?.addEventListener('click', openStart);
    find('start-cancel').addEventListener('click', () => { find('start-dialog').close(); find('start')?.focus(); });
    find('start-confirm').addEventListener('click', async () => {
        find('start-confirm').disabled = true;
        try {
            const data = await api(urls.start, 'POST', {});
            state.active = data.attempt;
            serverOffset = data.serverNow - Date.now();
            location.assign(urls.exam);
        } catch (error) {
            find('start-dialog').close();
            warning(error.message);
        } finally { find('start-confirm').disabled = false; }
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
                node('td', scoreText(record.result.score)), node('td', `${record.result.essaysFilled} / ${record.result.essayTotal} — ${record.result.essayPoints === null ? 'Belum dinilai' : `${scoreText(record.result.essayPoints)} / 25 poin`}`));
            const status = document.createElement('td');
            status.append(node('span', record.timedOut ? 'Waktu habis' : objectiveStatus(record), 'exam-tag'));
            if (record.timedOut) status.append(node('p', objectiveStatus(record), 'exam-muted'));
            status.append(node('p', record.result.totalScore === null ? 'Total: menunggu penilaian uraian' : `Total: ${scoreText(record.result.totalScore)} — ${record.result.passed ? 'Lulus' : 'Belum lulus'}`, 'exam-muted'));
            row.append(status);
            find('history').append(row);
        }
        // Preserve access to previous browser-only practice without promoting
        // its untrusted scores into the official account history.
        const legacy = find('legacy-history');
        const legacyRows = find('legacy-rows');
        legacyRows.replaceChildren();
        try {
            const oldState = JSON.parse(localStorage.getItem(settings.storage_key) || 'null');
            if (Array.isArray(oldState?.history)) {
                for (const record of oldState.history.slice(0, 100)) {
                    const score = record?.result?.score;
                    if (!Number.isFinite(record?.finishedAt) || !Number.isFinite(score) || score < 0 || score > 100) continue;
                    const date = new Date(record.finishedAt);
                    if (!Number.isFinite(date.getTime())) continue;
                    const row = document.createElement('tr');
                    row.append(node('td', date.toLocaleString('id-ID')), node('td', scoreText(score)), node('td', 'Latihan lokal · belum diverifikasi'));
                    legacyRows.append(row);
                }
            }
        } catch { /* An unreadable legacy practice record never blocks the account exam. */ }
        legacy.hidden = legacyRows.children.length === 0;
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
        find('finish-summary').textContent = `${answered} soal terjawab, ${questions.length - answered} soal belum dijawab. Sisa waktu ${formatSeconds(remainingSeconds(state.active, serverTime()))}. Yakin ingin mengumpulkan?`;
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
        find('retry-save').addEventListener('click', complete);
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
        find('essays').textContent = `${r.essaysFilled} dari ${r.essayTotal} — ${r.essayPoints === null ? 'Belum dinilai' : `${scoreText(r.essayPoints)} / 25 poin`}`;
        find('duration').textContent = formatSeconds(r.durationMs / 1000);
        find('final-score').textContent = r.totalScore === null
            ? 'Uraian: Belum dinilai. Nilai objektif ini belum merupakan penilaian keseluruhan Evaluasi Akhir.'
            : `Nilai total: ${scoreText(r.totalScore)} / 100. ${r.passed ? 'Lulus' : 'Belum lulus'}. Objektif ${scoreText(r.objectivePoints)} / 75 poin + uraian ${scoreText(r.essayPoints)} / 25 poin.`;
        const review = find('review-list'); review.replaceChildren();
        for (const [index, q] of record.questions.entries()) {
            const item = node('section', '', 'exam-review-item');
            item.append(node('h3', `Soal ${index + 1}`), node('p', q.question));
            const value = record.answers[q.id];
            const text = q.type === 'multiple_choice' ? q.options[value] : value;
            item.append(node('p', `Jawaban kamu: ${hasAnswer(q, value) ? text : 'Belum diisi'}`));
            const feedback = record.feedback[q.id];
            if (q.type === 'essay') {
                item.append(node('span', feedback.essayScore === null ? 'Belum dinilai' : `${scoreText(feedback.essayScore)} / 5 poin`, 'exam-tag'));
                if (feedback.feedback) item.append(node('p', feedback.feedback));
            }
            else {
                item.append(node('span', feedback.correct ? 'Benar' : 'Tidak benar / belum dijawab', 'exam-tag'));
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
        if (state.active && remainingSeconds(state.active, serverTime()) === 0) { complete(); return; }
        if (page === 'exam' && state.active) {
            const seconds = remainingSeconds(state.active, serverTime());
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
        if (!state.active || event.key !== cacheKey(state.active.id)) return;
        restoreDraft();
        if (page === 'exam') question(false);
    });
    window.addEventListener('pagehide', () => { clearTimeout(saveTimer); ticker.stop(); });
    window.addEventListener('pageshow', (event) => { if (event.persisted) location.reload(); });
    root.querySelectorAll('.exam-code code.language-python').forEach((code) => window.OopySyntax?.highlight(code));
    if (state.active && remainingSeconds(state.active, serverTime()) === 0) complete();
    renderPage();
    ticker.start(tick);
}
