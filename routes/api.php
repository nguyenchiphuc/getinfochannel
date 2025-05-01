<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\GetInfoController;
use App\Http\Controllers\API\TestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::pattern('string', '([A-Za-z0-9_-]+)');
Route::get('/getListVideo/{string}',  [GetInfoController::class, 'getListVideo']);
Route::get('/test/{string}',  [TestController::class, 'getListVideo']);
Route::get('/test-tik',  [TestController::class, 'urlTik']);
Route::get('/test-url',  [TestController::class, 'testUrl']);