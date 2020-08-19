<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// 用户 路由
Route::group(['prefix' => 'auth', 'namespace' => 'Auth'], function () {
    Route::post('register', 'UserController@register');
    Route::post('login', 'UserController@login');
});

// 日志 路由
Route::group(['prefix' => 'logs', 'namespace' => 'Logs'], function () {
    Route::group(['middleware' => 'auth'], function () {
        Route::post('log', 'LogsController@create');    // 新增操作记录
        Route::get('logs', 'LogsController@list');      // 获取日志列表
    });
});

// 数字货币 路由
Route::group(['prefix' => 'szhb', 'namespace' => 'Szhb'], function () {
    Route::post('log', 'SzhbController@create');    // 新增记录
    Route::get('logs', 'SzhbController@lists');      // 获取日志列表
});

// 可转债 路由
Route::group(['prefix' => 'kzz', 'namespace' => 'Kzz'], function () {
    Route::get('kzz', 'KzzController@notice');
    Route::get('lowRiskKzz', 'KzzController@lowRiskStrategy');
});

