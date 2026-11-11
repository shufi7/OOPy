import { test } from 'node:test';
import assert from 'node:assert/strict';
import { publicationPreview } from '../../public/js/admin/question-publication.js';

test('publication preview follows the selected count and composition without rounding the weight', () => {
    for (const count of [1, 5, 6, 10, 20, 30]) {
        const types = Array.from({ length: count }, (_, index) => index % 2 ? 'code_fill' : 'multiple_choice');
        const preview = publicationPreview(types, 1, 30);
        assert.equal(preview.count, count);
        assert.equal(preview.multipleChoice, Math.ceil(count / 2));
        assert.equal(preview.codeFill, Math.floor(count / 2));
        assert.equal(preview.weight, 100 / count);
        assert.equal(preview.valid, true);
    }
});

test('empty, oversized and unsupported packages cannot be published and bounds are configurable', () => {
    assert.equal(publicationPreview([], 1, 30).weight, null);
    assert.equal(publicationPreview([], 1, 30).valid, false);
    assert.equal(publicationPreview(Array(31).fill('multiple_choice'), 1, 30).valid, false);
    assert.equal(publicationPreview(['essay'], 1, 30).valid, false);
    assert.equal(publicationPreview(['multiple_choice'], 2, 10).valid, false);
    assert.equal(publicationPreview(Array(6).fill('code_fill'), 1, 5).valid, false);
});
