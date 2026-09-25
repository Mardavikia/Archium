<?php
declare(strict_types=1);
namespace Archium\Services;
use Archium\Repositories\AclRepository;
use Archium\Repositories\GroupRepository;
use Archium\Repositories\WorkspaceRepository;

final class PermissionResolver
{
    private const LEVEL=['viewer'=>1,'editor'=>2,'admin'=>3];
    public function __construct(private WorkspaceRepository $workspaces,private AclRepository $acl,private GroupRepository $groups) {}
    public function isOwner(int $workspaceId,int $userId):bool{return $this->workspaces->roleOf($workspaceId,$userId)==='owner';}
    public function isMember(int $workspaceId,int $userId):bool{return $this->workspaces->roleOf($workspaceId,$userId)!==null;}
    public function collectionRole(int $workspaceId,int $collectionId,int $userId):?string {if($this->isOwner($workspaceId,$userId))return 'admin';if(!$this->isMember($workspaceId,$userId))return null;$roles=[];$direct=$this->acl->userCollectionRole($collectionId,$userId);if($direct)$roles[]=$direct;foreach($this->groups->userGroupRolesForCollection($collectionId,$userId) as $r)$roles[]=$r;return $this->highest($roles);}
    public function documentRole(int $workspaceId,int $documentId,?int $collectionId,int $userId):?string {if($this->isOwner($workspaceId,$userId))return 'admin';if(!$this->isMember($workspaceId,$userId))return null;$documentRoles=[];$direct=$this->acl->userDocumentRole($documentId,$userId);if($direct)$documentRoles[]=$direct;foreach($this->groups->userGroupRolesForDocument($documentId,$userId) as $r)$documentRoles[]=$r;if($documentRoles!==[])return $this->highest($documentRoles);return $collectionId===null?null:$this->collectionRole($workspaceId,$collectionId,$userId);}
    public function canCollection(int $workspaceId,int $collectionId,int $userId,string $needed):bool{return $this->allows($this->collectionRole($workspaceId,$collectionId,$userId),$needed);}
    public function canDocument(int $workspaceId,int $documentId,?int $collectionId,int $userId,string $needed):bool{return $this->allows($this->documentRole($workspaceId,$documentId,$collectionId,$userId),$needed);}
    public function canCreateInWorkspace(int $workspaceId,int $userId):bool {return $this->isOwner($workspaceId,$userId);}
    public function allows(?string $role,string $needed):bool {if($role===null)return false;$required=match($needed){'read'=>1,'write'=>2,'delete'=>3,'admin'=>3,default=>99};return (self::LEVEL[$role]??0)>=$required;}
    private function highest(array $roles):?string {$best=null;$n=0;foreach($roles as $r){if((self::LEVEL[$r]??0)>$n){$best=$r;$n=self::LEVEL[$r];}}return $best;}
}