<?php
declare(strict_types=1);

/** @var \Archium\Support\Router $router */

use Archium\Controllers\AttachmentController;
use Archium\Controllers\AuthController;
use Archium\Controllers\CollectionController;
use Archium\Controllers\DashboardController;
use Archium\Controllers\DocumentController;
use Archium\Controllers\FavoriteController;
use Archium\Controllers\HomeController;
use Archium\Controllers\MigrateController;
use Archium\Controllers\RevisionController;
use Archium\Controllers\SearchController;
use Archium\Controllers\SetupController;
use Archium\Controllers\TagController;
use Archium\Controllers\TrashController;
use Archium\Controllers\WorkspaceController;
use Archium\Middleware\RequireAuth;
use Archium\Middleware\RequireGuest;

// Stato installazione
$router->get('/', [HomeController::class, 'index']);

// Migrazioni
$router->get('/migrate', [MigrateController::class, 'show']);
$router->post('/migrate/run', [MigrateController::class, 'run']);

// Setup primo amministratore
$router->get('/setup', [SetupController::class, 'show']);
$router->post('/setup', [SetupController::class, 'store']);

// Autenticazione — solo ospiti
$router->get('/register', [AuthController::class, 'showRegister'], [RequireGuest::class]);
$router->post('/register', [AuthController::class, 'register'], [RequireGuest::class]);
$router->get('/login', [AuthController::class, 'showLogin'], [RequireGuest::class]);
$router->post('/login', [AuthController::class, 'login'], [RequireGuest::class]);
$router->get('/forgot-password', [AuthController::class, 'showForgot'], [RequireGuest::class]);
$router->post('/forgot-password', [AuthController::class, 'sendReset'], [RequireGuest::class]);
$router->get('/reset-password', [AuthController::class, 'showReset'], [RequireGuest::class]);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], [RequireGuest::class]);
$router->get('/verify-email/notice', [AuthController::class, 'verifyNotice'], [RequireGuest::class]);
$router->get('/verify-email', [AuthController::class, 'verifyEmail']);

// Area autenticata
$router->post('/logout', [AuthController::class, 'logout'], [RequireAuth::class]);
$router->get('/dashboard', [DashboardController::class, 'index'], [RequireAuth::class]);

// Workspace
$router->get('/workspaces', [WorkspaceController::class, 'index'], [RequireAuth::class]);
$router->get('/workspaces/create', [WorkspaceController::class, 'create'], [RequireAuth::class]);
$router->post('/workspaces', [WorkspaceController::class, 'store'], [RequireAuth::class]);
$router->post('/workspaces/{id}/select', [WorkspaceController::class, 'select'], [RequireAuth::class]);
$router->get('/workspaces/{id}/edit', [WorkspaceController::class, 'edit'], [RequireAuth::class]);
$router->post('/workspaces/{id}/update', [WorkspaceController::class, 'update'], [RequireAuth::class]);

// Raccolte
$router->get('/collections', [CollectionController::class, 'index'], [RequireAuth::class]);
$router->get('/collections/create', [CollectionController::class, 'create'], [RequireAuth::class]);
$router->post('/collections', [CollectionController::class, 'store'], [RequireAuth::class]);
$router->get('/collections/{id}/edit', [CollectionController::class, 'edit'], [RequireAuth::class]);
$router->post('/collections/{id}/update', [CollectionController::class, 'update'], [RequireAuth::class]);
$router->post('/collections/{id}/delete', [CollectionController::class, 'destroy'], [RequireAuth::class]);

// Ricerca, tag, preferiti, cestino
$router->get('/search', [SearchController::class, 'index'], [RequireAuth::class]);
$router->get('/tags', [TagController::class, 'index'], [RequireAuth::class]);
$router->get('/tags/{id}', [TagController::class, 'show'], [RequireAuth::class]);
$router->post('/documents/{id}/tags', [TagController::class, 'add'], [RequireAuth::class]);
$router->post('/documents/{id}/tags/{tagId}/remove', [TagController::class, 'remove'], [RequireAuth::class]);
$router->get('/favorites', [FavoriteController::class, 'index'], [RequireAuth::class]);
$router->post('/documents/{id}/favorite', [FavoriteController::class, 'toggle'], [RequireAuth::class]);
$router->get('/trash', [TrashController::class, 'index'], [RequireAuth::class]);
$router->post('/trash/documents/{id}/restore', [TrashController::class, 'restoreDocument'], [RequireAuth::class]);
$router->post('/trash/documents/{id}/force-delete', [TrashController::class, 'forceDeleteDocument'], [RequireAuth::class]);
$router->post('/trash/collections/{id}/restore', [TrashController::class, 'restoreCollection'], [RequireAuth::class]);

// Documenti — rotte a segmenti multipli PRIMA di /documents/{id}
$router->get('/documents', [DocumentController::class, 'index'], [RequireAuth::class]);
$router->get('/documents/create', [DocumentController::class, 'create'], [RequireAuth::class]);
$router->post('/documents', [DocumentController::class, 'store'], [RequireAuth::class]);
$router->get('/documents/{id}/edit', [DocumentController::class, 'edit'], [RequireAuth::class]);
$router->post('/documents/{id}/update', [DocumentController::class, 'update'], [RequireAuth::class]);
$router->post('/documents/{id}/delete', [DocumentController::class, 'destroy'], [RequireAuth::class]);
$router->post('/documents/{id}/attachments', [AttachmentController::class, 'upload'], [RequireAuth::class]);
$router->get('/documents/{id}/revisions', [RevisionController::class, 'index'], [RequireAuth::class]);
$router->get('/documents/{id}/revisions/{num}', [RevisionController::class, 'show'], [RequireAuth::class]);
$router->post('/documents/{id}/revisions/{num}/restore', [RevisionController::class, 'restore'], [RequireAuth::class]);
$router->get('/documents/{id}', [DocumentController::class, 'show'], [RequireAuth::class]);

// Allegati
$router->get('/attachments/{id}/download', [AttachmentController::class, 'download'], [RequireAuth::class]);
$router->post('/attachments/{id}/delete', [AttachmentController::class, 'delete'], [RequireAuth::class]);
$router->get('/attachments/{id}', [AttachmentController::class, 'stream'], [RequireAuth::class]);