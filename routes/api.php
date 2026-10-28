<?php

use App\Http\Controllers\Api\V1\BugController as ApiBugController;
use App\Http\Controllers\Api\V1\DeliveryProjectController as ApiDeliveryProjectController;
use App\Http\Controllers\Api\V1\InitiativeController as ApiInitiativeController;
use App\Http\Controllers\Api\V1\KpiController as ApiKpiController;
use App\Http\Controllers\Api\V1\SystemController as ApiSystemController;
use App\Http\Controllers\Api\V1\StrategicPillarController as ApiStrategicPillarController;
use App\Http\Controllers\Api\V1\TaskController as ApiTaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('pillars', ApiStrategicPillarController::class)->parameters([
        'pillars' => 'strategicPillar',
    ]);
    Route::apiResource('initiatives', ApiInitiativeController::class);
    Route::apiResource('delivery-projects', ApiDeliveryProjectController::class)->parameters([
        'delivery-projects' => 'deliveryProject',
    ]);
    Route::apiResource('systems', ApiSystemController::class);
    Route::apiResource('tasks', ApiTaskController::class);
    Route::apiResource('bugs', ApiBugController::class);
    Route::apiResource('kpis', ApiKpiController::class);
});
