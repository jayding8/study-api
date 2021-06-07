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
    // 用户注册
    Route::post('register', 'UserController@register');
    // 用户登录
    Route::post('login', 'UserController@login');
});

// 日志 路由
Route::group(['prefix' => 'logs', 'namespace' => 'Logs'], function () {
    Route::group(['middleware' => 'auth'], function () {
        // 新增操作记录
        Route::post('log', 'LogsController@create');
        // 删除日志记录
        Route::delete('log', 'LogsController@delete');
        // 获取日志记录
        Route::get('log', 'LogsController@logs');
    });
});

// 可转债 路由
Route::group(['prefix' => 'kzz', 'namespace' => 'Kzz'], function () {
    Route::get('lowRiskKzz', 'KzzController@lowRiskStrategy');
    Route::get('strategy', 'KzzController@strategy');
});

