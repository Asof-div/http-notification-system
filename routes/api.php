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

Route::group([
    'namespace' => 'API',
], function(){    

    //Subscriptions
    Route::group(['prefix' => 'subscriptions'], function(){

        Route::get('/', 'SubscriptionController@index');

        Route::get('/{id}', 'SubscriptionController@show');

    });


    Route::post('subscribe/{topic}', 'SubscriptionController@subscribe');

    Route::post('update-subscription/{topic}/{id}', 'SubscriptionController@update');

    Route::delete('unsubscribe/{id}', 'SubscriptionController@unsubscribe');


    //Topics
    Route::group(['prefix' => 'topics'], function(){

        Route::get('/', 'TopicController@index');

        Route::get('/{topic}', 'TopicController@show');

    });


    //Publish
    Route::post('publish/{topic}', 'PublishController@publish');

});
