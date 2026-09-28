// One worker for the entire page. Only the active job owns its output and timer.
export class RuntimeManager {
    constructor() {
        this.worker = null;
        this.state = 'idle';
        this.queue = [];
        this.active = null;
        this.listeners = new Set();
        this.sequence = 0;
    }

    subscribe(listener) {
        this.listeners.add(listener);
        listener(this.state);
        return () => this.listeners.delete(listener);
    }

    setState(state) {
        this.state = state;
        this.listeners.forEach((listener) => listener(state));
    }

    start() {
        if (this.worker) return;
        this.setState('loading');
        try {
            const worker = new Worker(new URL('./python-worker.js', import.meta.url));
            this.worker = worker;
            this.loadingTimer = setTimeout(() => this.fail('Python terlalu lama dimuat. Periksa koneksi lalu coba lagi.'), 90000);
            worker.onmessage = ({ data }) => {
                if (worker !== this.worker) return;
                if (data.type === 'ready') {
                    clearTimeout(this.loadingTimer);
                    this.setState('ready');
                    this.drain();
                } else if (data.type === 'init-error') {
                    this.fail(`Python gagal dimuat: ${data.message}`);
                } else if (this.active && data.id === this.active.id) {
                    if (['done', 'error'].includes(data.type)) {
                        clearTimeout(this.executionTimer);
                        const job = this.active;
                        this.active = null;
                        job.receive(data);
                        this.drain();
                    } else if (['output', 'results'].includes(data.type)) {
                        this.active.receive(data);
                    }
                }
            };
            worker.onerror = (event) => {
                event.preventDefault();
                if (worker === this.worker) this.fail('Worker Python berhenti secara tidak terduga. Coba lagi.');
            };
            worker.onmessageerror = () => {
                if (worker === this.worker) this.fail('Pesan dari runtime Python tidak dapat dibaca. Coba lagi.');
            };
        } catch (error) {
            this.fail(`Gagal memulai Python: ${error.message}`);
        }
    }

    submit(owner, payload, receive) {
        if (this.active?.owner === owner || this.queue.some((job) => job.owner === owner)) return;
        this.queue.push({ owner, payload, receive, id: ++this.sequence });
        receive({ type: 'queued' });
        this.start();
        this.drain();
    }

    drain() {
        if (this.state !== 'ready' || this.active || !this.queue.length) return;
        const job = this.queue.shift();
        this.active = job;
        job.receive({ type: 'started' });
        // Queue waiting and CDN loading do not consume the execution budget.
        this.executionTimer = setTimeout(() => this.cancel(job.owner,
            'Program dihentikan karena waktu eksekusi terlalu lama (maksimal 10 detik).'), 10000);
        try {
            this.worker.postMessage({ ...job.payload, id: job.id, workspace: job.owner });
        } catch (error) {
            this.fail(`Gagal mengirim program ke Python: ${error.message}`);
        }
    }

    cancel(owner, message = 'Program dihentikan. Kode kamu tetap tersimpan di editor.') {
        if (this.active?.owner === owner) {
            const job = this.active;
            this.active = null;
            this.terminate();
            this.setState('loading');
            job.receive({ type: 'cancelled', message });
            // Queued jobs survive a restart; cancelling a queued job never stops another editor.
            this.start();
        } else {
            const index = this.queue.findIndex((job) => job.owner === owner);
            if (index < 0) return;
            const [job] = this.queue.splice(index, 1);
            job.receive({ type: 'cancelled', message: 'Antrean dibatalkan. Kode tetap tersimpan di editor.' });
        }
    }

    terminate() {
        clearTimeout(this.loadingTimer);
        clearTimeout(this.executionTimer);
        this.worker?.terminate();
        this.worker = null;
    }

    fail(message) {
        this.terminate();
        const jobs = [this.active, ...this.queue].filter(Boolean);
        this.active = null;
        this.queue = [];
        this.setState('error');
        jobs.forEach((job) => job.receive({ type: 'error', message }));
    }

    dispose() {
        this.terminate();
        this.active = null;
        this.queue = [];
        this.listeners.clear();
        this.state = 'idle';
    }
}

export const runtimeManager = new RuntimeManager();
