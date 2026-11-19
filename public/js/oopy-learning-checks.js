// Short, ungraded checks are attached by existing section IDs so published
// material and optional section fields keep the same data contract.
// Activity data is rendered from the escaped configuration attached to each section.

function buildConceptDiagram(visualization) {
    const figure = document.createElement('figure');
    figure.className = `material-concept-diagram is-${visualization.layout === 'flow' ? 'flow' : 'comparison'}`;
    const caption = document.createElement('figcaption');
    caption.textContent = visualization.caption;

    const items = document.createElement(visualization.layout === 'flow' ? 'ol' : 'ul');
    items.className = 'material-concept-diagram-items';
    items.style.setProperty('--diagram-columns', String(visualization.items.length));
    items.setAttribute('aria-label', visualization.caption);

    visualization.items.forEach((item) => {
        const step = document.createElement('li');
        step.className = 'material-concept-diagram-item';
        const label = document.createElement('strong');
        label.textContent = item.label;
        const code = document.createElement('code');
        code.textContent = item.code;
        const detail = document.createElement('span');
        detail.textContent = item.detail;
        step.append(label, code, detail);
        items.append(step);
    });

    figure.append(caption, items);
    return figure;
}

function buildLearningCheck(section, config) {
    const slot = section.querySelector('[data-learning-check-slot]');
    if (!slot) return;

    const panel = document.createElement('section');
    panel.className = 'material-learning-check';
    panel.setAttribute('aria-labelledby', `${section.id}-check-title`);

    const label = document.createElement('p');
    label.className = 'material-learning-check-label';
    label.textContent = config.kind;

    const title = document.createElement('h3');
    title.id = `${section.id}-check-title`;
    title.textContent = 'Coba pahami';

    const prompt = document.createElement('p');
    prompt.className = 'material-learning-check-prompt';
    prompt.textContent = config.prompt;

    panel.append(label, title);
    if (config.visualization) panel.append(buildConceptDiagram(config.visualization));
    panel.append(prompt);

    if (config.code) {
        const code = document.createElement('pre');
        code.tabIndex = 0;
        code.setAttribute('aria-label', `Potongan kode untuk aktivitas: ${config.kind}`);
        const codeText = document.createElement('code');
        codeText.className = 'language-python';
        codeText.textContent = config.code;
        code.append(codeText);
        panel.append(code);
        window.OopySyntax?.highlight(codeText);
    }

    const form = document.createElement('form');
    form.className = 'material-learning-check-form';
    const fieldset = document.createElement('fieldset');
    const legend = document.createElement('legend');
    legend.textContent = 'Pilih jawaban, lalu periksa.';
    const choices = document.createElement('div');
    choices.className = 'material-learning-check-choices';

    config.options.forEach((option, index) => {
        const choice = document.createElement('label');
        choice.className = 'material-learning-check-choice';
        const input = document.createElement('input');
        input.type = 'radio';
        input.name = `${section.id}-learning-check`;
        input.value = String(index);
        const text = document.createElement('span');
        text.textContent = option.label;
        choice.append(input, text);
        choices.append(choice);
    });

    fieldset.append(legend, choices);
    const actions = document.createElement('div');
    actions.className = 'material-learning-check-actions';
    const checkButton = document.createElement('button');
    checkButton.type = 'submit';
    checkButton.className = 'material-learning-check-submit';
    checkButton.textContent = 'Periksa jawaban';
    const retryButton = document.createElement('button');
    retryButton.type = 'button';
    retryButton.className = 'material-learning-check-retry';
    retryButton.textContent = 'Coba lagi';
    retryButton.hidden = true;
    actions.append(checkButton, retryButton);

    const feedback = document.createElement('p');
    feedback.className = 'material-learning-check-feedback';
    feedback.setAttribute('role', 'status');
    feedback.setAttribute('aria-live', 'polite');
    feedback.tabIndex = -1;
    feedback.hidden = true;

    form.append(fieldset, actions, feedback);
    panel.append(form);
    slot.replaceChildren(panel);

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (form.dataset.checked === 'true') return;
        const selected = form.querySelector('input[type="radio"]:checked');
        if (!selected) {
            feedback.hidden = false;
            feedback.dataset.state = 'notice';
            feedback.textContent = 'Pilih satu jawaban terlebih dahulu.';
            return;
        }

        const answer = config.options[Number(selected.value)];
        form.dataset.checked = 'true';
        fieldset.disabled = true;
        checkButton.hidden = true;
        retryButton.hidden = false;
        feedback.hidden = false;
        feedback.dataset.state = answer.correct ? 'correct' : 'incorrect';
        feedback.textContent = `${answer.correct ? 'Benar.' : 'Belum tepat.'} ${answer.feedback}`;
        feedback.focus({ preventScroll: true });
    });

    retryButton.addEventListener('click', () => {
        delete form.dataset.checked;
        fieldset.disabled = false;
        form.reset();
        feedback.hidden = true;
        feedback.textContent = '';
        delete feedback.dataset.state;
        retryButton.hidden = true;
        checkButton.hidden = false;
        choices.querySelector('input')?.focus();
    });
}

window.OopyLearningChecks = { render: buildLearningCheck };

document.querySelectorAll('.oopy-material [data-material-section]').forEach((section) => {
    const slot = section.querySelector('[data-learning-check-slot]');
    const encoded = slot?.dataset.learningCheckConfig;
    if (!encoded) return;
    try {
        const config = JSON.parse(encoded);
        if (config && Array.isArray(config.options)) buildLearningCheck(section, config);
    } catch (error) {
        console.error('Konfigurasi aktivitas belajar tidak dapat dibaca.', error);
    }
});
