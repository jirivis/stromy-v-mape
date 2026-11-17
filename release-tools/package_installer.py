#!/usr/bin/env python3
"""Reproducible fresh-install archive; explicit allowlist, never local user data."""
import argparse, hashlib, json, pathlib, re, zipfile

ROOT = pathlib.Path(__file__).resolve().parent.parent

def build(output):
    version = (ROOT / 'app/VERSION').read_text().strip()
    if not re.fullmatch(r'\d+\.\d+\.\d+', version):
        raise ValueError('Invalid version')
    paths = json.loads((ROOT / 'release-tools/installer-files.json').read_text())
    private_allowed = {'private/.htaccess', 'private/config.example.php',
                       'private/storage/area_photos/.gitkeep'}
    entries = {}
    for name in paths:
        p = pathlib.PurePosixPath(name)
        if p.is_absolute() or '..' in p.parts or str(p) != name:
            raise ValueError('Unsafe path: ' + name)
        if name.startswith('private/') and name not in private_allowed:
            raise ValueError('Private data forbidden: ' + name)
        if name.startswith(('map/public/photos/', 'map/public/uploads/')) and p.name not in {'.htaccess', '.gitkeep'}:
            raise ValueError('Customer photo forbidden: ' + name)
        source = ROOT / name
        if source.is_symlink() or not source.is_file():
            raise ValueError('Missing file or symlink: ' + name)
        data = source.read_bytes()
        if re.search(rb'-----BEGIN (?:RSA |EC |OPENSSH |ENCRYPTED )?PRIVATE KEY-----', data):
            raise ValueError('Private key forbidden: ' + name)
        entries['application/' + name] = data
    for name in ['INSTALACE.md', 'FOTOGRAFIE.md', 'OBNOVA_A_OPRAVNENI.md']:
        entries['docs/' + name] = (ROOT / 'docs' / name).read_bytes()
    entries['CTI_ME.md'] = (ROOT / 'docs/INSTALACE.md').read_bytes()
    entries['SHA256SUMS.txt'] = ''.join(hashlib.sha256(data).hexdigest() + '  ' + name + '\n'
        for name, data in sorted(entries.items())).encode()
    output = pathlib.Path(output)
    output.parent.mkdir(parents=True, exist_ok=True)
    # Fixed timestamps and modes make the ZIP tested in CI identical to distribution.
    with output.open('xb') as stream, zipfile.ZipFile(stream, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as z:
        for name, data in sorted(entries.items()):
            info = zipfile.ZipInfo(name, (2026, 1, 1, 0, 0, 0))
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            info.compress_type = zipfile.ZIP_DEFLATED
            z.writestr(info, data, compresslevel=9)
    print(json.dumps({'version': version, 'files': len(entries), 'sha256': hashlib.sha256(output.read_bytes()).hexdigest()}))

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--output', required=True)
    build(parser.parse_args().output)
