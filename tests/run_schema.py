#!/usr/bin/env python3
"""Isolated integration test; requires PHP mysqli and MariaDB binaries."""
import pathlib, subprocess, tempfile, time, os
with tempfile.TemporaryDirectory(prefix='svm-schema-') as td:
    root=pathlib.Path(td); socket='33079'
    user=str(os.getuid())
    subprocess.run(['mariadb-install-db','--no-defaults','--datadir='+str(root/'db'),'--auth-root-authentication-method=normal','--skip-test-db','--user='+user],check=True,capture_output=True)
    with (root/'server.log').open('w') as log:
        db=subprocess.Popen(['mariadbd','--no-defaults','--datadir='+str(root/'db'),'--socket=','--bind-address=127.0.0.1','--port='+socket,'--pid-file='+str(root/'db.pid'),'--user='+user],stdout=log,stderr=log)
        try:
            for _ in range(100):
                result=subprocess.run(['mariadb','--no-defaults','--protocol=tcp','-h127.0.0.1','-P'+socket,'-uroot','-e','SELECT 1'],capture_output=True)
                if result.returncode==0:break
                if db.poll() is not None:raise RuntimeError((root/'server.log').read_text())
                time.sleep(.1)
            else:raise RuntimeError('MariaDB startup timeout')
            subprocess.run(['php',str(pathlib.Path(__file__).with_name('auth_limit.php'))],check=True)
            subprocess.run(['php',str(pathlib.Path(__file__).with_name('update_schema.php')),socket],check=True)
            subprocess.run(['php',str(pathlib.Path(__file__).with_name('valuation_schema.php')),socket],check=True)
            subprocess.run(['php',str(pathlib.Path(__file__).with_name('catalogs.php')),socket],check=True)
            subprocess.run(['python3',str(pathlib.Path(__file__).with_name('measurement_http.py')),socket],check=True)
            subprocess.run(['python3',str(pathlib.Path(__file__).with_name('auth_http.py')),socket],check=True)
            subprocess.run(['python3',str(pathlib.Path(__file__).with_name('admin_http.py')),socket],check=True)
            subprocess.run(['python3',str(pathlib.Path(__file__).with_name('import_http.py')),socket],check=True)
            subprocess.run(['python3',str(pathlib.Path(__file__).with_name('public_map_http.py')),socket],check=True)
            subprocess.run(['python3',str(pathlib.Path(__file__).with_name('release_package.py')),socket],check=True)
            subprocess.run(['python3',str(pathlib.Path(__file__).with_name('release_patch.py')),socket],check=True)
            subprocess.run(['python3',str(pathlib.Path(__file__).with_name('release_patch_2_24_2.py')),socket],check=True)
            subprocess.run(['python3',str(pathlib.Path(__file__).with_name('release_patch_2_24_3.py')),socket],check=True)
        finally:
            db.terminate();db.wait(timeout=20)
