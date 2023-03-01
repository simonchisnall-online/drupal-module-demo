<?php

namespace Drupal\kat_autocloud\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelTrait;

/**
 * Class RemoteActionsController.
 */
class RemoteActionsController extends ControllerBase {

  use LoggerChannelTrait;

  /**
   * Run import.
   */
  public function remoteRunImport($key) {
    if ($key == 'wibB1jVsQZ1pbu8') {
      $this->logger('kat_autocloud')->notice('KAT autocloud remote run');
      process_autocloud_stock_queue();
    }
    return [
      '#type' => 'markup',
      '#markup' => $this->t(''),
    ];

  }

}
