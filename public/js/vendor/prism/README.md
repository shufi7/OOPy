Prism.js 1.30.0 (MIT). `prism.min.js` concatenates only the core and Python
grammar, in that order, with no plugins or other languages. One bundle keeps
the plain-text fallback safe when the highlighter asset cannot load.

Sources:
- https://cdn.jsdelivr.net/npm/prismjs@1.30.0/components/prism-core.min.js
- https://cdn.jsdelivr.net/npm/prismjs@1.30.0/components/prism-python.min.js
- https://github.com/PrismJS/prism/tree/v1.30.0

The file is served locally; no bundler or CDN request is needed at runtime.
The OOPy token theme is in `public/css/oopy/material/code.css`.
