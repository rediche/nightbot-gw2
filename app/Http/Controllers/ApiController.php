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

  // Convert to Gold String
  public function convertToCoinString($number) {
    $gold = floor($number / 10000);
    $silver = sprintf('%02d', floor($number / 100) % 100);
    $copper = sprintf('%02d', floor($number % 100));

    return $gold.'g '.$silver.'s '.$copper.'c';
  }

  /**
   * API ENDPOINTS 
   */

  // v2/account
  public function getAccountEndpoint($access_token) {
    $request = $this->gw2api_client->get('account' . $this->generateAccessTokenString($access_token));
    return json_decode($request->getBody());
  }

  // v2/account/achievements
  public function getAccountAchievementsEndpoint($access_token) {
    $request = $this->gw2api_client->get('account/achievements' . $this->generateAccessTokenString($access_token));
    return json_decode($request->getBody());
  }

  // v2/account/mastery/points
  public function getAccountMasteryPointsEndpoint($access_token) {
    $request = $this->gw2api_client->get('account/mastery/points' . $this->generateAccessTokenString($access_token));
    return json_decode($request->getBody());
  }

  // v2/account/wallet
  public function getAccountWalletEndpoint($access_token) {
    $request = $this->gw2api_client->get('account/wallet' . $this->generateAccessTokenString($access_token));
    return json_decode($request->getBody());
  }

  // v2/currencies
  public function getCurrencies() {
    $request = $this->gw2api_client->get('currencies?ids=all');
    return json_decode($request->getBody());
  }

  // v2/pvp/stats
  public function getPvpStatsEndpoint($access_token) {
    $request = $this->gw2api_client->get('pvp/stats' . $this->generateAccessTokenString($access_token));
    return json_decode($request->getBody());
  }

  /**
   * COMMANDS
   */
  public function getWvWRank($access_token) {
    $account_data = $this->getAccountEndpoint($access_token);
    return $account_data->wvw_rank;
  }

  public function getWvWKills($access_token) {
    $achievement_data = $this->getAccountAchievementsEndpoint($access_token);
    $achievement_index = array_search(283, array_column($achievement_data, 'id'));
    return number_format($achievement_data[$achievement_index]->current, 0, '', '.');
  }

  // PvP Rank + rollovers
  public function getPvPRank($access_token) {
    $pvp_data = $this->getPvpStatsEndpoint($access_token);
    return $pvp_data->pvp_rank + $pvp_data->pvp_rank_rollovers;
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

  function getWalletGold($access_token) {
    $wallet_data = $this->getAccountWalletEndpoint($access_token);
    $currency_index = array_search(1, array_column($wallet_data, 'id'));
    return $this->convertToCoinString($wallet_data[$currency_index]->value);
  }

  function getWalletKarma($access_token) {
    $wallet_data = $this->getAccountWalletEndpoint($access_token);
    $currency_index = array_search(2, array_column($wallet_data, 'id'));
    return number_format($wallet_data[$currency_index]->value, 0, '', '.');
  }

  /**
   * Get account age.
   * Converts from seconds to days & hours.
   *
   * @param String $access_token
   *
   * @return String
   */
  function getAccountAge($access_token) {
    $account_data = $this->getAccountEndpoint($access_token);

    // From: https://stackoverflow.com/questions/8273804/convert-seconds-into-days-hours-minutes-and-seconds
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$account_data->age");

    return $dtF->diff($dtT)->format('%a days, %h hours'); // ", %i minutes and %s seconds" to add minutes and seconds
  }

  function getAccountMasteryPoints($access_token, $region = 'all') {
    $account_mastery_point_data = $this->getAccountMasteryPointsEndpoint($access_token);

    if ($region == 'all') {
      $total_mastery_points = 0;
      
      foreach($account_mastery_point_data->totals as $region) {
        $total_mastery_points += $region->spent;
      }
  
      return $total_mastery_points;
    } else {
      $region_index = array_search(ucfirst($region), array_column($account_mastery_point_data->totals, 'region'));
      return $account_mastery_point_data->totals[$region_index]->spent;
    }
  }
}
