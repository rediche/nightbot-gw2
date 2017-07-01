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
      case 'wvw':
        return $this->api->getWvWRank($access_token);
        break;

      case 'pve':
        return 'No support for PvE Masteries yet.';
        break;

      case 'pvp':
        return 'No support for PvP rank yet.';
        break;

      case 'server':
        return $this->api->getServerName($this->api->getServerID($access_token));
        break;

      case 'account-name':
        return $this->api->getAccountName($access_token);
        break;

      case 'age':
        return $this->api->getAccountAge($access_token);
        break;

      default:
        return 'This command does not exist.';
        break;
    }

  }

}
