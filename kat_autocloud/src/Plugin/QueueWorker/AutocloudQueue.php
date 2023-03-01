<?php

namespace Drupal\kat_autocloud\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes stock items from API.
 *
 * @QueueWorker(
 *   id = "kat_autocloud_queue",
 *   title = @Translation("Learning task worker: autocloud queue"),
 * )
 */
class AutocloudQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($api_stock) {
    // @todo use dependency injetion.
    /** @var \Drupal\kat_autocloud\KatStock $stock_service */
    $stock_service = \Drupal::service('kat_autocloud.stock');
    $api_stock_key = $api_stock->Key;

    // Create / update stock node.
    $node_id = $stock_service->createUpdateStock($api_stock);

    $message = $api_stock_key . ' processed into Node id ' . $node_id;

    return $message;
  }

}
