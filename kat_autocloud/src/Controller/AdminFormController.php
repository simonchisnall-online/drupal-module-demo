<?php

namespace Drupal\kat_autocloud\Controller;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\kat_autocloud\KatStock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminFormController.
 *
 * Provides admin form.
 *
 * @package Drupal\kat_autocloud\Controller
 */
class AdminFormController extends FormBase {

  use StringTranslationTrait;
  use MessengerTrait;
  use LoggerChannelTrait;

  /**
   * Stock service.
   *
   * @var \Drupal\kat_autocloud\KatStock
   * */
  protected $stockService;

  /**
   * Stock Api service.
   *
   * @var \Drupal\kat_autocloud\ApiService
   * */
  protected $stockApiService;

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'kat_autocloud_actions';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(KatStock $kat_stock_service, $api_service) {
    $this->stockService = $kat_stock_service;
    $this->stockApiService = $api_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the services required.
      $container->get('kat_autocloud.stock'),
      $container->get('kat_autocloud.api'),
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

    $form['actions'] = [
      '#type' => 'checkboxes',
      '#title' => 'Actions to take',
      '#options' => [
        'update_stock' => $this->t('Update stock'),
        'update_branches' => $this->t('Update branches'),
        'test_api' => $this->t('Test api'),
        'delete_stock' => $this->t('Delete stock'),
      ],
    ];
    $form['actions'] = [
      '#type' => 'checkboxes',
      '#title' => 'Actions to take',
      '#options' => [
        'update_stock' => $this->t('Update stock'),
        'update_branches' => $this->t('Update branches'),
        'test_api' => $this->t('Test api'),
        'delete_stock' => $this->t('Delete stock'),
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['summary'] = [
      '#markup' => "<br/><br/><div>Stock node count = " . count($this->stockService->getStockNodesByStockKey()) . "<br/>",
      '#weight' => 5,
    ];
    $duplicates = $this->stockService->getStockNodesStockKeyDuplicates();
    $list = "";
    foreach ($duplicates as $node) {
      $list .= "<a href='/node/{$node->id()}/edit'>{$node->id()}</a>, ";
    }
    $form['summary_duplicates'] = [
      '#markup' => "<br/><div>Duplicate stock count = " . count($duplicates) . "<br/>"
      . $list,
      '#weight' => 6,
    ];
    return $form;
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
    $actions = $form_state->getValue('actions');

    foreach ($actions as $action) {
      echo $action;
      if ($action !== 0) {
        switch ($action) {
          case 'update_stock':
            $this->logger('kat_autocloud')->notice('Process stock started from action form.');
            process_autocloud_stock_batch();
            break;

          case 'update_taxonomies':
            $this->stockService->updateStockTaxonomies();
            $this->messenger()->addStatus('Stock taxonomies updated');
            break;

          case 'update_branches':
            $this->stockService->updateBranches();
            $this->messenger()->addStatus('Branches updated');
            break;

          case 'test_api':
            kint($this->stockApiService->getStockList());
            die();

          break;
          case 'delete_stock':
            $count = deleteAllNodesofType('stock');
            $this->messenger()->addStatus($count . ' stock nodes deleted');
            break;
        }
      }
    }
  }

}
