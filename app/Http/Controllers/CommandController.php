<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

class CommandController extends Controller
{
  /**
    * Create a new controller instance.
    *
    * @return void
    */
  public function __construct()
  {
      // Make a new API client for requests
      $this->gw2api_client = new Client([
        'base_uri' => 'https://api.guildwars2.com/v2/',
        'http_errors' => false
      ]);
  }

  /**
    * Determine which command to run.
    *
    * @param String $type
    * @param String $access_token
    *
    * @return String
    */
  public function runCommand($type, $access_token) {
    $this->access_token = $access_token;

    if (!$this->hasValidAccessToken()) {
      return 'Please enter a valid access token.';
    }

    switch ($type) {
      case 'wvw':
        return $this->getWvWRank();
        break;

      case 'pve':
        return 'No support for PvE Masteries yet.';
        break;

      case 'pvp':
        return 'No support for PvP rank yet.';
        break;

      case 'server':
        return 'No support for server yet.';
        break;

      default:
        return 'This command does not exist.';
        break;
    }

  }

  /**
    * Verify access token
    */
  public function hasValidAccessToken() {
    $request = $this->gw2api_client->get('tokeninfo' . $this->generateAccessTokenString());

    if ($request->getStatusCode() == 200) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Generate access token string
   *
   * @param String $access_token
   *
   * @return String
   */
  public function generateAccessTokenString() {
    return '?access_token=' . $this->access_token;
  }

  public function getWvWRank() {
    $request = $this->gw2api_client->get('account' . $this->generateAccessTokenString());
    $json = json_decode($request->getBody());

    return $json->wvw_rank;
  }

}
