<?php
declare(strict_types=1);
namespace Archium\Controllers;
use Archium\Repositories\GroupRepository;
use Archium\Repositories\WorkspaceMemberRepository;
use Archium\Repositories\WorkspaceRepository;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;

final class GroupController extends BaseController
{
    private function guard():array {$ws=$this->currentWorkspace();$u=Auth::user($this->config);if((new WorkspaceRepository(Database::connection($this->config)))->roleOf((int)$ws['id'],(int)$u['id'])!=='owner'){http_response_code(403);exit('Solo un owner può gestire i gruppi.');}return $ws;}
    private function repo():GroupRepository{return new GroupRepository(Database::connection($this->config));}
    public function index():string{$ws=$this->guard();return $this->view('groups/index',['pageTitle'=>'Gruppi','ws'=>$ws,'groups'=>$this->repo()->forWorkspace((int)$ws['id'])]);}
    public function create():string{$ws=$this->guard();return $this->view('groups/form',['pageTitle'=>'Nuovo gruppo','ws'=>$ws,'group'=>null,'members'=>(new WorkspaceMemberRepository(Database::connection($this->config)))->forWorkspace((int)$ws['id']),'selected'=>[],'errors'=>[]]);}
    public function store():string{Csrf::requireValid();$ws=$this->guard();$name=trim((string)($_POST['name']??''));$description=trim((string)($_POST['description']??''));if(mb_strlen($name)<2||mb_strlen($name)>150){flash('error','Il nome gruppo deve avere da 2 a 150 caratteri.');redirect('/groups/create');}$id=$this->repo()->create((int)$ws['id'],$name,$description?:null);$this->syncMembers($id,(int)$ws['id']);flash('success','Gruppo creato.');redirect('/groups');}
    public function edit(string $id):string{$ws=$this->guard();$group=$this->repo()->find((int)$id,(int)$ws['id']);if(!$group){http_response_code(404);return 'Gruppo non trovato.';}return $this->view('groups/form',['pageTitle'=>'Modifica gruppo','ws'=>$ws,'group'=>$group,'members'=>(new WorkspaceMemberRepository(Database::connection($this->config)))->forWorkspace((int)$ws['id']),'selected'=>array_map(fn($m)=>(int)$m['id'],$this->repo()->members((int)$id)),'errors'=>[]]);}
    public function update(string $id):string{Csrf::requireValid();$ws=$this->guard();$group=$this->repo()->find((int)$id,(int)$ws['id']);if(!$group){http_response_code(404);return 'Gruppo non trovato.';}$name=trim((string)($_POST['name']??''));if(mb_strlen($name)<2||mb_strlen($name)>150){flash('error','Nome gruppo non valido.');redirect('/groups/'.(int)$id.'/edit');}$this->repo()->update((int)$id,$name,trim((string)($_POST['description']??''))?:null);$this->syncMembers((int)$id,(int)$ws['id']);flash('success','Gruppo aggiornato.');redirect('/groups');}
    public function delete(string $id):string{Csrf::requireValid();$ws=$this->guard();$group=$this->repo()->find((int)$id,(int)$ws['id']);if(!$group){http_response_code(404);return 'Gruppo non trovato.';}$this->repo()->softDelete((int)$id);flash('success','Gruppo eliminato.');redirect('/groups');}
    private function syncMembers(int $groupId,int $workspaceId):void{$ids=array_values(array_unique(array_map('intval',(array)($_POST['user_ids']??[]))));$valid=array_map(fn($m)=>(int)$m['user_id'],(new WorkspaceMemberRepository(Database::connection($this->config)))->forWorkspace($workspaceId));$ids=array_values(array_intersect($ids,$valid));$this->repo()->syncMembers($groupId,$ids);}
}