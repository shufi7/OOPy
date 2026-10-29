"""Server-backed final evaluation browser checks, using admin-cdp.py's DevTools client.

This helper operates only on the disposable browser fixture accounts/database.
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
        client.evaluate("window.__finalQaNavigation=" + json.dumps(marker))
        client.call("Page.navigate", url=base + path)
        wait("window.__finalQaNavigation !== " + json.dumps(marker) + " && location.pathname === " + json.dumps(path.split("?")[0]) + " && document.readyState === 'complete'", "Page did not load: " + path)
        client.evaluate("document.fonts.ready.then(() => true)")

    def login(email):
        # Existing logged-in state is cleared with the real CSRF-protected logout.
        client.evaluate("""(async () => {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) await fetch('/logout', {method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':token,'Accept':'application/json'}});
            return true;
        })()""")
        visit("/login")
        client.evaluate("document.querySelector('[name=email]').value=" + json.dumps(email) + ";document.querySelector('[name=password]').value='password123';document.querySelector('form').requestSubmit();true")
        wait("location.pathname !== '/login' && document.readyState === 'complete' && !!document.querySelector('form[action$=\"/logout\"]')", "Login did not finish: " + email)

    def api(expression):
        return client.evaluate("(async()=>{" + expression + "})()")

    def layout(label):
        for width in [360, 390, 768, 1024, 1440]:
            client.call("Emulation.setDeviceMetricsOverride", width=width, height=1000, deviceScaleFactor=1, mobile=False)
            client.evaluate("new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))")
            metrics = client.evaluate("({width:innerWidth,scroll:document.documentElement.scrollWidth,unlabeled:[...document.querySelectorAll('input:not([type=hidden]):not([type=radio]),textarea,select')].filter(el=>!el.labels?.length&&!el.getAttribute('aria-label')).map(el=>el.name)})")
            assert metrics["scroll"] <= width, (label, metrics)
            assert not metrics["unlabeled"], (label, metrics)
            results.append({"page": label, "width": width, "layout": "PASS"})

    login("student.browser@example.test")
    visit("/materi/evaluasi-akhir")
    wait("!!document.querySelector('[data-exam=start]:not([disabled])') || !!document.querySelector('[data-exam=resume]:not([hidden])')", "Final evaluation not ready")
    assert client.evaluate("JSON.parse(document.querySelector('[data-exam=config]').textContent).questions.every(q=>!Object.hasOwn(q,'correct')&&!Object.hasOwn(q,'answer'))")
    # Finish a previous browser-run attempt, if this isolated fixture was reused.
    api("const s=await fetch('/evaluasi/attempts',{headers:{Accept:'application/json'}}).then(r=>r.json());if(s.active)await fetch('/evaluasi/attempts/'+s.active.id+'/submit',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({answers:s.active.answers})});return true;")
    visit("/materi/evaluasi-akhir")
    wait("!!document.querySelector('[data-exam=start]:not([disabled]):not([hidden])')", "New evaluation button not ready")
    layout("final-intro")
    client.evaluate("document.querySelector('[data-exam=start]').click();document.querySelector('[data-exam=start-confirm]').click();true")
    wait("location.pathname.endsWith('/ujian') && !!document.querySelector('[data-exam=workspace]:not([hidden])') && document.querySelectorAll('[data-exam=numbers] button').length===20", "Final exam did not start")
    assert client.evaluate("document.body.innerText.includes('Evaluasi akun: hasil disimpan')")
    active = api("return (await fetch('/evaluasi/attempts',{headers:{Accept:'application/json'}}).then(r=>r.json())).active;")
    assert active["official"] is True
    assert all("correct" not in q and "answer" not in q for q in active["questions"])
    client.evaluate("document.querySelector('[data-exam=options] input').click();true")
    wait("document.querySelector('[data-exam=save-status]').textContent.includes('tersimpan pada server')", "Objective answer did not autosave")
    # The Python fragment stays usable at all requested viewport sizes.
    client.evaluate("document.querySelectorAll('[data-exam=numbers] button')[10].click();true")
    code_value = 'self.nilai = nilai'
    client.evaluate("const field=document.querySelector('[data-exam=code-answer]');field.value=" + json.dumps(code_value) + ";field.dispatchEvent(new Event('input',{bubbles:true}));true")
    wait("document.querySelector('[data-exam=save-status]').textContent.includes('tersimpan pada server')", "Code answer did not autosave")
    layout("final-code-question")
    visit("/materi/evaluasi-akhir/ujian")
    wait("document.querySelector('[data-exam=code-answer]')?.value === " + json.dumps(code_value), "Code draft did not survive refresh")
    resumed = api("return (await fetch('/evaluasi/attempts',{headers:{Accept:'application/json'}}).then(r=>r.json())).active;")
    assert resumed["id"] == active["id"] and resumed["deadline"] == active["deadline"]
    # Add an essay and submit through the existing confirmation dialog.
    client.evaluate("document.querySelectorAll('[data-exam=numbers] button')[15].click();const field=document.querySelector('[data-exam=essay-answer]');field.value='Class merupakan rancangan; object merupakan instance, contohnya sensor pada sungai.';field.dispatchEvent(new Event('input',{bubbles:true}));document.querySelector('[data-exam=finish]').click();true")
    wait("document.querySelector('[data-exam=finish-dialog]').open", "Finish dialog did not open")
    client.evaluate("const button=document.querySelector('[data-exam=finish-confirm]');button.click();button.click();true")
    wait("location.pathname.endsWith('/hasil') && !!document.querySelector('[data-exam=results]:not([hidden])')", "Final results did not load")
    assert client.evaluate("document.querySelector('[data-exam=final-score]').textContent.includes('Belum dinilai')")
    record = api("return (await fetch('/evaluasi/attempts',{headers:{Accept:'application/json'}}).then(r=>r.json())).history.find(r=>r.id===" + json.dumps(active["id"]) + ");")
    assert record["result"]["totalScore"] is None and record["result"]["essayPoints"] is None
    layout("final-pending-results")
    results.append({"interaction": "Official final exam starts, autosaves, resumes identical deadline and submits once with essays pending", "status": "PASS"})

    login("admin.browser@example.test")
    visit("/admin/evaluations/" + active["id"])
    assert client.evaluate("document.querySelectorAll('input[name^=\"scores[\"]').length") == 5
    layout("admin-final-grading")
    client.evaluate("document.querySelectorAll('input[name^=\"scores[\"]').forEach(el=>el.value='4');document.querySelector('form[action*=\"/evaluations/\"]').requestSubmit();true")
    wait("document.body.innerText.includes('Penilaian uraian tersimpan')", "Manual essay grading did not finish")
    assert client.evaluate("!document.body.innerText.includes('Menunggu penilaian uraian')")
    login("student.browser@example.test")
    visit("/materi/evaluasi-akhir/hasil?attempt=" + active["id"])
    wait("document.querySelector('[data-exam=final-score]')?.textContent.includes('Nilai total:')", "Published total not visible to learner")
    graded = api("return (await fetch('/evaluasi/attempts',{headers:{Accept:'application/json'}}).then(r=>r.json())).history.find(r=>r.id===" + json.dumps(active["id"]) + ");")
    assert graded["result"]["essayPoints"] == 20
    assert graded["result"]["totalScore"] == graded["result"]["objectivePoints"] + 20
    results.append({"interaction": "Admin assigns bounded manual essay scores; learner receives valid objective + essay total", "status": "PASS"})
