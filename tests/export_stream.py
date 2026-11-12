"""A download larger than PHP's memory limit must not accumulate in an output buffer."""
import pathlib,subprocess,tempfile
repo=pathlib.Path(__file__).resolve().parent.parent
with tempfile.TemporaryDirectory() as td:
 root=pathlib.Path(td);payload=root/'archive';size=40*1024*1024
 with payload.open('wb') as f:f.truncate(size)
 with (root/'received').open('wb') as output:
  result=subprocess.run(['php','-d','memory_limit=16M','-r',"require $argv[1];ob_start();exSendZip($argv[2],'test.zip');",str(repo/'map/public/project_tools/photo_export_helpers.php'),str(payload)],stdout=output,stderr=subprocess.PIPE)
 assert result.returncode==0,result.stderr.decode()
 assert (root/'received').stat().st_size==size
 print('PASS 40 MB download under 16 MB PHP limit with an active output buffer')
