<?php
declare(strict_types=1);
// Only installation administrators may browse the installation-wide directory.
function svmProjectClients(mysqli $db, int $user, bool $super): array {
    $sql = 'SELECT c.id,c.name,c.ico FROM clients c';
    if (!$super) $sql .= " WHERE EXISTS (SELECT 1 FROM projects p WHERE p.client_id=c.id AND (p.owner_id=? OR EXISTS (SELECT 1 FROM project_users pu WHERE pu.project_id=p.id AND pu.user_id=? AND pu.project_role='admin')))";
    $s=$db->prepare($sql.' ORDER BY c.name,c.id');
    if (!$super) $s->bind_param('ii',$user,$user);
    $s->execute(); return $s->get_result()->fetch_all(MYSQLI_ASSOC);
}
