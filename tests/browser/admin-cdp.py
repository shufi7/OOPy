"""Admin QA using installed Chrome and its DevTools protocol; no pip/npm packages.

Run PHP fixture/router first (see docs/ADMIN_PANEL.md), then pass --browser PATH.
Only the isolated browser fixture accounts are used. Screenshots/results go in storage.
"""
import argparse
import base64
import hashlib
import json
import os
from pathlib import Path
import socket
import struct
import subprocess
import sys
import time
import urllib.request

sys.dont_write_bytecode = True


class DevTools:
    def __init__(self, url):
        from urllib.parse import urlparse
        parsed = urlparse(url)
        self.sock = socket.create_connection((parsed.hostname, parsed.port), timeout=15)
        nonce = base64.b64encode(os.urandom(16)).decode()
        self.sock.sendall((f"GET {parsed.path} HTTP/1.1\r\nHost: {parsed.netloc}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: {nonce}\r\nSec-WebSocket-Version: 13\r\n\r\n").encode())
        header = b""
        while not header.endswith(b"\r\n\r\n"):
            header += self.sock.recv(1)
        assert header.split(b"\r\n", 1)[0].split()[1] == b"101", header
        expected = base64.b64encode(hashlib.sha1((nonce + "258EAFA5-E914-47DA-95CA-C5AB0DC85B11").encode()).digest())
        assert expected.lower() in header.lower()
        self.index = 0

    def read(self, count):
        result = b""
        while len(result) < count:
            part = self.sock.recv(count - len(result))
            if not part:
                raise RuntimeError("DevTools disconnected")
            result += part
        return result

    def receive(self):
        chunks = b""
        while True:
            first, second = self.read(2)
            length = second & 127
            if length == 126:
                length = struct.unpack("!H", self.read(2))[0]
            elif length == 127:
                length = struct.unpack("!Q", self.read(8))[0]
            mask = self.read(4) if second & 128 else None
            payload = self.read(length)
            if mask:
                payload = bytes(value ^ mask[i % 4] for i, value in enumerate(payload))
            if first & 15 == 8:
                raise RuntimeError("DevTools closed")
            chunks += payload
            if first & 128:
                return json.loads(chunks)

    def call(self, method, **params):
        self.index += 1
        data = json.dumps({"id": self.index, "method": method, "params": params}).encode()
        mask = os.urandom(4)
        header = bytes([129])
        if len(data) < 126:
            header += bytes([128 | len(data)])
        elif len(data) < 65536:
            header += bytes([128 | 126]) + struct.pack("!H", len(data))
        else:
            header += bytes([128 | 127]) + struct.pack("!Q", len(data))
        self.sock.sendall(header + mask + bytes(value ^ mask[i % 4] for i, value in enumerate(data)))
        while True:
            message = self.receive()
            if message.get("id") == self.index:
                if "error" in message:
                    raise RuntimeError(message["error"])
                return message.get("result", {})

    def evaluate(self, expression):
        response = self.call("Runtime.evaluate", expression=expression, returnByValue=True, awaitPromise=True)
        if response.get("exceptionDetails"):
            raise RuntimeError(response["exceptionDetails"])
        return response.get("result", {}).get("value")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--browser", required=True)
    parser.add_argument("--base", default="http://127.0.0.1:8035")
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[2]
    output = root / "storage/framework/testing/admin-browser-results"
    output.mkdir(parents=True, exist_ok=True)
    process = subprocess.Popen([args.browser, "--headless=new", "--disable-gpu", "--no-first-run", "--no-default-browser-check", "--remote-debugging-port=9237", f"--user-data-dir={output / 'profile'}", "about:blank"], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    results = []
    try:
        for _ in range(100):
            try:
                targets = json.load(urllib.request.urlopen("http://127.0.0.1:9237/json", timeout=2))
                break
            except OSError:
                time.sleep(.1)
        client = DevTools(next(target["webSocketDebuggerUrl"] for target in targets if target["type"] == "page"))
        client.call("Page.enable")
        client.call("Runtime.enable")
        client.call("Network.enable")
        client.call("Emulation.setEmulatedMedia", features=[{"name": "prefers-reduced-motion", "value": "reduce"}])

        def visit(path):
            client.call("Page.navigate", url=args.base + path)
            for _ in range(100):
                ready = client.evaluate("document.readyState === 'complete' && !!document.body")
                if ready and client.evaluate("location.pathname") == path.split("?")[0]:
                    break
                time.sleep(.1)
            client.evaluate("document.fonts.ready.then(() => true)")
            assert "Internal Server Error" not in client.evaluate("document.body.innerText"), path

        visit("/login")
        client.evaluate("document.querySelector('[name=email]').value='admin.browser@example.test';document.querySelector('[name=password]').value='password123';document.querySelector('form').requestSubmit();true")
        time.sleep(.7)
        visit("/admin")
        assert client.evaluate("document.querySelector('h1').textContent") == "Dashboard"
        pages = ["/admin", "/admin/users", "/admin/users/2", "/admin/scores", "/admin/questions", "/admin/questions/create", "/admin/questions/1", "/admin/questions/1/edit", "/admin/materials", "/admin/materials/1/edit"]
        for path in pages:
            visit(path)
            for width in [360, 390, 768, 1024, 1440]:
                client.call("Emulation.setDeviceMetricsOverride", width=width, height=1000, deviceScaleFactor=1, mobile=False)
                client.evaluate("new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))")
                layout = client.evaluate("""(() => ({ width: innerWidth, scroll: document.documentElement.scrollWidth, font: getComputedStyle(document.body).fontFamily, unlabeled: [...document.querySelectorAll('input:not([type=hidden]):not([type=submit]):not([type=checkbox]),textarea,select')].filter(el=>!el.labels?.length&&!el.getAttribute('aria-label')).map(el=>el.name) }))()""")
                assert layout["scroll"] <= width, (path, layout)
                assert "Plus Jakarta Sans" in layout["font"], layout
                assert not layout["unlabeled"], (path, layout)
                results.append({"page": path, "width": width, "layout": "PASS"})
                if width in [390, 1440] and path in ["/admin", "/admin/scores", "/admin/materials/1/edit"]:
                    data = client.call("Page.captureScreenshot", format="png", captureBeyondViewport=False)["data"]
                    (output / (path.strip("/").replace("/", "-") + f"-{width}.png")).write_bytes(base64.b64decode(data))
            if path == "/admin":
                client.call("Emulation.setDeviceMetricsOverride", width=390, height=1000, deviceScaleFactor=1, mobile=False)
                client.evaluate("new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))")
                assert client.evaluate("document.querySelector('#admin-sidebar').hidden")
                client.evaluate("document.querySelector('#admin-menu-toggle').click()")
                assert client.evaluate("!document.querySelector('#admin-sidebar').hidden")
                client.call("Input.dispatchKeyEvent", type="keyDown", key="Escape", code="Escape", windowsVirtualKeyCode=27)
                assert client.evaluate("document.querySelector('#admin-sidebar').hidden && document.activeElement.id === 'admin-menu-toggle'")
                results.append({"interaction": "Mobile menu opens, Escape closes and returns focus", "status": "PASS"})
        # Check actual important color pairs and keyboard-visible focus.
        visit("/admin/scores")
        colors = client.evaluate("""(() => { const targets = ['.btn-brand','.admin-nav-link.is-active','th','td']; return targets.map(selector => {const el=document.querySelector(selector); const s=getComputedStyle(el); let bg=s.backgroundColor;let parent=el;while(bg==='rgba(0, 0, 0, 0)'&&parent.parentElement){parent=parent.parentElement;bg=getComputedStyle(parent).backgroundColor}return {selector,color:s.color,background:bg}}) })()""")
        def luminance(color):
            import re
            values = [float(value) / 255 for value in re.findall(r"[\d.]+", color)[:3]]
            linear = [value / 12.92 if value <= .04045 else ((value + .055) / 1.055) ** 2.4 for value in values]
            return sum(value * weight for value, weight in zip(linear, [.2126, .7152, .0722]))
        for color in colors:
            a, b = luminance(color["color"]), luminance(color["background"])
            ratio = (max(a, b) + .05) / (min(a, b) + .05)
            assert ratio >= 4.5, (color, ratio)
            results.append({"contrast": color["selector"], "ratio": round(ratio, 2), "status": "PASS"})
        client.call("Emulation.setDeviceMetricsOverride", width=720, height=600, deviceScaleFactor=1, mobile=False)
        assert client.evaluate("document.documentElement.scrollWidth <= innerWidth")
        results.append({"reflow": "720 CSS px (1440 viewport at 200% equivalent)", "status": "PASS"})
        sys.path.insert(0, str(Path(__file__).resolve().parent))
        from final_evaluation_cdp import run as final_evaluation_checks
        final_evaluation_checks(client, args.base, results)
        from questions_cdp import run as question_checks
        question_checks(client, args.base, results)
        from materials_cdp import run as material_checks
        material_checks(client, args.base, results)
        (output / "results.json").write_text(json.dumps(results, indent=2), encoding="utf-8")
        print(f"PASS: {len(results)} browser checks. Evidence: {output}")
    finally:
        process.terminate()
        try:
            process.wait(timeout=10)
        except subprocess.TimeoutExpired:
            process.kill()


if __name__ == "__main__":
    main()
