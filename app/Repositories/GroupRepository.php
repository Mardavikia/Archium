<?php
declare(strict_types=1);
namespace Archium\Repositories;
use Archium\Support\Str;
use PDO;

final class GroupRepository
{
    public function __construct(private PDO $pdo) {}
    public function forWorkspace(int $workspaceId): array {
        $s=$this->pdo->prepare('SELECT g.*, (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id=g.id) AS member_count FROM groups g WHERE g.workspace_id=:w AND g.deleted_at IS NULL ORDER BY g.name');$s->execute(['w'=>$workspaceId]);return $s->fetchAll()?:[];
    }
    public function find(int $id,int $workspaceId): ?array {$s=$this->pdo->prepare('SELECT * FROM groups WHERE id=:id AND workspace_id=:w AND deleted_at IS NULL LIMIT 1');$s->execute(['id'=>$id,'w'=>$workspaceId]);$r=$s->fetch();return $r?:null;}
    public function create(int $workspaceId,string $name,?string $description): int {$slug=$this->uniqueSlug($workspaceId,$name);$s=$this->pdo->prepare('INSERT INTO groups(workspace_id,name,slug,description) VALUES(:w,:n,:s,:d)');$s->execute(['w'=>$workspaceId,'n'=>$name,'s'=>$slug,'d'=>$description]);return (int)$this->pdo->lastInsertId();}
    public function update(int $id,string $name,?string $description): void {$s=$this->pdo->prepare('UPDATE groups SET name=:n,description=:d WHERE id=:id');$s->execute(['n'=>$name,'d'=>$description,'id'=>$id]);}
    public function softDelete(int $id):void {$s=$this->pdo->prepare('UPDATE groups SET deleted_at=NOW() WHERE id=:id');$s->execute(['id'=>$id]);}
    public function members(int $groupId):array {$s=$this->pdo->prepare('SELECT u.id,u.name,u.email,u.status FROM group_members gm INNER JOIN users u ON u.id=gm.user_id WHERE gm.group_id=:g AND u.deleted_at IS NULL ORDER BY u.name');$s->execute(['g'=>$groupId]);return $s->fetchAll()?:[];}
    public function syncMembers(int $groupId,array $userIds):void {$this->pdo->prepare('DELETE FROM group_members WHERE group_id=:g')->execute(['g'=>$groupId]);if($userIds===[])return;$i=$this->pdo->prepare('INSERT IGNORE INTO group_members(group_id,user_id) VALUES(:g,:u)');foreach($userIds as $id)$i->execute(['g'=>$groupId,'u'=>$id]);}
    public function userGroupRolesForCollection(int $collectionId,int $userId):array {$s=$this->pdo->prepare('SELECT cgp.role FROM collection_group_permissions cgp INNER JOIN group_members gm ON gm.group_id=cgp.group_id INNER JOIN groups g ON g.id=cgp.group_id AND g.deleted_at IS NULL WHERE cgp.collection_id=:c AND gm.user_id=:u');$s->execute(['c'=>$collectionId,'u'=>$userId]);return $s->fetchAll(PDO::FETCH_COLUMN)?:[];}
    public function userGroupRolesForDocument(int $documentId,int $userId):array {$s=$this->pdo->prepare('SELECT dgp.role FROM document_group_permissions dgp INNER JOIN group_members gm ON gm.group_id=dgp.group_id INNER JOIN groups g ON g.id=dgp.group_id AND g.deleted_at IS NULL WHERE dgp.document_id=:d AND gm.user_id=:u');$s->execute(['d'=>$documentId,'u'=>$userId]);return $s->fetchAll(PDO::FETCH_COLUMN)?:[];}
    private function uniqueSlug(int $w,string $name):string {$base=Str::slug($name);$candidate=$base;$n=2;$s=$this->pdo->prepare('SELECT COUNT(*) FROM groups WHERE workspace_id=:w AND slug=:s');while(true){$s->execute(['w'=>$w,'s'=>$candidate]);if(!(int)$s->fetchColumn())return $candidate;$candidate=$base.'-'.$n++;}}
}