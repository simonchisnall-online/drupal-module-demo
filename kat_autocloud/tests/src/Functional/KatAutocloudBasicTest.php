<?php

namespace Drupal\Tests\kat_autocloud\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Test basic functionality of Kat Autocloud module.
 *
 * @group Kat
 *
 * @dependencies kat_autod
 */
class KatAutocloudBasicTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * A normal user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['kat_autocloud'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a normal user.
    $permissions = [
      'access content',
    ];
    $this->normalUser = $this->drupalCreateUser($permissions);

    // Create an admin user.
    $permissions += [
      'administer content types',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
  }

  /**
   * Test access to the administration page.
   */
  public function testReCaptchaAdminAccess() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/autocloud');
    $this->assertSession()->pageTextNotContains($this->t('Access denied'));
    $this->drupalLogout();
  }

  /**
   * Test the reCAPTCHA settings form.
   */
  public function testAdminSettingsForm() {
    $this->drupalLogin($this->adminUser);

    // Check test api.
    $this->drupalGet('admin/config/autocloud');
    $edit['actions'] = 'test_api';
    $this->submitForm($edit, $this->t('Submit'));
    $this->assertSession()->responseContains($this->t('test_api'));
  }

}
