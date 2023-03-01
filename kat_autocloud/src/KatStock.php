<?php

namespace Drupal\kat_autocloud;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Class KatStock.
 *
 * @package Drupal\kat_autocloud
 */
class KatStock {

  protected $branchNodesByKey;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->branchNodesByKey = $this->getBranchNodesByBranchKey();

  }

  /**
   * Get branches keyed by branch id.
   */
  public function getBranchNodesByBranchKey() {
    $branch_nodes = $this->getNodes('stock_branch');
    $branch_nodes_by_branch_key = [];
    foreach ($branch_nodes as $node) {
      $branch_nodes_by_branch_key[$node->field_stock_branch_key->value] = $node;
    }
    return $branch_nodes_by_branch_key;
  }

  /**
   * Get nodes of type.
   */
  public function getNodes($type) {
    $result = \Drupal::entityQuery("node")
      ->condition('type', $type)
      ->execute();

    $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
    $entities = $storage_handler->loadMultiple($result);
    return $entities;
  }

  /**
   * Get stock nodes duplicates.
   */
  public function getStockNodesStockKeyDuplicates() {
    $stock_nodes = $this->getNodes('stock');
    $duplicates = [];
    $checked = [];
    foreach ($stock_nodes as $node) {
      $stock_key = $node->field_stock_key->value;
      if (!in_array($stock_key, $checked)) {
        $checked[] = $stock_key;
      }
      else {
        $duplicates[$node->id()] = $node;
      }

    }
    return $duplicates;
  }

  /**
   *
   */
  public function createUpdateStock($api_stock) {

    /** @var \Drupal\kat_autocloud\ApiService $service */
    $api_service = \Drupal::service('kat_autocloud.api');

    // Get stock item from API.
    $stock_item = $api_service->getStockItem($api_stock->Key);

    // Look for stock node.
    $result = \Drupal::entityQuery("node")
      ->condition('type', 'stock')
      ->condition('field_stock_key', $api_stock->Key)
      ->execute();

    // Decide if it needs to be created, updated or ignored.
    if (!$result) {
      // Create.
      $node_id = $this->createStock($api_stock, $stock_item);
      \Drupal::logger('kat_autocloud')
        ->notice('Stock node created , node id created =  ' . $node_id);
      return $node_id . ' - new';
    }
    else {
      $stock_node = Node::load(reset($result));
      if ($stock_node->field_date_updated->value != $stock_item->DateUpdated) {
        // Update.
        $this->updateStock($stock_node, $api_stock, $stock_item);
        \Drupal::logger('kat_autocloud')
          ->notice('Stock node updated , node id created =  ' . $stock_node->id());
        return $stock_node->id() . ' - updated';
      }
      else {
        return 'Do nothing';
      }
    }

  }

  /**
   * CREATE Stock.
   *
   * @param $api_stock
   */
  public function createStock($api_stock, $stock_item) {

    \Drupal::logger('kat_autocloud')
      ->notice('Creating  stock key ' . $api_stock->Key);

    $node_values = [
      'type' => 'stock',
      'title' => $api_stock->Title ? $api_stock->Title : 'No title',
      'path' => "/stock/" . $api_stock->StockNumber,
      'uid' => 1,
    ];

    // Top level data.
    $field_mappings = [
      'field_stock_number' => 'StockNumber',
      'field_stock_key' => 'Key',
      'field_stock_make' => 'Make',
      'field_stock_model' => 'Model',
      'field_stock_price_retail' => 'PriceRetail',
      'field_stock_price_sale' => 'PriceSale',
      'field_stock_year' => 'Year',
      'field_stock_body' => 'StockSubType',
      'field_stock_price_notes' => 'PriceNotes',
    ];

    foreach ($field_mappings as $field => $api_field) {
      $node_values[$field] = [
        'value' => $api_stock->$api_field,
      ];
    }

    // Stock attributes.
    $field_mappings = [
      'field_stock_odometer' => 'odometer',
      'field_stock_gvm_number' => 'gvm',
      'field_stock_abs' => 'abs',
      'field_stock_air_bags' => 'airbag',
      'field_stock_air_conditioning' => 'air_conditioning',
      'field_stock_alloy_wheels' => 'alloys',
      'field_stock_cc_rating' => 'cc',
      'field_stock_central_locking' => 'central_locking',
      'field_stock_power_steering' => 'power_steering',
      'field_stock_power_windows' => 'power_windows',
      'field_stock_spoiler' => 'spoilers',
      'field_stock_seats' => 'seats',
      'field_stock_tare' => 'tare_weight',
      'field_stock_turbo' => 'turbo',
      'field_stock_external_link' => 'external_link',
    ];

    foreach ($field_mappings as $field => $api_field) {
      if (isset($api_stock->StockAttributes->{$api_field})) {
        $value = $api_stock->StockAttributes->{$api_field}->Value ? $api_stock->StockAttributes->{$api_field}->Value : '0';
        $node_values[$field] = [
          'value' => $value,
        ];
      }
    }

    // Images.
    if (isset($api_stock->StockAssets)) {
      foreach ($api_stock->StockAssets as $image_url) {
        $image_file = $this->save_image($image_url);
        if ($image_file) {
          $node_values['field_stock_images'][] = [
            'target_id' => $image_file->id(),
            'alt' => $api_stock->Title,
          ];
        }
      }

    }

    // Description.
    if (isset($stock_item->Description)) {
      $node_values['field_stock_description'] = [
        'value' => $stock_item->Description,
        'format' => 'full_html',
      ];
    }

    // Date updated.
    if (isset($stock_item->DateUpdated)) {
      $node_values['field_date_updated'] = $stock_item->DateUpdated;
    }

    $status = 'new';

    // Branch.
    if (
      isset($stock_item->BranchKey) &&
      array_key_exists($stock_item->BranchKey, $this->branchNodesByKey) == TRUE
    ) {
      $branch_node = $this->branchNodesByKey[$stock_item->BranchKey];
      $node_values['field_stock_branch'] = [
        'target_id' => $branch_node->id(),
      ];

      // If branch is Keith andrews preowned then status is used.
      if ($stock_item->BranchKey == 'cd3fe5a8-21c3-427c-9125-d354acc65ce4') {
        $status = 'used';
      }
    }

    // Status.
    $node_values['field_stock_status'] = [
      'value' => $status,
    ];

    $node = Node::create($node_values);
    $node->save();

    return $node->id();
  }

  /**
   *
   */
  public function save_image($image_url) {
    $file = NULL;
    $image_file_name = pathinfo($image_url)['basename'];
    if ($image_url && $image_file_name) {
      $data = file_get_contents($image_url);
      $file = file_save_data($data, 'public://' . $image_file_name, FILE_EXISTS_REPLACE);
    }
    return $file;
  }

  /**
   * Update stock, we actually just delete the node and re-create.
   *
   * @param $api_stock
   * @param $stock_item
   */
  public function updateStock($stock_node, $api_stock, $stock_item) {

    \Drupal::logger('kat_autocloud')
      ->notice('Updating stock key ' . $api_stock->Key);

    // Delete node.
    $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
    $storage_handler->delete([$stock_node]);

    // Create new node.
    $this->createStock($api_stock, $stock_item);

  }

  /**
   *
   */
  public function updateBranches() {

    \Drupal::logger('kat_autocloud')->notice('Update branches started');

    /** @var \Drupal\kat_autocloud\ApiService $service */
    $api_service = \Drupal::service('kat_autocloud.api');

    // Get current branches from API.
    $branch_list = $api_service->getBranchList();

    // Get current branch nodes.
    // Create new branches.
    $nodes_created = [];
    foreach ($branch_list as $api_branch_key => $api_branch) {
      $api_branch->Key = $api_branch_key;
      // Check it doesn't already exist.
      if (array_key_exists($api_branch_key, $this->branchNodesByKey) == FALSE) {
        // Create node.
        $nodes_created[] = $this->createBranch($api_branch);
      }
    }

    \Drupal::logger('kat_autocloud')
      ->notice('Branch update complete, nodes created =  ' . implode(',', $nodes_created));

  }

  /**
   * CREATE Stock.
   *
   * @param $api_stock
   */
  private function createBranch($api_branch) {

    /** @var \Drupal\kat_autocloud\ApiService $service */
    $api_service = \Drupal::service('kat_autocloud.api');

    \Drupal::logger('kat_autocloud')
      ->notice('Creating branch key' . $api_branch->Key);

    $node_values = [
      'type' => 'stock_branch',
      'title' => $api_branch->Name,
      'path' => "/stock/branch/" . $api_branch->Key,
      'uid' => 1,
    ];

    // Top level data.
    $field_mappings = [
      'field_stock_branch_address_1' => 'Address1',
      'field_stock_branch_address_2' => 'Address2',
      'field_stock_branch_address_3' => 'Address3',
      'field_stock_branch_email' => 'Email',
      'field_stock_branch_key' => 'Key',
      'field_stock_branch_postal_1' => 'Postal1',
      'field_stock_branch_region' => 'Region',
      'field_stock_branch_telephoneah' => 'TelephoneAH',
      'field_stock_branch_telephonebh' => 'TelephoneBH',
      'field_stock_branch_telephonefax' => 'TelephoneFax',
      'field_stock_branch_mobile' => 'TelephoneMobile',

    ];

    foreach ($field_mappings as $field => $api_field) {
      $node_values[$field] = [
        'value' => $api_branch->$api_field,
      ];
    }
    $node = Node::create($node_values);
    $node->save();

    return $node->id();
  }

  /**
   *
   */
  public function updateStockTaxonomies() {
    $stock_nodes = $this->getNodes('stock');

    // Mapping between node value (key) and taxonomy vid.
    $mappings = [
      'field_stock_make' => 'stock_make',
      'field_stock_model' => 'stock_model',
    ];

    $new_terms = [];

    // Remove all terms first.
    foreach ($mappings as $node_field => $vid) {
      $this->delete_terms_from_vocab($vid);

      foreach ($stock_nodes as $node) {
        $field_value = $node->{$node_field}->value;
        $new_terms[$vid][] = $field_value;
      }
    }

    // Create new terms.
    foreach ($new_terms as $vid => $terms) {
      $terms = array_unique($terms);
      foreach ($terms as $term) {
        $term = Term::create([
          'vid' => $vid,
          'name' => $term,
        ]);
        $term->save();
      }
    }

  }

  /**
   *
   */
  public function removeOldStock() {
    $log = "Removing old stock ";
    // Remove old stock.
    $stock_nodes = $this->getStockNodesByStockKey();
    $node_stock_keys = array_keys($stock_nodes);
    /** @var \Drupal\kat_autocloud\ApiService $service */
    $api_service = \Drupal::service('kat_autocloud.api');
    $api_stock_keys = array_keys(get_object_vars($api_service->getStockList()));
    $diff = array_diff($node_stock_keys, $api_stock_keys);
    $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
    foreach ($diff as $stock_key_to_delete) {
      $node = $stock_nodes[$stock_key_to_delete];
      $storage_handler->delete([$node]);
      $log .= $node->id() . ",";
    }
    \Drupal::logger('kat_autocloud')
      ->notice($log);
  }

  /**
   *
   */
  public function getStockNodesByStockKey() {
    $stock_nodes = $this->getNodes('stock');
    $stock_nodes_by_stock_key = [];
    foreach ($stock_nodes as $node) {
      $stock_nodes_by_stock_key[$node->field_stock_key->value] = $node;
    }
    return $stock_nodes_by_stock_key;
  }

  /**
   * Delete all taxonomy terms from a vocabulary.
   *
   * @param $vid
   */
  public function delete_terms_from_vocab($vid) {

    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->execute();

    if (empty($tids)) {
      return;
    }

    $controller = \Drupal::entityManager()
      ->getStorage('taxonomy_term');
    $entities = $controller->loadMultiple($tids);

    $controller->delete($entities);
  }

}
