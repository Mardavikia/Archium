<?php
declare(strict_types=1);
namespace Archium\Repositories;
use PDO;

final class AclRepository
{
    public function __construct(private PDO $pdo) {}
    public function collectionEntries(int $collectionId):array {
        $sql='SELECT "user" AS subject_type,u.id AS subject_id,u.name AS subject_name,u.email AS subject_detail,cup.role FROM collection_user_permissions cup INNER JOIN users u ON u.id=cup.user_id WHERE cup.collection_id=:id UNION ALL SELECT "group" AS subject_type,g.id AS subject_id,g.name AS subject_name,COALESCE(g.description,"") AS subject_detail,cgp.role FROM collection_group_permissions cgp INNER JOIN groups g ON g.id=cgp.group_id WHERE cgp.collection_id=:id AND g.deleted_at IS NULL ORDER BY subject_type,subject_name';$s=$this->pdo->prepare($sql);$s->execute(['id'=>$collectionId]);return $s->fetchAll()?:[];
    }
    public function documentEntries(int $documentId):array {
        $sql='SELECT "user" AS subject_type,u.id AS subject_id,u.name AS subject_name,u.email AS subject_detail,dup.role FROM document_user_permissions dup INNER JOIN users u ON u.id=dup.user_id WHERE dup.document_id=:id UNION ALL SELECT "group" AS subject_type,g.id AS subject_id,g.name AS subject_name,COALESCE(g.description,"") AS subject_detail,dgp.role FROM document_group_permissions dgp INNER JOIN groups g ON g.id=dgp.group_id WHERE dgp.document_id=:id AND g.deleted_at IS NULL ORDER BY subject_type,subject_name';$s=$this->pdo->prepare($sql);$s->execute(['id'=>$documentId]);return $s->fetchAll()?:[];
    }
    public function collectionInherited(int $collectionId):array{return $this->collectionEntries($collectionId);}
    public function grantCollectionUsers(int $collectionId,array $ids,string $role):void {$s=$this->pdo->prepare('INSERT INTO collection_user_permissions(collection_id,user_id,role) VALUES(:c,:u,:r) ON DUPLICATE KEY UPDATE role=VALUES(role),updated_at=CURRENT_TIMESTAMP');foreach($ids as $id)$s->execute(['c'=>$collectionId,'u'=>$id,'r'=>$role]);}
    public function grantCollectionGroups(int $collectionId,array $ids,string $role):void {$s=$this->pdo->prepare('INSERT INTO collection_group_permissions(collection_id,group_id,role) VALUES(:c,:g,:r) ON DUPLICATE KEY UPDATE role=VALUES(role),updated_at=CURRENT_TIMESTAMP');foreach($ids as $id)$s->execute(['c'=>$collectionId,'g'=>$id,'r'=>$role]);}
    public function grantDocumentUsers(int $documentId,array $ids,string $role):void {$s=$this->pdo->prepare('INSERT INTO document_user_permissions(document_id,user_id,role) VALUES(:d,:u,:r) ON DUPLICATE KEY UPDATE role=VALUES(role),updated_at=CURRENT_TIMESTAMP');foreach($ids as $id)$s->execute(['d'=>$documentId,'u'=>$id,'r'=>$role]);}
    public function grantDocumentGroups(int $documentId,array $ids,string $role):void {$s=$this->pdo->prepare('INSERT INTO document_group_permissions(document_id,group_id,role) VALUES(:d,:g,:r) ON DUPLICATE KEY UPDATE role=VALUES(role),updated_at=CURRENT_TIMESTAMP');foreach($ids as $id)$s->execute(['d'=>$documentId,'g'=>$id,'r'=>$role]);}
    public function revokeCollection(string $type,int $collectionId,int $subjectId):void {$table=$type==='group'?'collection_group_permissions':'collection_user_permissions';$column=$type==='group'?'group_id':'user_id';$this->pdo->prepare("DELETE FROM {$table} WHERE collection_id=:c AND {$column}=:s")->execute(['c'=>$collectionId,'s'=>$subjectId]);}
    public function revokeDocument(string $type,int $documentId,int $subjectId):void {$table=$type==='group'?'document_group_permissions':'document_user_permissions';$column=$type==='group'?'group_id':'user_id';$this->pdo->prepare("DELETE FROM {$table} WHERE document_id=:d AND {$column}=:s")->execute(['d'=>$documentId,'s'=>$subjectId]);}
    public function userCollectionRole(int $collectionId,int $userId):?string {$s=$this->pdo->prepare('SELECT role FROM collection_user_permissions WHERE collection_id=:c AND user_id=:u');$s->execute(['c'=>$collectionId,'u'=>$userId]);$v=$s->fetchColumn();return $v===false?null:(string)$v;}
    public function userDocumentRole(int $documentId,int $userId):?string {$s=$this->pdo->prepare('SELECT role FROM document_user_permissions WHERE document_id=:d AND user_id=:u');$s->execute(['d'=>$documentId,'u'=>$userId]);$v=$s->fetchColumn();return $v===false?null:(string)$v;}
}