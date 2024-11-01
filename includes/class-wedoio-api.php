<?php
/**
 * User: gb
 * RNI
 */

use GuzzleHttp\Psr7\Request;
use Guzzle\Http\Exception\ClientErrorResponseException;

class WedoioApi {

  private $apiEndpoint = 'https://wedoio.com/api2/uniconta/';

  public $wedoioToken;
  public $unicontaToken;
  public $unicontaCompany;

  public function __construct() {
    // Should be loaded already but since we are using require_once we don't have to worry
    require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

    $uniconta_username = get_option('uniconta_username');
    $uniconta_password = get_option('uniconta_password');
    $uniconta_company = get_option('uniconta_company');
    $wedoio_token = get_option('wedoio_token');

    $uniconta_token = $uniconta_username . ":" . $uniconta_password;
    $this->wedoioToken = $wedoio_token;
    $this->unicontaToken = $uniconta_token;
    $this->unicontaCompany = $uniconta_company;
  }

  /**
   * Check the connexion with WedoioAPI
   */
  public function statusCheck() {
    $result = $this->sendDirect("Enum/CountryISOCode");
//        print_r($result);
    if ($result['status'] != 200) {
      return false;
    }
    return true;
  }

  /**
   * Process a request directly
   */
  public function sendDirect($endpoint, $params = array()) {

    $company = $this->unicontaCompany;
    $this->unicontaCompany = "";
    $result = $this->send($endpoint, $params);
    $this->unicontaCompany = $company;
    return $result;
  }

  /**
   * Process a request and return the result as is. Means the response needs to be processed.
   *
   * How to use :
   *
   */
  public function send($endpoint, $params = array()) {

    $eula_accepted = esc_attr(get_option('uniconta_accept_eula'));
    $response = [
      "status" => 401,
      "body" => "[]"
    ];

    if (!$eula_accepted)
      return [];

    if ($this->unicontaCompany) $endpoint = "Company/" . $this->unicontaCompany . "/" . $endpoint;
    $url = $this->apiEndpoint . "$endpoint";

    $default = array(
      "method" => "GET",
      "params" => array(),
      "headers" => array()
    );

    $params = array_merge($default, $params);

    if ($params['method'] == "POST") {
      $params['method'] = "PUT";
    } else if ($params['method'] == "PUT") {
      $params['method'] = "POST";
    }

    $request_headers = array();
    $req_params = array();

    $req_params['auth'] = array($this->wedoioToken, "");
    $req_params['headers'] = $params['headers'];
    $req_params['headers']['UNICONTA_Authorization'] = $this->unicontaToken;
    $req_params['http_errors'] = false;

    if (isset($params['body'])) {
      $req_params['body'] = $params['body'];
    }

    if (isset($params['json'])) {
      $req_params['json'] = $params['json'];
    }

    $reponse = array(
      "status" => 200,
      "body" => ""
    );

//        Wedoio_Public::log($params);

    global $wedoioApiCalls;

    try {
      $client = new \GuzzleHttp\Client();
      $req = new Request($params['method'], $url);

//			Wedoio_Public::log($params['method'] . " " . $url);
      $wedoioApiCalls[] = $params['method'] . " " . $url;

      $res = @$client->send($req, $req_params);
      $body = $res->getBody();
      $body = (string)$body;

      $response['status'] = $res->getStatusCode();
      $response['body'] = $body;
      $response['res'] = $res;

//            Wedoio_Public::log($response);

    } catch (Exception $e) {
      error_log("wedoio | " . $e->getMessage());
      $response['status'] = 500;
      $response['body'] = $e->getMessage();
      Wedoio_Watchdog::log("API", $e->getMessage() . " Response : " . json_encode($response), "error");
    }

//        Wedoio_Public::log($response);

    return $response;
  }

  /**
   * General Fetcher for Uniconta
   */
  public function fetch($entity, $params = array(), $cache = false) {
    global $wedoio_cache;
    $cache_key = md5(__FUNCTION__ . "_" . $entity . "_" . json_encode($params));

    $entities = $wedoio_cache[$cache_key] ?? [];

    if ($cache && $entities) {
      return $entities;
    }

    $entities = array();
    $endpoint = "$entity.json";

    if (is_integer($params) || is_string($params)) {
      $rowID = $params;
      $endpoint = "$entity/$rowID.json";
    } else if (is_array($params)) {
      $args = http_build_query($params);
      if ($args) {
        $endpoint .= "?" . $args;
      }
    }

    $endpoint = urldecode($endpoint);

    $response = $this->send($endpoint);

    if ($response['status'] == 200) {
      $entities = json_decode($response['body']);
    }

    $wedoio_cache[$cache_key] = $entities;

    return $entities;
  }

  /**
   * General Setter for Uniconta
   */
  public function set($entity, $params) {
    $rowId = 0;
    $method = "POST";
    $result = false;

    if (isset($params['RowId'])) {
      $rowId = $params['RowId'];
      $endpoint = "$entity/$rowId.json";
      unset($params['RowId']);
    } else {
      $endpoint = "$entity.json";
    }

    if (!$rowId) {
      // If there is no rowId then we create the entity
      $method = "PUT";
    }

    foreach ($params as $key => $value) {
      if (is_string($value)) {
        $value = trim($value);
        if ($value == "") {
          unset($params[$key]);
        }
      }
    }


    $request['method'] = $method;
    $request['json'] = $params;
    $request['endpoint'] = $endpoint;
//        Wedoio_Public::log($request);
//        Wedoio_Public::log(json_encode($request['json']));
    $response = $this->send($endpoint, $request);

    if (strpos($response['status'], '20') === 0) {
      $result = $method == "PUT" ? json_decode($response['body']) : true;
    }

    return $result;
  }

  /**
   * Fetch a debtor from uniconta
   */
  public function fetchDebtor($params) {
    $user = $this->fetch("Debtor", $params);
    return $user;
  }

  /**
   * Set a debtor on uniconta ( update / create )
   */
  public function setDebtor($params) {
    $user = $this->set("Debtor", $params);
    return $user;
  }

  /**
   * Fetch a debtor from uniconta
   */
  public function fetchPriceList($params) {
    $user = $this->fetch("InvPriceListLineClient", $params);
    return $user;
  }

  /**
   * Set a debtorOrder
   */
  public function fetchDebtorOrder($params) {
    $debtorOrder = $this->fetch("DebtorOrder", $params);
    return $debtorOrder;
  }

  /**
   * Set a debtorOrder
   */
  public function setDebtorOrder($params) {
    $debtorOrder = $this->set("DebtorOrder", $params);
    return $debtorOrder;
  }

  /**
   * Set a debtorOrderLine
   */
  public function fetchDebtorOrderLine($params) {
    $url = "DebtorOrderLine";
    if (isset($params['debtorOrder'])) {
      $debtorOrder = $params['debtorOrder'];
      unset($params['debtorOrder']);
      $url = "DebtorOrder/" . $debtorOrder . "/" . $url;
    }
    $debtorOrderLine = $this->fetch($url, $params);
    return $debtorOrderLine;
  }

  /**
   * Set a debtorOrderLine
   */
  public function setDebtorOrderLine($params) {
    $url = "DebtorOrderLine";
    if (isset($params['debtorOrder'])) {
      $debtorOrder = $params['debtorOrder'];
      unset($params['debtorOrder']);

      if (isset($params['_Price'])) {
        $url = "DebtorOrder/" . $debtorOrder . "/" . $url;
      } else {
        $url = "DebtorOrder/" . $debtorOrder . "/" . $url . "/FindPrice";
      }

//            $url = "DebtorOrder/".$debtorOrder . "/" . $url;
//            $url = "DebtorOrder/".$debtorOrder . "/" . $url . "/1";
    }
    $debtorOrderLine = $this->set($url, $params);
    return $debtorOrderLine;
  }

  /**
   * Delete a debtorOrderLine
   */
  public function deleteDebtorOrderLine($rowId) {
    $url = "DebtorOrderLine/" . $rowId;
    $params['method'] = "DELETE";
    $debtorOrderLine = $this->send($url, $params);
    return $debtorOrderLine;
  }

  /**
   * Extract the fields from the entity and set a key value for them
   * In case you have special fields on the entities like UserFields on debtor. UserFields is the fieldKeyName
   * and Userfield is the fieldKeyValues
   */
  public static function extractFields($fieldKeyName, $fieldKeyValues, &$entity) {
    $extract = array();

    if (isset($entity->$fieldKeyName) && isset($entity->$fieldKeyValues)) {
      foreach ($entity->$fieldKeyName as $keyIndex => $keyDetails) {
        $keyName = $keyDetails->_Name;
        $extract[$keyName]['value'] = $entity->$fieldKeyValues->_data[$keyIndex];
        $extract[$keyName]['keyInfo'] = $keyDetails;
      }
    }

    $extractKeyName = $fieldKeyName . "Extract";
    $entity->$extractKeyName = $extract;
  }

  /**
   * Return an Enumeration Entity
   */
  public function getEnum($enumEntity) {
    $enum = get_option("uniconta-enum-$enumEntity");
    if (!$enum) {
      $enum = $this->sendDirect("Enum/" . $enumEntity);
      $enum = $enum['body'];
      $enum = json_decode($enum);
      update_option("uniconta-enum-$enumEntity", $enum);
    }

    return $enum;
  }

  /**
   * Get countries
   */
  public function getCountry($code) {
    $countries = $this->getEnum("CountryISOCode");
    return array_search($code, $countries);
  }

  /**
   * Get countries Iso
   */
  public function getCountryIso($index) {
    $countries = $this->getEnum("CountryISOCode");
    return $countries[$index];
  }

  /**
   * Get info about the current company
   * @return array|mixed|object
   */
  public static function getCompany() {
    global $uniconta_company;
    if (!$uniconta_company) {
      $api = new WedoioApi();
      $company_call = $api->send("");
      $uniconta_company = json_decode($company_call['body'], true);
    }
    return $uniconta_company;
  }

  public static function odata_call($endpoint, $full = false) {
    $odata_url = "https://odata.uniconta.com/api/Entities/";
    $url = $odata_url . $endpoint;

    $params['method'] = "GET";

    $req_params = array();

    $uniconta_username = get_option('uniconta_username');
    $uniconta_password = get_option('uniconta_password');
    $uniconta_company = get_option('uniconta_company');

    $req_params['auth'] = array("00" . $uniconta_company . "/" . $uniconta_username, $uniconta_password);
    Wedoio_Watchdog::log("API", "ODATA CALL " . $params['method'] . " $url");

    try {
      $client = new \GuzzleHttp\Client();
      $req = new Request($params['method'], $url);

      $res = @$client->send($req, $req_params);
      $body = $res->getBody();
      $body = (string)$body;

      $response['status'] = $res->getStatusCode();
      $response['body'] = json_decode($body, true);
      $response['res'] = $res;
    } catch (Exception $e) {
      error_log("wedoio | " . $e->getMessage());
      $response['status'] = 500;
      $response['body'] = $e->getMessage();
      Wedoio_Watchdog::log("API", $e->getMessage() . " Response : " . json_encode($response), "error");
    }

    if (!$full) {
      $response = $response['body'];
    }

    return $response;
  }

}
