<?php
// Set on for developing so far
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/

require 'vendor/autoload.php';

use GuzzleHttp\Client;

$gw2api_client = new Client([
  'base_uri' => 'https://api.guildwars2.com/v2/',
  'http_errors' => false
]);

accessTokenAvailable();

function accessTokenAvailable() {
  if (hasAccessToken()) {
    verifyAccessToken();
  } else {
    echo 'Please enter an access token.';
  }
}

function verifyAccessToken() {
  if (hasValidAccessToken()) {
    if (isset($_GET['command']) && !empty($_GET['command'])) {
      determineCommand($_GET['command']);
    } else {
      echo 'Please choose a command.';
    }
  } else {
    echo 'Invalid access token.';
  }
}

function determineCommand($command) {
  switch ($command) {
    case 'wvw':
      echo getWvWRank();
      break;
    case 'pve':
      echo 'No support for PvE Masteries yet.';
      break;
    case 'pvp':
      echo 'No support for PvP rank yet.';
      break;
    case 'server':
      echo getServerName(getServerID());
      //echo 'No support for server yet.';
      break;
  }
}

function getWvWRank() {
  global $gw2api_client;

  $request = $gw2api_client->get('account'.generateAccessTokenString());
  $json = json_decode($request->getBody());

  return $json->wvw_rank;
}

function getServerName($serverId) {
  global $gw2api_client;

  $request = $gw2api_client->get('worlds/'.$serverId);
  $json = json_decode($request->getBody());

  return $json->name;
} 

function getServerID() {
  global $gw2api_client;

  $request = $gw2api_client->get('account'.generateAccessTokenString());
  $json = json_decode($request->getBody());

  return $json->world;
}

function hasAccessToken() {
  if ($_GET['access_token']) {
    return true;
  } else {
    return false;
  }
}

function hasValidAccessToken() {
  global $gw2api_client;

  $request = $gw2api_client->get('tokeninfo'.generateAccessTokenString());

  if ($request->getStatusCode() == 200) {
    return true;
  } else {
    return false;
  }
}

function getAccessToken() {
  if (hasAccessToken()) {
    return $_GET['access_token'];
  } else {
    return;
  }
}

function generateAccessTokenString() {
  return '?access_token='.getAccessToken();
}

?>