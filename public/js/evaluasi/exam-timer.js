// One ticker per document; displayed time always comes from the saved deadline.
export class ExamTicker {
    constructor(schedule = (fn, delay) => globalThis.setInterval(fn, delay), cancel = (id) => globalThis.clearInterval(id)) {
        this.schedule = schedule;
        this.cancel = cancel;
        this.interval = null;
    }

    start(tick) {
        this.stop();
        this.interval = this.schedule(tick, 1000);
        tick();
    }

    stop() {
        if (this.interval !== null) this.cancel(this.interval);
        this.interval = null;
    }
}
