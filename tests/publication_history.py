"""Read-only publication triage over all fetched refs. Prints paths/reasons, never values.
This is not a complete secret detector or a security audit.
"""
import subprocess, re, json

def git(*args): return subprocess.check_output(['git',*args])
objects=git('rev-list','--objects','--all').decode().splitlines()
findings=[]; blobs=0
patterns=[
 ('private-key', re.compile(rb'^-----BEGIN (?:RSA |EC |OPENSSH |ENCRYPTED )?PRIVATE KEY-----',re.M)),
 ('provider-token', re.compile(rb'\b(?:gh[pousr]_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{40,}|AKIA[A-Z0-9]{16})\b')),
 ('literal-credential', re.compile(rb"""(?i)(?:password|passwd|db_pass|smtp_pass|api_key|client_secret)['"]?\s*(?:=>|=|:)\s*['"]([^'"\r\n]{8,})['"]""",re.M))
]
for row in objects:
 sha,_,path=row.partition(' ')
 if git('cat-file','-t',sha).strip()!=b'blob':continue
 blobs+=1; data=git('cat-file','blob',sha)
 if re.search(r'(^|/)(config\.php|\.env(?:\..*)?|installed\.lock|publisher-private\.pem)$',path):
  findings.append({'blob':sha,'path':path,'reason':'sensitive-filename','test_fixture':path.startswith('tests/')})
 if b'\0' in data:continue
 for label,pattern in patterns:
  if pattern.search(data):findings.append({'blob':sha,'path':path,'reason':label,'test_fixture':path.startswith('tests/')})
print(json.dumps({'blobs_checked':blobs,'findings':findings},indent=2))
# Candidates need human review; an empty result is not a guarantee of no secrets.
