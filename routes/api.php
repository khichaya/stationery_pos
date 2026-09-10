<?php
use App\Http\Controllers\Api\OrderSyncController;

Route::post('/sync-order', [OrderSyncController::class, 'sync']);