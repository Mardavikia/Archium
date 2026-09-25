<?php
declare(strict_types=1);
namespace Archium\Controllers;
use Archium\Repositories\AclRepository;
use Archium\Repositories\GroupRepository;
use Archium\Repositories\WorkspaceRepository;
use Archium\Services\PermissionResolver;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;
use PDO;
final class TrashController extends BaseController {
 private function db():PDO{return Database::connection($this->config);}
 private function acl():PermissionResolver{$p=$this->db();return new PermissionResolver(new WorkspaceRepository($p),new AclRepository($p),new GroupRepository($p));}
 private function owner(array $ws):bool{$u=Auth::user($this->config);return $this->acl()->isOwner((int)$ws['id'],(int)$u['id']);}
 public function index():string{$ws=$this->currentWorkspace();if(!$this->owner($ws)){http_response_code(403);return 'Cestino riservato al proprietario del workspace.';}$p=$this->db();$d=$p->prepare('SELECT id,title,deleted_at FROM documents WHERE workspace_id=:w AND deleted_at IS NOT NULL ORDER BY deleted_at DESC');$d->execute(['w'=>$ws['id']]);$c=$p->prepare('SELECT id,name,deleted_at FROM collections WHERE workspace_id=:w AND deleted_at IS NOT NULL ORDER BY deleted_at DESC');$c->execute(['w'=>$ws['id']]);return $this->view('trash/index',['pageTitle'=>'Cestino','ws'=>$ws,'documents'=>$d->fetchAll(),'collections'=>$c->fetchAll()]);}
 public function restoreDocument(string $id):string{Csrf::requireValid();$ws=$this->currentWorkspace();if(!$this->owner($ws)){http_response_code(403);return 'Accesso negato.';}$p=$this->db();$s=$p->prepare('UPDATE documents SET deleted_at=NULL WHERE id=:id AND workspace_id=:w AND deleted_at IS NOT NULL');$s->execute(['id'=>(int)$id,'w'=>$ws['id']]);if(!$s->rowCount()){http_response_code(404);return 'Documento non nel cestino.';}flash('success','Documento ripristinato.');redirect('/trash');}
 public function restoreCollection(string $id):string{Csrf::requireValid();$ws=$this->currentWorkspace();if(!$this->owner($ws)){http_response_code(403);return 'Accesso negato.';}$p=$this->db();$s=$p->prepare('UPDATE collections SET deleted_at=NULL WHERE id=:id AND workspace_id=:w AND deleted_at IS NOT NULL');$s->execute(['id'=>(int)$id,'w'=>$ws['id']]);if(!$s->rowCount()){http_response_code(404);return 'Raccolta non nel cestino.';}flash('success','Raccolta ripristinata.');redirect('/trash');}
 public function purgeDocument(string $id):string{Csrf::requireValid();$ws=$this->currentWorkspace();if(!$this->owner($ws)){http_response_code(403);return 'Accesso negato.';}$p=$this->db();$p->beginTransaction();try{$find=$p->prepare('SELECT id FROM documents WHERE id=:id AND workspace_id=:w AND deleted_at IS NOT NULL FOR UPDATE');$find->execute(['id'=>(int)$id,'w'=>$ws['id']]);if(!$find->fetchColumn()){$p->rollBack();http_response_code(404);return 'Documento non nel cestino.';}
 $p->prepare('UPDATE documents SET parent_id=NULL WHERE parent_id=:id')->execute(['id'=>(int)$id]);
 $del=$p->prepare('DELETE FROM documents WHERE id=:id');$del->execute(['id'=>(int)$id]);$p->commit();flash('success','Documento eliminato definitivamente; gli allegati orfani restano privati.');redirect('/trash');}catch(\Throwable $e){if($p->inTransaction())$p->rollBack();throw $e;}}
 public function purgeCollection(string $id):string{Csrf::requireValid();$ws=$this->currentWorkspace();if(!$this->owner($ws)){http_response_code(403);return 'Accesso negato.';}$p=$this->db();$p->beginTransaction();try{$find=$p->prepare('SELECT id FROM collections WHERE id=:id AND workspace_id=:w AND deleted_at IS NOT NULL FOR UPDATE');$find->execute(['id'=>(int)$id,'w'=>$ws['id']]);if(!$find->fetchColumn()){$p->rollBack();http_response_code(404);return 'Raccolta non nel cestino.';}
 $p->prepare('UPDATE documents SET collection_id=NULL WHERE collection_id=:id')->execute(['id'=>(int)$id]);$p->prepare('UPDATE collections SET parent_id=NULL WHERE parent_id=:id')->execute(['id'=>(int)$id]);$p->prepare('DELETE FROM collections WHERE id=:id')->execute(['id'=>(int)$id]);$p->commit();flash('success','Raccolta eliminata definitivamente; documenti conservati.');redirect('/trash');}catch(\Throwable $e){if($p->inTransaction())$p->rollBack();throw $e;}}
}