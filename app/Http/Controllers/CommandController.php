<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;

class CommandController extends Controller
{
  /**
    * Create a new controller instance.
    *
    * @return void
    */
  public function __construct() {
    $this->api = new ApiController();
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
    //$this->access_token = $access_token;

    if (!$this->api->hasValidAccessToken($access_token)) {
      return 'Please enter a valid access token.';
    }

    switch ($type) {

      /**
       * Account Commands
       */
      case 'account-name':
        return $this->api->getAccountName($access_token);
        break;

      case 'account-server':
        return $this->api->getServerName($this->api->getServerID($access_token));
        break;

      case 'account-age':
        return $this->api->getAccountAge($access_token);
        break;

      /**
       * WvW Commands
       */
      case 'wvw-rank':
        return $this->api->getWvWRank($access_token);
        break;

      case 'wvw-kills':
        return $this->api->getWvWKills($access_token);
        break;

      /**
       * PvP Commands
       */
      case 'pvp-rank':
        return $this->api->getPvPRank($access_token);
        break;

      case 'pvp-rating':
        return 'No support for PvP rating yet.';
        break;

      /**
       * PvE Commands
       */
      case 'pve-masteries':
        return 'No support for PvE Masteries yet.';
        break;

      default:
        return 'This command does not exist.';
        break;

      /** 
       * Wallet
       */
      case 'wallet-gold':
        return $this->api->getWalletGold($access_token);
        break;

      case 'wallet-karma':
        return $this->api->getWalletKarma($access_token);
        break;
    }

  }

}
