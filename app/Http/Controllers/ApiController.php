<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;

class ApiController extends Controller
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
    * Verify access token
    */
  public function hasValidAccessToken($access_token) {
    $request = $this->gw2api_client->get('tokeninfo' . $this->generateAccessTokenString($access_token));

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
  public function generateAccessTokenString($access_token) {
    return '?access_token=' . $access_token;
  }

  public function getAccountEndpoint($access_token) {
    $request = $this->gw2api_client->get('account' . $this->generateAccessTokenString($access_token));
    return json_decode($request->getBody());
  }

  public function getWvWRank($access_token) {
    $account_data = $this->getAccountEndpoint($access_token);
    return $account_data->wvw_rank;
  }

  // This could be rewritten to return worlds?ids=all and then traverse the array to minimize API calls
  function getServerName($serverId) {
    $request = $this->gw2api_client->get('worlds/' . $serverId);
    $json = json_decode($request->getBody());

    return $json->name;
  } 

  function getServerID($access_token) {
    $account_data = $this->getAccountEndpoint($access_token);
    return $account_data->world;
  }

  function getAccountName($access_token) {
    $account_data = $this->getAccountEndpoint($access_token);
    return $account_data->name;
  }
}
