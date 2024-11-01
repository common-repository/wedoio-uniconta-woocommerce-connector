<?php

namespace Wedoio\API;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;

class WedoioClient {

  const VERSION = '0.0.8';

  /**
   * @var array()
   */
  protected $auth;

  /**
   * @var String
   */
  protected $company;

  /**
   * @var String
   */
  protected $apiUrl;

}
