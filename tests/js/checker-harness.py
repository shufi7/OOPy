"""Execute fixtures against the embedded checkers using a temporary main.py."""
import contextlib
import io
import json
import os
from pathlib import Path
import sys
import tempfile

payload = json.load(sys.stdin)
output = []
with tempfile.TemporaryDirectory(prefix="oopy-checker-") as directory:
    original_cwd = os.getcwd()
    os.chdir(directory)
    try:
        for source in payload["sources"]:
            Path("main.py").write_text(source, encoding="utf-8")
            namespace = {"__name__": "__main__", "__file__": str(Path("main.py").resolve())}
            with contextlib.redirect_stdout(io.StringIO()):
                exec(compile(source, namespace["__file__"], "exec"), namespace)
                checks = dict(namespace)
                checks.pop("results", None)
                exec(compile(payload["checker"], "<pemeriksaan>", "exec"), checks)
            output.append(checks["results"])
    finally:
        os.chdir(original_cwd)
json.dump(output, sys.stdout)
