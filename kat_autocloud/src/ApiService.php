<?php

namespace Drupal\kat_autocloud;

use GuzzleHttp\Exception\RequestException;

/**
 * Class ApiService.
 *
 * @package Drupal\icehouse_autocloud
 */
class ApiService {

  protected $base_url = 'http://128.199.237.176';
  protected $account_key = '?AccountKey=c0d486eb-ae21-4634-a81b-0f928714efcc&DealerKey=0619256a-f661-4bdc-bfb0-991518bda6dc';

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * Get stock list.
   */
  public function getStockList() {
    $list = $this->callEndpoint("/stock/list");
    if ($list) {
      $list_decoded = json_decode($list);
      return $list_decoded->list;
    }
    else {
      return NULL;
    }
  }

  /**
   * Get branch list.
   */
  public function getBranchList() {
    $list = $this->callEndpoint("/branch/list");
    if ($list) {
      $list_decoded = json_decode($list);
      return $list_decoded->list;
    }
    else {
      return NULL;
    }
  }

  /**
   * Get individual stock item.
   */
  public function getStockItem($stock_number) {
    $key = '&Params={"StockKey":"' . $stock_number . '"}';
    $stock_item = $this->callEndpoint("/stock/get", $key);
    if ($stock_item) {
      $stock_item_decoded = json_decode($stock_item);
      if (isset($stock_item_decoded->Data)) {
        return $stock_item_decoded->Data;
      }
      else {
        \Drupal::logger('kat_autocloud')
          ->error("Error loading stock key:$stock_number");
        return NULL;
      }

    }
    else {
      $list = NULL;
    }
    return $list;
  }

  /**
   * Call endpoint.
   */
  public function callEndpoint($endpoint, $extra_key = '') {
    $uri = $this->base_url . $endpoint . $this->account_key . $extra_key;
    try {
      $response = \Drupal::httpClient()
        ->get($uri, ['headers' => ['Accept' => 'application/json']]);
      $data = $response->getBody()->getContents();
    }
    catch (RequestException $e) {
      \Drupal::logger('kat_autocloud')
        ->error($e->getMessage());
      return FALSE;
    }
    return $data;
  }

}
