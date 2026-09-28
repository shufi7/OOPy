import { test, beforeEach, afterEach } from 'node:test';
import assert from 'node:assert/strict';
import { RuntimeManager } from '../../public/js/live-code/runtime-manager.js';

let manager;
let workers;
const originalWorker = globalThis.Worker;

beforeEach(() => {
    workers = [];
    globalThis.Worker = class {
        constructor() { this.sent = []; workers.push(this); }
        postMessage(data) { this.sent.push(data); }
        terminate() { this.terminated = true; }
        emit(data) { this.onmessage({ data }); }
    };
    manager = new RuntimeManager();
});
afterEach(() => {
    manager.dispose();
    globalThis.Worker = originalWorker;
});

test('one worker queues jobs and routes output/results only to their owner', () => {
    const a = [], b = [];
    manager.submit('a', { type: 'run' }, (message) => a.push(message));
    manager.submit('b', { type: 'check' }, (message) => b.push(message));
    assert.equal(workers.length, 1);
    workers[0].emit({ type: 'ready' });
    assert.equal(workers[0].sent.length, 1);
    const first = workers[0].sent[0].id;
    workers[0].emit({ type: 'output', id: first, chunks: ['A'] });
    assert.equal(a.at(-1).type, 'output');
    assert.equal(b.at(-1).type, 'queued');
    workers[0].emit({ type: 'done', id: first });
    assert.equal(workers[0].sent.length, 2);
    workers[0].emit({ type: 'output', id: first, chunks: ['stale'] });
    assert.equal(b.at(-1).type, 'started');
});

test('worker crash releases both active and queued editors', () => {
    const a = [], b = [];
    manager.submit('a', {}, (message) => a.push(message));
    workers[0].emit({ type: 'ready' });
    manager.submit('b', {}, (message) => b.push(message));
    workers[0].onerror({ preventDefault() {} });
    assert.equal(a.at(-1).type, 'error');
    assert.equal(b.at(-1).type, 'error');
    assert.equal(manager.active, null);
    assert.equal(manager.queue.length, 0);
    assert.equal(manager.state, 'error');
});

test('cancelling a queued job does not interrupt the active worker', () => {
    const a = [], b = [];
    manager.submit('a', {}, (message) => a.push(message));
    workers[0].emit({ type: 'ready' });
    manager.submit('b', {}, (message) => b.push(message));
    manager.cancel('b');
    assert.equal(b.at(-1).type, 'cancelled');
    assert.equal(manager.active.owner, 'a');
    assert.equal(workers[0].terminated, undefined);
});

test('stopping active work restarts one runtime and preserves queued jobs', () => {
    const a = [], b = [];
    manager.submit('a', {}, (message) => a.push(message));
    workers[0].emit({ type: 'ready' });
    manager.submit('b', {}, (message) => b.push(message));
    manager.cancel('a');
    assert.equal(a.at(-1).type, 'cancelled');
    assert.equal(workers[0].terminated, true);
    assert.equal(workers.length, 2);
    workers[0].emit({ type: 'done', id: 1 });
    assert.equal(b.at(-1).type, 'queued');
    workers[1].emit({ type: 'ready' });
    assert.equal(workers[1].sent[0].workspace, 'b');
});

test('load failure settles all queued jobs and a single retry recovers', () => {
    const messages = [];
    manager.submit('a', {}, (message) => messages.push(message));
    manager.submit('b', {}, (message) => messages.push(message));
    workers[0].emit({ type: 'init-error', message: 'CDN offline' });
    assert.equal(manager.state, 'error');
    assert.equal(messages.filter((m) => m.type === 'error').length, 2);
    manager.start();
    manager.start();
    assert.equal(workers.length, 2);
    workers[1].emit({ type: 'ready' });
    assert.equal(manager.state, 'ready');
});

test('timeout starts at execution, restarts runtime, and keeps the queue', (t) => {
    t.mock.timers.enable({ apis: ['setTimeout'] });
    const a = [], b = [];
    manager.submit('a', {}, (message) => a.push(message));
    t.mock.timers.tick(15000);
    assert.equal(a.at(-1).type, 'queued');
    workers[0].emit({ type: 'ready' });
    manager.submit('b', {}, (message) => b.push(message));
    t.mock.timers.tick(10000);
    assert.match(a.at(-1).message, /10 detik/);
    assert.equal(workers[0].terminated, true);
    workers[1].emit({ type: 'ready' });
    assert.equal(b.at(-1).type, 'started');
});
