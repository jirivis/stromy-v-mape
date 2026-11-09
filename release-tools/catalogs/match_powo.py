#!/usr/bin/env python3
"""Match a local official WCVP ZIP; never download or alter a live database.
Usage: python match_powo.py /path/wcvp.zip
Outputs reviewed mapping plus idempotent, fill-only SQL to database/catalogs.
"""
import csv, io, json, re, sys, zipfile, hashlib
from pathlib import Path
ROOT=Path(__file__).resolve().parents[2]
DATA=ROOT/'database/catalogs'
def name(s):return re.sub(r'\s+',' ',s.replace('×',' × ')).strip().casefold()
def author(s):return re.sub(r'\s+','',s).casefold()
def quote(s):return "'"+s.replace('\\','\\\\').replace("'","''")+"'"
def select(row,hits,reviewed):
    if row['scientific_name'] in reviewed:
        override=reviewed[row['scientific_name']]
        chosen=[h for h in hits if h['powo_id']==override['powo_id'] and h['taxon_name']==override['matched_name']]
        if len(chosen)!=1:raise ValueError('Reviewed mapping changed: '+row['scientific_name'])
        return chosen[0],'reviewed',override['reason']
    exact=[h for h in hits if author(h['taxon_authors'])==author(row['author'])]
    if len(exact)==1:return exact[0],'name_and_author',''
    if len(hits)==1:return hits[0],'unique_name_author_diff','Jediná shoda názvu; původní autor se liší a není automaticky opravován.'
    return None,'unresolved','Chybějící nebo nejednoznačná shoda; žádné SQL.'
def main(path):
    if hashlib.sha256(Path(path).read_bytes()).hexdigest()!='d32ea2b3a85e489b14e83bcc9eae7274532e1d113753f7be290d4b2dfde573fa':
        raise ValueError("Source differs from reviewed WCVP v16; review metadata and mappings before proceeding")
    source=json.loads((DATA/'powo-input.json').read_text())
    reviewed=json.loads((DATA/'powo-reviewed.json').read_text())
    targets={name(r['scientific_name']) for r in source}|{name(r['matched_name']) for r in reviewed.values()}
    candidates={}
    with zipfile.ZipFile(path) as z:
        for r in csv.DictReader(io.TextIOWrapper(z.open('wcvp_names.csv'),encoding='utf-8-sig'),delimiter='|'):
            if name(r['taxon_name']) in targets:candidates.setdefault(name(r['taxon_name']),[]).append(r)
    result=[];accepted_ids=set()
    for row in source:
        key=reviewed.get(row['scientific_name'],{}).get('matched_name',row['scientific_name'])
        hit,method,note=select(row,candidates.get(name(key),[]),reviewed)
        out={**row,'match_method':method,'note':note}
        if hit:
            if not re.fullmatch(r'\d+-\d+',hit['powo_id']):raise ValueError('Missing or invalid POWO ID')
            out.update({k:hit[k] for k in ['powo_id','taxon_name','taxon_authors','taxon_status','accepted_plant_name_id']})
            out['url']='https://powo.science.kew.org/taxon/urn:lsid:ipni.org:names:'+hit['powo_id']
            accepted_ids.add(hit['accepted_plant_name_id'])
        result.append(out)
    accepted={}
    with zipfile.ZipFile(path) as z:
        for r in csv.DictReader(io.TextIOWrapper(z.open('wcvp_names.csv'),encoding='utf-8-sig'),delimiter='|'):
            if r['plant_name_id'] in accepted_ids:accepted[r['plant_name_id']]={k:r[k] for k in ['taxon_name','taxon_authors','powo_id']}
    for r in result:
        if 'powo_id' in r:r['accepted_taxon']=accepted.get(r.pop('accepted_plant_name_id'))
    sha=hashlib.sha256(Path(path).read_bytes()).hexdigest()
    report={'source':'Royal Botanic Gardens, Kew: WCVP v16, 2026-06-04','source_url':'https://sftp.kew.org/pub/data-repositories/WCVP/wcvp.zip','doi':'10.34885/egs6-cp24','license':'CC BY 3.0','retrieved':'2026-11-09','source_sha256':sha,'records':result}
    (DATA/'powo-matches.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n')
    sql=['-- Kew WCVP v16, DOI 10.34885/egs6-cp24, CC BY 3.0; see powo-matches.json.', '-- Fill empty POWO IDs only, guarded by original name AND author. No renaming or deletion.']
    for r in result:
        if 'powo_id' not in r:continue
        sql.append('UPDATE species SET powo_id = '+quote(r['powo_id'])+' WHERE BINARY scientific_name = BINARY '+quote(r['scientific_name'])+' AND BINARY author = BINARY '+quote(r['author'])+" AND (powo_id IS NULL OR powo_id = '');")
    (DATA/'powo-ids.sql').write_text('\n'.join(sql)+'\n')
    print('Matched',sum('powo_id' in r for r in result),'of',len(result))
if __name__=='__main__':main(sys.argv[1])
