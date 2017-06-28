<?php

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

$app->get('/', function () use ($app) {
    return view('frontpage');
});

// WvW Rank
$app->get('/command/wvw/{access_token}', function ($access_token) use ($app) {
    return 'WvW '.$access_token;
});

// PvE Mastery
$app->get('/command/pve/{access_token}', function ($access_token) use ($app) {
    return 'No support for PvE Masteries yet.';
});

// PvP Rank
$app->get('/command/pvp/{access_token}', function ($access_token) use ($app) {
    return 'No support for PvP rank yet.';
});

// Server
$app->get('/command/server/{access_token}', function ($access_token) use ($app) {
    return 'No support for server yet.';
});