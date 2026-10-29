"""Material authoring checks for admin-cdp.py's isolated browser fixture.

Only the disposable fixture's material and two browser accounts are changed.
"""
import json
import time


def run(client, base, results):
    def wait(expression, label, seconds=20):
        deadline = time.monotonic() + seconds
        while time.monotonic() < deadline:
            if client.evaluate(expression):
                return
            time.sleep(.1)
        raise AssertionError(label)

    def visit(path):
        marker = str(time.monotonic_ns())
        client.evaluate("window.__materialQaNavigation=" + json.dumps(marker))
        client.call("Page.navigate", url=base + path)
        wait("window.__materialQaNavigation !== " + json.dumps(marker) + " && location.pathname === " + json.dumps(path.split("?")[0]) + " && document.readyState === 'complete'", "Material page did not load: " + path)
        client.evaluate("document.fonts.ready.then(() => true)")

    def logout():
        client.evaluate("""(async () => {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) await fetch('/logout', {method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json'}});
            return true;
        })()""")

    def login(email):
        logout()
        visit('/login')
        client.evaluate("document.querySelector('[name=email]').value=" + json.dumps(email) + ";document.querySelector('[name=password]').value='password123';document.querySelector('form').requestSubmit();true")
        wait("location.pathname !== '/login' && document.readyState === 'complete' && !!document.querySelector('form[action$=\"/logout\"]')", "Material QA login failed: " + email)

    stamp = str(time.monotonic_ns())
    title = 'Materi browser ' + stamp
    heading = 'Bagian browser ' + stamp
    code = 'class SensorQa:\n    def nilai(self):\n        return 42\n'
    output = 'Nilai sensor: 42'
    paragraph = 'Bagian browser fixture.\n\n<script>window.__materialXss=true</script>'
    edit_path = '/admin/materials/1/edit'
    preview_path = '/admin/materials/1/preview'

    def fill_form():
        client.evaluate("document.querySelector('[name=title]').value=" + json.dumps(title) + ";true")
        existing = client.evaluate("[...document.querySelectorAll('[data-material-section-form]')].some(el=>el.querySelector('input[name$=\"[title]\"]').value===" + json.dumps(heading) + ")")
        if not existing:
            client.evaluate("document.querySelector('#add-material-section').click();true")
        client.evaluate("""(() => {
            const section = [...document.querySelectorAll('[data-material-section-form]')].find(el=>el.querySelector('input[name$="[title]"]').value === HEADING) || document.querySelector('#material-sections').lastElementChild;
            const value = (name, text) => section.querySelector('[name$="['+name+']"]').value=text;
            value('title',HEADING);value('paragraphs',PARAGRAPH);value('code',CODE);value('output',OUTPUT);
            return true;
        })()""".replace('HEADING', json.dumps(heading)).replace('PARAGRAPH', json.dumps(paragraph)).replace('CODE', json.dumps(code)).replace('OUTPUT', json.dumps(output)))

    login('admin.browser@example.test')
    visit(edit_path)
    wait("!!document.querySelector('#add-material-section') && !!document.querySelector('#material-section-template')", 'Material editor did not initialize')
    public_path = client.evaluate("[...document.querySelectorAll('a')].find(a=>a.textContent.trim()==='Lihat materi terbit').pathname")
    fill_form()
    client.evaluate("const form=document.querySelector('#material-editor');form.requestSubmit(form.querySelector('button[formaction]'));true")
    wait("location.pathname === " + json.dumps(preview_path) + " && document.querySelector('#preview-title')?.textContent === " + json.dumps(title), 'Unsaved material preview did not open')
    assert client.evaluate("document.body.innerText.includes('Perubahan formulir belum disimpan')")
    assert client.evaluate("[...document.querySelectorAll('.material-section h2')].some(el=>el.textContent===" + json.dumps(heading) + ")")
    assert client.evaluate("typeof window.__materialXss === 'undefined'")
    not_saved = client.evaluate("(async()=>{const html=await fetch(" + json.dumps(edit_path) + ").then(r=>r.text());return new DOMParser().parseFromString(html,'text/html').querySelector('[name=title]').value!==" + json.dumps(title) + ";})()")
    assert not_saved, 'Preview unexpectedly saved material'
    results.append({'interaction': 'Material add-section control and unsaved preview work without writing or executing HTML', 'status': 'PASS'})

    # The real browser Back action returns to the authoring form. Refill if its cache did not retain fields.
    client.evaluate('history.back();true')
    wait("location.pathname === " + json.dumps(edit_path) + " && document.readyState === 'complete' && !!document.querySelector('#material-editor')", 'Back from material preview failed')
    fill_form()
    client.evaluate("document.querySelector('#material-editor').requestSubmit();true")
    wait("location.pathname === " + json.dumps(edit_path) + " && document.body.innerText.includes('Draf materi disimpan')", 'Material draft did not save')
    assert client.evaluate("document.querySelector('[name=title]').value === " + json.dumps(title))
    assert client.evaluate("[...document.querySelectorAll('textarea[name$=\"[code]\"]')].some(el=>el.value === " + json.dumps(code) + ")")
    client.evaluate("[...document.querySelectorAll('a')].find(a=>a.textContent.trim()==='Periksa draf tersimpan').click();true")
    wait("location.pathname === " + json.dumps(preview_path) + " && document.body.innerText.includes('Draf tersimpan.') && !!document.querySelector('form[action$=\"/publish\"]')", 'Saved material preview did not load')

    for width in [360, 390, 768, 1024, 1440]:
        client.call('Emulation.setDeviceMetricsOverride', width=width, height=1000, deviceScaleFactor=1, mobile=False)
        client.evaluate('new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve)))')
        metrics = client.evaluate("({width:innerWidth,scroll:document.documentElement.scrollWidth,tables:[...document.querySelectorAll('.material-table-wrapper')].every(el=>getComputedStyle(el).overflowX==='auto'),code:[...document.querySelectorAll('.material-code pre')].every(el=>el.scrollWidth <= el.clientWidth || getComputedStyle(el).overflowX==='auto')})")
        assert metrics['scroll'] <= width, ('material-preview', metrics)
        assert metrics['tables'] and metrics['code'], ('material-preview-local-scroll', metrics)
        results.append({'page': 'admin-material-preview', 'width': width, 'layout': 'PASS'})

    client.evaluate('window.scrollTo(0,0);document.activeElement.blur();true')
    client.call('Input.dispatchKeyEvent', type='keyDown', key='Tab', code='Tab', windowsVirtualKeyCode=9)
    client.call('Input.dispatchKeyEvent', type='keyUp', key='Tab', code='Tab', windowsVirtualKeyCode=9)
    focus = client.evaluate("({tag:document.activeElement.tagName,visible:document.activeElement.matches(':focus-visible'),outline:getComputedStyle(document.activeElement).outlineStyle})")
    assert focus['tag'] not in ['BODY', 'HTML'] and focus['visible'] and focus['outline'] != 'none', focus
    results.append({'interaction': 'Material preview supports keyboard Tab with a visible focus indicator', 'status': 'PASS'})

    client.evaluate("document.querySelector('form[action$=\"/publish\"]').requestSubmit();true")
    wait("location.pathname === " + json.dumps(edit_path) + " && document.body.innerText.includes('Materi dipublikasikan')", 'Material publication did not finish')
    logout()
    visit(public_path)
    assert client.evaluate("document.querySelector('h1').textContent === " + json.dumps(title))
    assert client.evaluate("[...document.querySelectorAll('.material-section h2')].some(el=>el.textContent === " + json.dumps(heading) + ")")
    assert client.evaluate("[...document.querySelectorAll('.material-code pre code')].some(el=>el.textContent === " + json.dumps(code) + ")")
    assert client.evaluate("document.body.innerText.includes(" + json.dumps(output) + ") && typeof window.__materialXss === 'undefined' && !!document.querySelector('[data-live-code]')")
    results.append({'interaction': 'Published material title, new section, exact Python code and output reach guests while legacy live coding remains', 'status': 'PASS'})
    login('student.browser@example.test')
    visit(public_path)
    assert client.evaluate("document.querySelector('h1').textContent === " + json.dumps(title) + " && document.body.innerText.includes(" + json.dumps(heading) + ")")
    results.append({'interaction': 'Published material also reaches the signed-in learner', 'status': 'PASS'})
