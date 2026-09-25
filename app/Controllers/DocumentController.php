<?php
declare(strict_types=1);

namespace Archium\Controllers;

use Archium\Repositories\AttachmentRepository;
use Archium\Repositories\CollectionRepository;
use Archium\Repositories\DocumentRepository;
use Archium\Repositories\FavoriteRepository;
use Archium\Repositories\RevisionRepository;
use Archium\Repositories\TagRepository;
use Archium\Repositories\WorkspaceRepository;
use Archium\Services\MarkdownService;
use Archium\Services\PermissionResolver;
use Archium\Support\Auth;
use Archium\Support\Csrf;
use Archium\Support\Database;
use Archium\Validation\Validator;

final class DocumentController extends BaseController
{
    private function pdo(): \PDO { return Database::connection($this->config); }
    private function docs(): DocumentRepository { return new DocumentRepository($this->pdo()); }
    private function cols(): CollectionRepository { return new CollectionRepository($this->pdo()); }
    private function tags(): TagRepository { return new TagRepository($this->pdo()); }
    private function md(): MarkdownService { return new MarkdownService(); }
    private function resolver(): PermissionResolver { $pdo = $this->pdo(); return new PermissionResolver(new WorkspaceRepository($pdo), new CollectionRepository($pdo), new DocumentRepository($pdo)); }

    public function index(): string
    {
        $ws = $this->currentWorkspace(); $user = Auth::user($this->config);
        if (!$this->resolver()->canWorkspace((int) $ws['id'], (int) $user['id'], 'read')) { http_response_code(403); return 'Accesso negato.'; }
        $visible = array_values(array_filter($this->docs()->forWorkspace((int) $ws['id']), function (array $doc) use ($ws, $user): bool {
            return $this->resolver()->canDocument((int) $doc['id'], (int) $ws['id'], $doc['collection_id'] !== null ? (int) $doc['collection_id'] : null, (int) $user['id'], 'read');
        }));
        return $this->view('documents/index', ['pageTitle' => 'Documenti', 'ws' => $ws, 'documents' => $visible, 'canWrite' => $this->resolver()->canWorkspace((int) $ws['id'], (int) $user['id'], 'write')]);
    }

    public function create(): string
    {
        $ws = $this->currentWorkspace(); $user = Auth::user($this->config);
        if (!$this->resolver()->canWorkspace((int) $ws['id'], (int) $user['id'], 'write')) { http_response_code(403); return 'Non hai i permessi di scrittura sul workspace.'; }
        return $this->view('documents/create', ['pageTitle'=>'Nuovo documento','ws'=>$ws,'collections'=>$this->cols()->forWorkspace((int)$ws['id']),'parents'=>$this->docs()->forWorkspace((int)$ws['id']),'availableTags'=>$this->tags()->forWorkspace((int)$ws['id']),'errors'=>[],'old'=>['title'=>'','content_markdown'=>'','collection_id'=>'','parent_id'=>'','tags'=>'']]);
    }

    public function store(): string
    {
        Csrf::requireValid(); $ws = $this->currentWorkspace(); $user = Auth::user($this->config);
        if (!$this->resolver()->canWorkspace((int)$ws['id'], (int)$user['id'], 'write')) { http_response_code(403); return 'Accesso negato.'; }
        $docs=$this->docs(); [$collectionId,$parentId,$errors]=$this->validateRefs((int)$ws['id'],null);
        $title=trim((string)($_POST['title']??'')); $content=(string)($_POST['content_markdown']??''); $tagsIn=(string)($_POST['tags']??'');
        $validator=Validator::make($_POST,['title'=>'required|min:2|max:255','tags'=>'max:520']);
        if($validator->fails()||$errors!==[]) return $this->view('documents/create',['pageTitle'=>'Nuovo documento','ws'=>$ws,'collections'=>$this->cols()->forWorkspace((int)$ws['id']),'parents'=>$docs->forWorkspace((int)$ws['id']),'availableTags'=>$this->tags()->forWorkspace((int)$ws['id']),'errors'=>array_merge($validator->errors(),$errors),'old'=>['title'=>$title,'content_markdown'=>$content,'collection_id'=>$collectionId,'parent_id'=>$parentId,'tags'=>$tagsIn]]);
        if($collectionId!==null && !$this->resolver()->canCollection($collectionId,(int)$ws['id'],(int)$user['id'],'write')) { http_response_code(403); return 'Non hai scrittura sulla raccolta selezionata.'; }
        $id=$docs->create((int)$ws['id'],$collectionId,$parentId,$title,$docs->uniqueSlug((int)$ws['id'],$title),$content!==''?$content:null,$this->md()->toHtml($content),(int)$user['id']);
        $this->tags()->syncDocument((int)$ws['id'],$id,TagRepository::parse($tagsIn)); flash('success','Documento creato.'); redirect('/documents/'.$id);
    }

    public function show(string $id): string
    {
        $ws=$this->currentWorkspace(); $user=Auth::user($this->config); $doc=$this->docs()->findInWorkspace((int)$id,(int)$ws['id']);
        if($doc===null){http_response_code(404);return 'Documento non trovato.';}
        if(!$this->resolver()->canDocument((int)$doc['id'],(int)$ws['id'],$doc['collection_id']!==null?(int)$doc['collection_id']:null,(int)$user['id'],'read')){http_response_code(403);return 'Accesso negato.';}
        $pdo=$this->pdo(); $children=array_values(array_filter($this->docs()->forWorkspace((int)$ws['id']),fn(array $d):bool=>(int)($d['parent_id']??0)===(int)$doc['id'] && $this->resolver()->canDocument((int)$d['id'],(int)$ws['id'],$d['collection_id']!==null?(int)$d['collection_id']:null,(int)$user['id'],'read')));
        return $this->view('documents/show',['pageTitle'=>$doc['title'],'ws'=>$ws,'doc'=>$doc,'children'=>$children,'html'=>$doc['content_html']??$this->md()->toHtml((string)($doc['content_markdown']??'')),'tags'=>$this->tags()->forDocument((int)$doc['id']),'attachments'=>(new AttachmentRepository($pdo))->forDocument((int)$doc['id']),'isFavorite'=>(new FavoriteRepository($pdo))->isFavorite((int)$user['id'],(int)$doc['id']),'canWrite'=>$this->resolver()->canDocument((int)$doc['id'],(int)$ws['id'],$doc['collection_id']!==null?(int)$doc['collection_id']:null,(int)$user['id'],'write'),'canAdmin'=>$this->resolver()->canDocument((int)$doc['id'],(int)$ws['id'],$doc['collection_id']!==null?(int)$doc['collection_id']:null,(int)$user['id'],'admin')]);
    }

    public function edit(string $id): string
    {
        $ws=$this->currentWorkspace();$user=Auth::user($this->config);$doc=$this->docs()->findInWorkspace((int)$id,(int)$ws['id']);
        if($doc===null){http_response_code(404);return 'Documento non trovato.';}
        if(!$this->resolver()->canDocument((int)$doc['id'],(int)$ws['id'],$doc['collection_id']!==null?(int)$doc['collection_id']:null,(int)$user['id'],'write')){http_response_code(403);return 'Sola lettura.';}
        return $this->view('documents/edit',['pageTitle'=>'Modifica: '.$doc['title'],'ws'=>$ws,'doc'=>$doc,'collections'=>$this->cols()->forWorkspace((int)$ws['id']),'parents'=>$this->docs()->forWorkspace((int)$ws['id']),'availableTags'=>$this->tags()->forWorkspace((int)$ws['id']),'docTagsCsv'=>TagRepository::toCsv($this->tags()->forDocument((int)$doc['id'])),'attachments'=>(new AttachmentRepository($this->pdo()))->forDocument((int)$doc['id']),'errors'=>[]]);
    }

    public function update(string $id): string
    {
        Csrf::requireValid();$ws=$this->currentWorkspace();$user=Auth::user($this->config);$docs=$this->docs();$doc=$docs->findInWorkspace((int)$id,(int)$ws['id']);
        if($doc===null){http_response_code(404);return 'Documento non trovato.';}
        if(!$this->resolver()->canDocument((int)$doc['id'],(int)$ws['id'],$doc['collection_id']!==null?(int)$doc['collection_id']:null,(int)$user['id'],'write')){http_response_code(403);return 'Accesso negato.';}
        [$collectionId,$parentId,$errors]=$this->validateRefs((int)$ws['id'],(int)$doc['id']);$title=trim((string)($_POST['title']??''));$content=(string)($_POST['content_markdown']??'');$tagsIn=(string)($_POST['tags']??'');$validator=Validator::make($_POST,['title'=>'required|min:2|max:255','tags'=>'max:520']);
        if($validator->fails()||$errors!==[]){$doc['title']=$title;$doc['content_markdown']=$content;$doc['collection_id']=$collectionId;$doc['parent_id']=$parentId;return $this->view('documents/edit',['pageTitle'=>'Modifica: '.$title,'ws'=>$ws,'doc'=>$doc,'collections'=>$this->cols()->forWorkspace((int)$ws['id']),'parents'=>$docs->forWorkspace((int)$ws['id']),'availableTags'=>$this->tags()->forWorkspace((int)$ws['id']),'docTagsCsv'=>$tagsIn,'attachments'=>(new AttachmentRepository($this->pdo()))->forDocument((int)$doc['id']),'errors'=>array_merge($validator->errors(),$errors)]);}
        if($collectionId!==null && !$this->resolver()->canCollection($collectionId,(int)$ws['id'],(int)$user['id'],'write')){http_response_code(403);return 'Non hai scrittura sulla raccolta selezionata.';}
        $pdo=$this->pdo();(new RevisionRepository($pdo))->add((int)$doc['id'],(string)$doc['title'],$doc['content_markdown'],(int)$user['id']);$docs->update((int)$doc['id'],$collectionId,$parentId,$title,$content!==''?$content:null,$this->md()->toHtml($content),(int)$user['id']);$this->tags()->syncDocument((int)$ws['id'],(int)$doc['id'],TagRepository::parse($tagsIn));flash('success','Documento aggiornato (revisione precedente salvata).');redirect('/documents/'.(int)$doc['id']);
    }

    public function destroy(string $id): string
    {
        Csrf::requireValid();$ws=$this->currentWorkspace();$user=Auth::user($this->config);$doc=$this->docs()->findInWorkspace((int)$id,(int)$ws['id']);
        if($doc===null){http_response_code(404);return 'Documento non trovato.';}
        if(!$this->resolver()->canDocument((int)$doc['id'],(int)$ws['id'],$doc['collection_id']!==null?(int)$doc['collection_id']:null,(int)$user['id'],'write')){http_response_code(403);return 'Accesso negato.';}
        $this->docs()->softDelete((int)$doc['id']);flash('success','Documento spostato nel cestino.');redirect('/documents');
    }

    private function validateRefs(int $wsId, ?int $selfDocId): array
    {
        $errors=[];$collectionId=($raw=trim((string)($_POST['collection_id']??'')))!==''?(int)$raw:null;if($collectionId!==null&&!$this->cols()->findInWorkspace($collectionId,$wsId)){$errors['collection_id']=['Raccolta non valida per questo workspace.'];$collectionId=null;}
        $parentId=($raw=trim((string)($_POST['parent_id']??'')))!==''?(int)$raw:null;if($parentId!==null){$parent=$this->docs()->findInWorkspace($parentId,$wsId);if(!$parent){$errors['parent_id']=['Documento genitore non valido.'];$parentId=null;}elseif($selfDocId!==null&&$this->docs()->isSelfOrDescendant($selfDocId,$parentId)){$errors['parent_id']=['Non puoi creare un ciclo gerarchico.'];}}
        return [$collectionId,$parentId,$errors];
    }
}