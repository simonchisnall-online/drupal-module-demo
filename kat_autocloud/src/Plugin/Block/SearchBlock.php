<?php

namespace Drupal\kat_autocloud\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Search Block.
 *
 * @Block(
 *   id = "kat_autocloud_search_block",
 *   admin_label = @Translation("KAT Autocloud Search block"),
 *   category = @Translation("KAT"),
 * )
 */
class SearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @todo use dependency injetion.
    $form = \Drupal::formBuilder()->getForm('Drupal\kat_autocloud\Form\SearchForm');
    $renderArray['form'] = $form;

    return $renderArray;
  }

}
