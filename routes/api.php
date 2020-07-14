<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('sms/{id}', 'WhatsController@listenToReplies');
Route::post('rchsms/{id}', 'WhatsController@listenToRch');
Route::post('wakebsms/{id}', 'WhatsController@listenToWakeb');
Route::post('majdouiesms/{id}', 'WhatsController@listenToMajd');
Route::post('tamkensms/{id}', 'WhatsController@listenToTamken');



Route::get('test', 'WhatsController@test');
Route::get('demo', 'WhatsController@arabic');
