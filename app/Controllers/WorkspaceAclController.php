<?php
declare(strict_types=1);
namespace Archium\Controllers;
use Archium\Repositories\WorkspaceRepository;
use Archium\Repositories\WorkspaceMemberRepository;
use Archium\Repositories\GroupRepository;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;
use PDO;
final class WorkspaceAclController extends BaseController {
 private function access(int $workspaceId):array{$p=Database::connection($this->config);$ws=(new WorkspaceRepository($p))->findActive($workspaceId);$user=Auth::user($this->config);if(!$ws){http_response_code(404);exit('Workspace non trovato.');}if((new WorkspaceRepository($p))->roleOf($workspaceId,(int)$user['id'])!=='owner'){http_response_code(403);exit('Solo owner del workspace.');}return $ws;}
 public function index(string $id):string{$ws=$this->access((int)$id);$p=Database::connection($this->config);return $this->view('workspaces/permissions',['pageTitle'=>'Permessi workspace','ws'=>$ws,'members'=>(new WorkspaceMemberRepository($p))->forWorkspace((int)$id),'groups'=>(new GroupRepository($p))->forWorkspace((int)$id)]);}
 public function setMembers(string $id):string{Csrf::requireValid();$ws=$this->access((int)$id);$role=(string)($_POST['role']??'');$ids=$_POST['user_ids']??[];if(!in_array($role,['owner','editor','viewer'],true)||!is_array($ids)||count($ids)>200){http_response_code(422);return 'Selezione o ruolo non valido.';}$ids=array_unique(array_map('intval',$ids));$p=Database::connection($this->config);$valid=array_column((new WorkspaceMemberRepository($p))->forWorkspace((int)$id),'user_id');foreach($ids as $uid)if($uid<=0||!in_array($uid,array_map('intval',$valid),true)){http_response_code(422);return 'Utente non membro del workspace.';}$p->beginTransaction();try{foreach($ids as $uid){$s=$p->prepare('UPDATE workspace_members SET role=:r WHERE workspace_id=:w AND user_id=:u AND role<>"owner"');$s->execute(['r'=>$role,'w'=>(int)$id,'u'=>$uid]);}$p->commit();}catch(\Throwable $e){$p->rollBack();throw $e;}flash('success','Ruoli workspace aggiornati; gli owner esistenti non sono modificabili da questo modulo.');redirect('/workspaces/'.(int)$id.'/permissions');}
}