// Local Prism core + Python only. Plain, escaped source remains the fallback.
if (window.Prism?.languages.python) {
    // Prism also supports Python 2; in our Python 3 examples print/exec are built-ins.
    const python = window.Prism.languages.python;
    python.keyword = new RegExp(python.keyword.source.replace('|exec|', '|').replace('|print|', '|'), python.keyword.flags);
    python.builtin = new RegExp(`${python.builtin.source}|\\b(?:print|exec)\\b`, python.builtin.flags);
}

window.OopySyntax = {
    highlight(code) {
        if (!window.Prism?.languages.python) return;

        const source = code.textContent;
        try {
            window.Prism.highlightElement(code);
        } catch {
            code.textContent = source;
        }
    },
};

document.querySelectorAll('.oopy-material pre > code.language-python').forEach((code) => {
    window.OopySyntax.highlight(code);
});
