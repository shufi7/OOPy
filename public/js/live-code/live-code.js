import { createLiveCode } from './live-code-instance.js';
import { runtimeManager } from './runtime-manager.js';

const instances = [];
const ids = new Set();
document.querySelectorAll('[data-live-code]').forEach((root) => {
    try {
        const config = JSON.parse(root.querySelector('[data-role="config"]').textContent);
        if (ids.has(config.id)) throw new Error(`ID latihan "${config.id}" dipakai lebih dari sekali. Gunakan id unik.`);
        ids.add(config.id);
        instances.push(createLiveCode(root, config));
    } catch (error) {
        root.querySelector('[data-role="editor-loading"]').textContent = error.message;
    }
});

if (instances.length) runtimeManager.start();

window.addEventListener('beforeunload', (event) => {
    if (instances.some((instance) => instance.isDirty())) {
        event.preventDefault();
        event.returnValue = '';
    }
});

window.addEventListener('pagehide', (event) => {
    if (event.persisted) return;
    instances.forEach((instance) => instance.unsubscribe());
    runtimeManager.dispose();
    // The document owns Monaco models; avoid rejecting language requests during navigation.
});
