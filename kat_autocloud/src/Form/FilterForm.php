<?php

namespace Drupal\kat_autocloud\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DefaultController.
 *
 * @package Drupal\icehouse_salesforce\Controller
 */
class FilterForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Request service.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   * */
  protected $requestService;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'kat_autocloud_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($request_service) {
    $this->requestService = $request_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the services required.
      $container->get('request_stack'),
    );
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

    $form['row_one'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-row']],
    ];

    $query = $this->requestService->query->all();

    $form['row_one']['model'] = [
      '#type' => 'select',
      '#title' => 'Model',
      '#options' => $this->generateFilterOptions('Model', 'stock_model'),
      '#title_display' => 'invisible',
      '#default_value' => $query['model'] ?? 'blank',
    ];

    $form['row_one']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['row_one']['reset'] = [
      '#type' => 'markup',
      '#markup' => "<a href='/stock-fuso-new' class='btn'>Reset</a>",
    ];

    $form['#attributes'] = ['class' => 'form-inline'];
    return $form;

  }

  /**
   * Generate array of filter options.
   */
  public function generateFilterOptions($first, $vid) {
    $terms = kat_autocloud_get_stock_taxonomy_names($vid);
    $options = array_merge(['blank' => $first], array_combine($terms, $terms));
    return $options;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = [];

    if (($model = $form_state->getValue('model')) && $model != 'blank') {
      $query['model'] = $model;
    }

    $url = Url::fromUri('internal:/stock-fuso-new', ['query' => $query]);
    $response = new RedirectResponse($url->toString());
    $response->send();
  }

}
