<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api', 'middleware' => 'auth'], function() use ($router){
    $router->get('me', 'AuthController@me');
});

$router->group(['prefix' => 'api', 'middleware' => ['apikey', 'cors']], function () use ($router) {
    // TransactionController Routes
    $router->get('transactions/fetchWallet', 'TransactionController@fetchWallet');
    $router->get('transactions/getAllBanks', 'TransactionController@getAllBanks');
    $router->post('transactions/peertopeer', 'TransactionController@peertopeer');
    $router->post('transactions/fetchNIPAccount', 'TransactionController@fetchNIPAccount');
    $router->post('transactions/fetchProvidusAccount', 'TransactionController@fetchProvidusAccount');
    $router->post('transactions/verifyOTP', 'TransactionController@verifyOTP');
    $router->post('transactions/userTransactions', 'TransactionController@userTransactions');
    $router->get('transactions/generateReceiptLink', 'TransactionController@generateReceiptLink');
    $router->post('transactions/creditWalletCard', 'TransactionController@creditWalletCard');
    $router->post('transactions/setTransactionPin', 'TransactionController@setTransactionPin');

    // ProvToExtTransactionController Routes
    $router->post('transactions/p2e', 'ProvToExtTransactionController@provToExt');

    // ProvToProvTransactionController Routes
    $router->post('transactions/p2p', 'ProvToProvTransactionController@provToProv');
    $router->get('transactions/all', 'TransactionController@index'); // For the Admin view
    $router->get('transactions/{id}', 'TransactionController@show');
    $router->post('transactions/create', 'TransactionController@store');
    $router->post('transactions/update/{id}', 'TransactionController@update');
    $router->delete('transactions/delete/{id}', 'TransactionController@destroy');
    $router->post('register', 'AuthController@register');
    $router->post('login', 'AuthController@login');

});
