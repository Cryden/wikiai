<?php

use Illuminate\Support\Facades\Route;
use Fun\Wiki\Controllers\Api\ApiController;

Route::prefix('api/v1/wiki')->group(function () {
    // Шаблоны
    Route::get('templates', [ApiController::class, 'getTemplates']);
    Route::post('templates', [ApiController::class, 'saveTemplate']);
    Route::get('templates/{id}', [ApiController::class, 'getTemplate']);

    // Статьи
    Route::get('articles', [ApiController::class, 'getArticles']);
    Route::post('articles', [ApiController::class, 'createArticle']);
    Route::get('articles/{id}', [ApiController::class, 'getArticle']);
    Route::put('articles/{id}', [ApiController::class, 'updateArticle']);
    Route::delete('articles/{id}', [ApiController::class, 'deleteArticle']);

    // Иерархия (Дерево)
    Route::get('tree', [ApiController::class, 'getTree']);
});
