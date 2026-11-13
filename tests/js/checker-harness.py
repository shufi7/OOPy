"""Execute each single/multi-file fixture with fresh project imports."""
import contextlib
import importlib
import io
import json
import os
from pathlib import Path
import sys
import tempfile
import traceback

payload = json.load(sys.stdin)
output = []
for source in payload["sources"]:
    files = {"main.py": source} if isinstance(source, str) else source
    modules = dict(sys.modules)
    paths = list(sys.path)
    cwd = os.getcwd()
    bytecode = sys.dont_write_bytecode
    profiler = sys.getprofile()
    with tempfile.TemporaryDirectory(prefix="oopy-checker-") as directory:
        root = Path(directory).resolve()
        stdout = io.StringIO()
        report = {}
        try:
            for name, code in files.items():
                path = (root / name).resolve()
                if not path.is_relative_to(root):
                    raise ValueError("Fixture paths must stay inside the temporary workspace")
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_text(code, encoding="utf-8")
            sys.path.insert(0, str(root))
            sys.dont_write_bytecode = True
            os.chdir(root)
            importlib.invalidate_caches()
            namespace = {"__name__": "__main__", "__file__": str(root / "main.py")}
            observations = []

            def observe(frame, event, result):
                if (len(observations) < 1000 and event == 'return' and frame.f_code.co_name in ('__init__', 'status')
                        and frame.f_back is not None and frame.f_back.f_code.co_filename == namespace['__file__']):
                    obj = frame.f_locals.get(frame.f_code.co_varnames[0]) if frame.f_code.co_argcount else None
                    if obj is not None:
                        observations.append((frame.f_code.co_name, obj, result, tuple(
                            getattr(obj, name, None) for name in ('nama', 'lokasi', 'tinggi_air_cm'))))
            with contextlib.redirect_stdout(stdout):
                try:
                    if set(files) == {'ekosistem.py', 'rawa.py', 'main.py'}:
                        sys.setprofile(observe)
                    exec(compile(files["main.py"], namespace["__file__"], "exec"), namespace)
                finally:
                    sys.setprofile(profiler)
                module_names = ["sungai", "rawa"]
                before = {name: dict(vars(sys.modules[name])) for name in module_names if name in sys.modules}
                checks = dict(namespace)
                checks.pop("results", None)
                checks["__oopy_output__"] = stdout.getvalue()[:12000]
                checks["__oopy_modules__"] = {name: sys.modules[name] for name in ("ekosistem", "rawa") if name in sys.modules}
                checks["__oopy_execution__"] = observations
                exec(compile(payload["checker"], "<pemeriksaan>", "exec"), checks)
            report["results"] = checks["results"]
            report["super_restored"] = all(
                ("super" in original) == ("super" in vars(sys.modules[name]))
                and ("super" not in original or original["super"] is vars(sys.modules[name])["super"])
                for name, original in before.items()
            )
        except Exception:
            report["error"] = traceback.format_exc()
        finally:
            sys.setprofile(profiler)
            report["output"] = stdout.getvalue()
            os.chdir(cwd)
            sys.path[:] = paths
            sys.dont_write_bytecode = bytecode
            for name in list(sys.modules):
                if name not in modules:
                    del sys.modules[name]
            sys.modules.update(modules)
            sys.path_importer_cache.clear()
            importlib.invalidate_caches()
        output.append(report)
json.dump(output, sys.stdout)
