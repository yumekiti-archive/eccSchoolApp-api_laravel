<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScrapingController;

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

Route::controller(ScrapingController::class)->group(function () {
    // 認証
    Route::post('/signin','signin');

    // ニュース系
    Route::post('/news','news');
    Route::post('/news/{id}','only');

    // カレンダー系
    Route::post('/calendar/{month}','calendar');

    // 出席率
    Route::post('/attendance','attendance');

    // タイムテーブル
    Route::post('/timetable','timetable');
});
