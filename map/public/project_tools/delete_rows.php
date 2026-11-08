<?php
// Included only after common authentication, project and CSRF checks.
function ptDeleteRows(array $data):int{
 global $mysqli,$project,$canEdit;
 if(!$canEdit)throw new RuntimeException('Nemáte oprávnění mazat objekty.',403);
 $kind=$data['kind']??'';$items=$data['rows']??null;
 if(!in_array($kind,['trees','areas'],true)||!is_array($items)||count($items)<1||count($items)>100)throw new InvalidArgumentException('Vyberte 1 až 100 objektů na aktuálním panelu.');
 $table=$kind==='trees'?'trees':'svm_areas';$seen=[];$locked=[];
 $mysqli->begin_transaction();try{
 foreach($items as $item){$id=$item['id']??null;if(!is_scalar($id)||!ctype_digit((string)$id)||(int)$id<1||isset($seen[(int)$id]))throw new InvalidArgumentException('Neplatný nebo duplicitní výběr.');$id=(int)$id;$seen[$id]=true;
  $old=ptQuery("SELECT * FROM $table WHERE id=? AND project_id=? FOR UPDATE",[$id,$project])->get_result()->fetch_assoc();
  if(!$old)throw new RuntimeException('Objekt již není v aktivním projektu. Nic nebylo smazáno.',409);
  if(!is_string($item['version']??null)||!hash_equals(ptVersion($old),$item['version']))throw new RuntimeException('Objekt mezitím někdo změnil. Obnovte tabulku. Nic nebylo smazáno.',409);$locked[]=$old;
 }
 foreach($locked as $old){$id=$old['id'];
  if($kind==='areas'){
   ptQuery('DELETE FROM svm_area_history WHERE area_id=? AND project_id=?',[$id,$project]);ptQuery('DELETE FROM svm_area_photos WHERE area_id=? AND project_id=?',[$id,$project]);
  }else{
   ptQuery('DELETE hp FROM tree_history_photos hp JOIN trees_photo p ON hp.photo_id=p.id WHERE p.tree_id=?',[$id]);
   foreach(['tree_history','tree_measurements','tree_shares','trees_photo'] as $child)ptQuery('DELETE FROM '.$child.' WHERE tree_id=?',[$id]);
  }
  ptQuery('INSERT INTO svm_table_audit(project_id,user_id,object_type,object_id,changes_json) VALUES(?,?,?,?,?)',[$project,(int)$_SESSION['user_id'],$kind,$id,ptJson(['action'=>'delete','number'=>$old['usr_id']??$old['code']??null])]);
  ptQuery("DELETE FROM $table WHERE id=? AND project_id=?",[$id,$project]);
 }
 $mysqli->commit();return count($locked);
 }catch(Throwable $e){$mysqli->rollback();throw $e;}
}
