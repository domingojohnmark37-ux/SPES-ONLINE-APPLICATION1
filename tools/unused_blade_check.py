import os
import re
import hashlib

root = os.getcwd()
blade_files = []
for dirpath, _, filenames in os.walk(os.path.join(root, 'resources', 'views')):
    for fname in filenames:
        if fname.endswith('.blade.php'):
            path = os.path.join(dirpath, fname)
            rel = os.path.relpath(path, os.path.join(root, 'resources', 'views')).replace('\\', '/')
            blade_files.append((rel, path))

source_paths = []
for dirpath, _, filenames in os.walk(root):
    for fname in filenames:
        if fname.endswith('.php') or fname.endswith('.blade.php'):
            source_paths.append(os.path.join(dirpath, fname))

refs = set()
pattern = re.compile(r"(?:view\(|@include\(|@extends\(|@component\(|@includeFirst\(|@includeWhen\(|@includeIf\(|\$__env->make\()\s*['\"]([a-zA-Z0-9_./\-]+)['\"]")
component_pattern = re.compile(r"<x-([a-zA-Z0-9_.\-]+)")

for path in source_paths:
    try:
        with open(path, encoding='utf-8', errors='ignore') as f:
            text = f.read()
    except Exception:
        continue
    for m in pattern.finditer(text):
        refs.add(m.group(1))
    for m in component_pattern.finditer(text):
        refs.add(m.group(1).replace('-', '.'))

unused = []
for rel, path in blade_files:
    view = rel[:-10] if rel.endswith('.blade.php') else rel
    view = view.replace('/', '.')
    if view not in refs and rel not in refs and rel.replace('/', '.') not in refs:
        unused.append((view, rel))

dupes = {}
for rel, path in blade_files:
    with open(path, 'rb') as f:
        h = hashlib.md5(f.read()).hexdigest()
    dupes.setdefault(h, []).append(rel)

print(f'Total blade files: {len(blade_files)}')
print(f'Total source files scanned: {len(source_paths)}')
print() 
print('Unused or unreferenced Blade files:')
for view, rel in sorted(unused):
    print(view)
print() 
print('Duplicate blade contents groups:')
for h, group in dupes.items():
    if len(group) > 1:
        print('---')
        for rel in sorted(group):
            print(rel)
