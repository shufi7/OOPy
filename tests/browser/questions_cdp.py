"""Real bank-soal publication checks, restricted to the disposable browser fixture."""
import json
import time


def run(client, base, results):
    def wait(expression, label):
        deadline = time.monotonic() + 20
        while time.monotonic() < deadline:
            if client.evaluate(expression):
                return
            time.sleep(.1)
        raise AssertionError(label)

    def visit(path):
        marker = str(time.monotonic_ns())
        client.evaluate("window.__questionQaNavigation=" + json.dumps(marker))
        client.call("Page.navigate", url=base + path)
        wait("window.__questionQaNavigation !== " + json.dumps(marker)
             + " && location.pathname === " + json.dumps(path.split("?")[0])
             + " && document.readyState === 'complete'", "Page did not load: " + path)

    def login(email):
        client.evaluate("""(async()=>{
            const token=document.querySelector('meta[name="csrf-token"]')?.content;
            if(token)await fetch('/logout',{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':token,Accept:'application/json'}});
            return true;
        })()""")
        visit("/login")
        client.evaluate("document.querySelector('[name=email]').value=" + json.dumps(email)
                        + ";document.querySelector('[name=password]').value='password123';document.querySelector('form').requestSubmit();true")
        wait("location.pathname!='/login' && document.readyState==='complete' && !!document.querySelector('form[action$=\"/logout\"]')", "Login did not finish")

    def submit(selector, condition, label):
        marker = str(time.monotonic_ns())
        client.evaluate("window.__questionQaNavigation=" + json.dumps(marker)
                        + ";document.querySelector(" + json.dumps(selector) + ").requestSubmit();true")
        wait("window.__questionQaNavigation !== " + json.dumps(marker)
             + " && document.readyState === 'complete' && (" + condition + ")", label)

    login("admin.browser@example.test")
    visit("/admin/questions?quiz_id=1")
    active = client.evaluate("[...document.querySelectorAll('input[name=\"question_ids[]\"]:checked')].map(el=>el.value)")
    assert len(active) == 5, active
    original = active[0]
    # Always revise the currently published first question, so fixture reuse is safe.
    visit("/admin/questions/" + original + "/edit")
    tag = "QA-Soal-" + str(time.monotonic_ns())
    client.evaluate("document.querySelector('[name=question_text]').value += " + json.dumps("\n" + tag) + ";true")
    submit("form.admin-form", "location.pathname.match(/^\\/admin\\/questions\\/\\d+$/) && document.body.innerText.includes(" + json.dumps(tag) + ")", "Question draft did not save")
    draft = client.evaluate("location.pathname.split('/').pop()")
    assert draft != original
    assert client.evaluate("document.body.innerText.includes('Draft') && document.body.innerText.includes('Pratinjau Soal')")
    results.append({"interaction": "Admin revises an active question through its form and previews the saved draft", "status": "PASS"})

    visit("/admin/questions?quiz_id=1")
    selected = [draft if question == original else question for question in active]
    client.evaluate("document.querySelectorAll('input[name=\"question_ids[]\"]').forEach(el=>el.checked=" + json.dumps(selected) + ".includes(el.value));true")
    types = client.evaluate("[...document.querySelectorAll('input[name=\"question_ids[]\"]:checked')].map(el=>el.closest('tr').cells[3].textContent.trim())")
    assert types.count("Pilihan ganda") == 3 and types.count("Isian kode") == 2, types
    submit('form[action$="/quizzes/1/publish"]', "document.body.innerText.includes('Paket soal diterbitkan')", "Question publication did not finish")
    published = client.evaluate("[...document.querySelectorAll('input[name=\"question_ids[]\"]:checked')].map(el=>el.value)")
    assert set(published) == set(selected), (published, selected)
    results.append({"interaction": "Admin publishes a valid 3-choice + 2-code package replacing the original revision", "status": "PASS"})

    login("student.browser@example.test")
    visit("/materi/dasar-pemrograman-oop")
    wait("document.querySelector('[data-quiz=question]')?.textContent.includes(" + json.dumps(tag) + ")", "Learner did not receive the published question")
    questions = client.evaluate("JSON.parse(document.querySelector('[data-quiz=questions]').textContent)")
    assert len(questions) == 5 and str(questions[0]["question_id"]) == draft
    assert all("correct_answer" not in question and "answer" not in question and "explanation" not in question for question in questions)
    results.append({"interaction": "Learner chapter quiz renders the published revision without exposing grading keys", "status": "PASS"})
