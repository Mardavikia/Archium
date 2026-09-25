<?php
declare(strict_types=1);
namespace Archium\Controllers;
use Archium\Repositories\AclRepository;
use Archium\Repositories\GroupRepository;
use Archium\Repositories\DocumentRepository;
use Archium\Repositories\WorkspaceRepository;
use Archium\Repositories\WorkspaceMemberRepository;
use Archium\Services\PermissionResolver;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;
final class DocumentAclController extends BaseController {
 private function context(int $id):array{$ws=$this->currentWorkspace();$p=Database::connection($this->config);$d=(new DocumentRepository($p))->findInWorkspace($id,(int)$ws['id']);if(!$d){http_response_code(404);exit('Documento non trovato.');}$u=Auth::user($this->config);$r=new PermissionResolver(new WorkspaceRepository($p),new AclRepository($p),new GroupRepository($p));if(!$r->canDocument((int)$ws['id'],$id,$d['collection_id']===null?null:(int)$d['collection_id'],(int)$u['id'],'admin')){http_response_code(403);exit('Permesso admin documento richiesto.');}return [$ws,$d,$p];}
 public function index(string $id):string{[$ws,$d,$p]=$this->context((int)$id);$acl=new AclRepository($p);return $this->view('documents/access',['pageTitle'=>'Accesso documento','ws'=>$ws,'doc'=>$d,'direct'=>$acl->documentEntries((int)$id),'inherited'=>$d['collection_id']===null?[]:$acl->collectionEntries((int)$d['collection_id']),'members'=>(new WorkspaceMemberRepository($p))->forWorkspace((int)$ws['id']),'groups'=>(new GroupRepository($p))->forWorkspace((int)$ws['id'])]);}
 public function save(string $id):string{Csrf::requireValid();[$ws,$d,$p]=$this->context((int)$id);$type=(string)($_POST['subject_type']??'');$role=(string)($_POST['role']??'');$raw=$_POST['subject_ids']??[];if(!in_array($type,['user','group'],true)||!in_array($role,['viewer','editor','admin'],true)||!is_array($raw)||count($raw)>200){http_response_code(422);return 'Input non valido.';}$ids=array_values(array_unique(array_map('intval',$raw)));$allowed=$type==='user'?array_map('intval',array_column((new WorkspaceMemberRepository($p))->forWorkspace((int)$ws['id']),'user_id')):array_map('intval',array_column((new GroupRepository($p))->forWorkspace((int)$ws['id']),'id'));foreach($ids as $v)if($v<=0||!in_array($v,$allowed,true)){http_response_code(422);return 'Soggetto esterno al workspace.';}$acl=new AclRepository($p);$p->beginTransaction();try{if($type==='user')$acl->grantDocumentUsers((int)$id,$ids,$role);else $acl->grantDocumentGroups((int)$id,$ids,$role);$p->commit();}catch(\Throwable $e){$p->rollBack();throw $e;}flash('success','Override documento assegnati.');redirect('/documents/'.(int)$id.'/access');}
 public function remove(string $id,string $type,string $subject):string{Csrf::requireValid();[$ws,$d,$p]=$this->context((int)$id);if(!in_array($type,['user','group'],true)||!ctype_digit($subject)){http_response_code(422);return 'Input non valido.';}(new AclRepository($p))->revokeDocument($type,(int)$id,(int)$subject);flash('success','Override rimosso.');redirect('/documents/'.(int)$id.'/access');}
}