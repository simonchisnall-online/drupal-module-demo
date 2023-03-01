<?php

namespace Drupal\kat_autocloud;

/**
 *
 */
class ProcessStock {

  /**
   *
   */
  public static function CreateNodeStockFromApiStock($api_stock, &$context) {
    /** @var \Drupal\kat_autocloud\KatStock $stock_service */
    $stock_service = \Drupal::service('kat_autocloud.stock');
    $api_stock_key = $api_stock->Key;

    // Create / update stock node.
    $node_id = $stock_service->createUpdateStock($api_stock);

    $message = $api_stock_key . ' processed into Node id ' . $node_id;
    $context['results'][] = $node_id;

    $context['message'] = $message;

  }

  /**
   *
   */
  public static function updateStockFinishedCallback($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One stock node created.', '@count stock items processed.'
      );
      /** @var \Drupal\kat_autocloud\KatStock $service */
      $stock_service = \Drupal::service('kat_autocloud.stock');
      $stock_service->removeOldStock();
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::logger('kat_autocloud')->notice('Process autocloud stock finished - ' . $message);
  }

}
