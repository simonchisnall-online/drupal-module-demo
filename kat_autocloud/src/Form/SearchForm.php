<?php

namespace Drupal\kat_autocloud\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class DefaultController.
 *
 * @package Drupal\icehouse_salesforce\Controller
 */
class SearchForm extends FilterForm {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'kat_autocloud_search';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['form_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container', 'mt2', 'mb2']],
    ];

    $form['form_wrapper']['search'] = [
      '#type' => 'textfield',
      '#title' => 'Search vehicles',
      '#size' => 30,
    ];

    $form['form_wrapper']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    $form['#attributes'] = ['class' => 'form-inline'];
    return $form;

  }

}
