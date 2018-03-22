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

  public function getAccountCurrencyValue($currencyId, $access_token) {
    $wallet_data = $this->getAccountWalletEndpoint($access_token);
    $currency_index = array_search($currencyId, array_column($wallet_data, 'id'));
    return $wallet_data[$currency_index]->value;
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

  // v2/wvw/matches
  public function getWvwMatches($ids = []) {
    $idsString = implode (",", $ids);

    if (empty($idsString)) {
      $request = $this->gw2api_client->get('wvw/matches');
    } else {
      $request = $this->gw2api_client->get("wvw/matches?ids=$idsString");
    }

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

  function getServerNames($ids) {
    $idsString = implode(',', $ids);
    $request = $this->gw2api_client->get("worlds?ids=$idsString");
    $json = json_decode($request->getBody());

    return $json;
  }

  function getServerID($access_token) {
    $account_data = $this->getAccountEndpoint($access_token);
    return $account_data->world;
  }

  function getWvWMatchup($access_token) {
    $serverId = $this->getServerID($access_token);
    $region = $serverId > 2000 ? 2 : 1; // 2 = EU, 1 = NA
    $wvwMatches = $this->getWvwMatches();

    $regionalMatches = [];
    foreach ($wvwMatches as $match) {
      if (strpos($match, "$region") === 0) { // Typecast to string, otherwise strpos doesn't work.
          $regionalMatches[] = $match;
      }
    }

    $regionalMatchesData = $this->getWvwMatches($regionalMatches);
    foreach ($regionalMatchesData as $index => $matchup) {
      if (in_array($serverId, $matchup->all_worlds->blue) || 
          in_array($serverId, $matchup->all_worlds->red) || 
          in_array($serverId, $matchup->all_worlds->green)) {
        $foundMatchup = $regionalMatchesData[$index];
      }
    }

    $allWorldIdsInMatchup = array_merge($foundMatchup->all_worlds->red, $foundMatchup->all_worlds->blue, $foundMatchup->all_worlds->green);

    $allWorldsInMatchup = $this->getServerNames($allWorldIdsInMatchup);

    $red = $this->createWorldLinkObject('red', $allWorldsInMatchup, $foundMatchup);
    $blue = $this->createWorldLinkObject('blue', $allWorldsInMatchup, $foundMatchup);
    $green = $this->createWorldLinkObject('green', $allWorldsInMatchup, $foundMatchup);

    $output = $this->createWorldLinkString($red) . ' vs. ';
    $output .= $this->createWorldLinkString($blue) . ' vs. ';
    $output .= $this->createWorldLinkString($green);

    return $output;
  }

  function calculateWorldKDR($kills, $deaths) {
    return round($kills / $deaths, 1);
  }

  function createWorldLinkString($worldObj) {
    $output = '';
    $output .= "{$worldObj->hosting->name} ";

    if (count($worldObj->linked) > 0) {
      $output .= '(';

      foreach ($worldObj->linked as $index => $link) {
        $output .= "$link->name";
        if (count($worldObj->linked) != $index + 1) {
          $output .= ', ';
        }
      }

      $output .= ')';
    }

    $output .= ' ['.$this->calculateWorldKDR($worldObj->kills, $worldObj->deaths).']';

    return $output;
  }

  function createWorldLinkObject($color, $worldIds, $matchup) {
    $matchup = json_decode(json_encode($matchup), true);
    $obj = json_decode('{}');
    $obj->linked = [];

    $obj->kills = $matchup['kills'][$color];
    $obj->deaths = $matchup['deaths'][$color];

    foreach ($worldIds as $world) {
      if (!in_array($world->id, $matchup['all_worlds'][$color])) {
        continue;
      }
      
      if ($world->id == $matchup['worlds'][$color]) {
        $obj->hosting = $world;
      } else {
        $obj->linked[] = $world;
      }
    }

    return $obj;
  }

  function getAccountName($access_token) {
    $account_data = $this->getAccountEndpoint($access_token);
    return $account_data->name;
  }

  function getAccountFractalLevel($access_token) {
    $account_data = $this->getAccountEndpoint($access_token);
    return $account_data->fractal_level;
  }

  function getWalletGold($access_token) {
    return $this->convertToCoinString($this->getAccountCurrencyValue(1, $access_token));
  }

  function getWalletKarma($access_token) {
    return number_format($this->getAccountCurrencyValue(2, $access_token), 0, '', '.');
  }

  function getWalletLaurels($access_token) {
    return number_format($this->getAccountCurrencyValue(3, $access_token), 0, '', '.');
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
