// Restricted syntax comparison only; learner code is never executed.
export function normalizeCode(source) {
    if (typeof source !== 'string' || /[\r\n\x00]/.test(source)) return null;
    const tokens = [];
    let rest = source.trim();
    while (rest) {
        const match = rest.match(/^(?:[A-Za-z_][A-Za-z_0-9]*|[.@=(),+])/);
        if (!match) return null;
        tokens.push(match[0]);
        rest = rest.slice(match[0].length).replace(/^[ \t]+/, '');
    }
    let position = 0;
    const keywords = new Set('False None True and as assert async await break class continue def del elif else except finally for from global if import in is lambda nonlocal not or pass raise return try while with yield'.split(' '));
    const identifier = token => typeof token === 'string' && /^[A-Za-z_][A-Za-z_0-9]*$/.test(token) && !keywords.has(token);
    const primary = () => {
        const token = tokens[position++];
        let node;
        if (token === '(') {
            node = expression();
            if (!node || tokens[position++] !== ')') return null;
        } else if (identifier(token)) node = ['name', token];
        else return null;
        while (['.', '('].includes(tokens[position])) {
            const operator = tokens[position++];
            if (operator === '.') {
                const name = tokens[position++];
                if (!identifier(name)) return null;
                node = ['attr', node, name];
            } else {
                const args = [];
                while (tokens[position] !== ')') {
                    const argument = expression();
                    if (!argument) return null;
                    args.push(argument);
                    if (tokens[position] !== ',') break;
                    position++;
                }
                if (tokens[position++] !== ')') return null;
                node = ['call', node, args];
            }
        }
        return node;
    };
    const expression = () => {
        let left = primary();
        while (left && tokens[position] === '+') {
            position++;
            const right = primary();
            if (!right) return null;
            left = ['add', left, right];
        }
        return left;
    };
    const prefix = ['return', '@'].includes(tokens[0]) ? tokens[position++] : null;
    let result = expression();
    if (!result) return null;
    if (tokens[position] === '=' && prefix === null) {
        if (!['name', 'attr'].includes(result[0])) return null;
        position++;
        const value = expression();
        if (!value) return null;
        result = ['assign', result, value];
    }
    return position === tokens.length ? JSON.stringify([prefix, result]) : null;
}

export function matchesCode(question, answer) {
    if (typeof question.answer !== 'string' || typeof answer !== 'string' || !answer.trim()) return false;
    if (/(?:def\s+|\.)(_{3,})/.test(question.code || '')) return answer.trim() === question.answer;
    const expected = normalizeCode(question.answer);
    return expected === null ? answer.trim() === question.answer.trim() : normalizeCode(answer) === expected;
}
