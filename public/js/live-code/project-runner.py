"""Generic project execution, loaded once by the browser worker (never by PHP)."""
import builtins
import importlib
import json
import os
import shutil
import sys
import traceback


def unsupported_input(prompt=''):
    raise RuntimeError('input() interaktif belum didukung. Gunakan variabel pada kode.')


def execute_project(files, entry_file, checker, checking, workspace):
    # Snapshot interpreter plumbing before each job. Workspace files, modules,
    # globals, streams, paths and environment must not leak into the next job.
    modules = dict(sys.modules)
    paths = list(sys.path)
    argv = list(sys.argv)
    environment = dict(os.environ)
    builtin_dict = vars(builtins)
    original_builtins = dict(builtin_dict)
    streams = sys.stdin, sys.stdout, sys.stderr
    cwd = os.getcwd()
    bytecode = sys.dont_write_bytecode
    directory = '/workspaces/' + workspace
    phase = 'Program'
    try:
        shutil.rmtree('/workspaces', ignore_errors=True)
        os.makedirs(directory)
        for name, source in files.items():
            path = directory + '/' + name
            os.makedirs(os.path.dirname(path), exist_ok=True)
            with open(path, 'w', encoding='utf-8') as handle:
                handle.write(source)

        sys.dont_write_bytecode = True
        os.chdir(directory)
        sys.path[:] = [directory, os.path.dirname(directory + '/' + entry_file)] + paths
        sys.argv[:] = [entry_file]
        builtins.input = unsupported_input
        importlib.invalidate_caches()
        namespace = {'__name__': '__main__', '__file__': directory + '/' + entry_file}
        exec(compile(files[entry_file], namespace['__file__'], 'exec'), namespace)
        results = None
        if checking:
            phase = 'Checker'
            # A separate dict exposes entry variables and imported objects to the
            # checker, while a learner variable called results cannot fake feedback.
            checks = dict(namespace)
            checks.pop('results', None)
            try:
                exec(compile(checker, '<pemeriksaan>', 'exec'), checks)
                results = checks.get('results', [
                    {'label': 'Pemeriksaan latihan', 'passed': True, 'feedback': ''},
                ])
            except AssertionError as error:
                results = [{'label': 'Pemeriksaan latihan', 'passed': False,
                            'feedback': str(error) or 'Perilaku program belum sesuai instruksi.'}]

            if not isinstance(results, list) or not results or len(results) > 200:
                raise ValueError('Checker harus menghasilkan 1–200 hasil pemeriksaan.')
            for result in results:
                if (not isinstance(result, dict)
                        or not isinstance(result.get('label'), str)
                        or type(result.get('passed')) is not bool
                        or not isinstance(result.get('feedback', ''), str)):
                    raise ValueError('Setiap hasil harus memiliki label (string), passed (bool), dan feedback (string).')
                result['label'] = result['label'][:1000]
                result['feedback'] = result.get('feedback', '')[:2000]
        return json.dumps({'results': results})
    except BaseException:
        return json.dumps({'error': phase + ' Python Error\n' + traceback.format_exc()[-100000:]})
    finally:
        sys.stdin, sys.stdout, sys.stderr = streams
        # Flush partial lines while this request still owns the output stream.
        sys.stdout.flush()
        sys.stderr.flush()
        builtin_dict.clear()
        builtin_dict.update(original_builtins)
        sys.path[:] = paths
        sys.argv[:] = argv
        sys.dont_write_bytecode = bytecode
        os.environ.clear()
        os.environ.update(environment)
        os.chdir(cwd)
        for name in list(sys.modules):
            if name not in modules:
                sys.modules.pop(name, None)
        sys.modules.update(modules)
        sys.path_importer_cache.clear()
        importlib.invalidate_caches()
        shutil.rmtree('/workspaces', ignore_errors=True)
