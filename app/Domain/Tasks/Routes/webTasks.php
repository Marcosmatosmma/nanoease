<?php

declare(strict_types=1);

use App\Domain\Tasks\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tasks Routes (Sistema de Tarefas - Kanban)
|--------------------------------------------------------------------------
|
| Rotas para o sistema de tarefas em formato Kanban
| Todas as rotas requerem autenticação
|
*/

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        
        // Visualização do Kanban
        Route::get('/tasks', [TaskController::class, 'index'])
            ->name('tasks.index');

        // Gerenciar listas
        Route::post('/tasks/boards/{board}/lists', [TaskController::class, 'storeList'])
            ->name('tasks.lists.store');
        
        Route::put('/tasks/lists/{boardList}', [TaskController::class, 'updateList'])
            ->name('tasks.lists.update');

        Route::post('/tasks/boards/{board}/lists/reorder', [TaskController::class, 'reorderLists'])
            ->name('tasks.lists.reorder');

        // CRUD de tarefas
        Route::post('/tasks/lists/{boardList}', [TaskController::class, 'store'])
            ->name('tasks.store');
        
        Route::put('/tasks/{task}', [TaskController::class, 'update'])
            ->name('tasks.update');
        
        Route::post('/tasks/{task}/move', [TaskController::class, 'move'])
            ->name('tasks.move');
        
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
            ->name('tasks.destroy');

        // Arquivar/Desarquivar tarefas
        Route::post('/tasks/{task}/archive', [TaskController::class, 'archive'])
            ->name('tasks.archive');
        
        Route::post('/tasks/{task}/unarchive', [TaskController::class, 'unarchive'])
            ->name('tasks.unarchive');

        // Gerenciar etiquetas
        Route::post('/tasks/{task}/labels', [TaskController::class, 'storeLabel'])
            ->name('tasks.labels.store');
        
        Route::delete('/tasks/{task}/labels/{label}', [TaskController::class, 'destroyLabel'])
            ->name('tasks.labels.destroy');
    });
