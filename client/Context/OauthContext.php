<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use NuvoleWeb\Drupal\DrupalExtension\Context\RawDrupalContext;
use RobThree\Auth\TwoFactorAuth;
use Dotenv\Dotenv;
use Drupal\VahiBehatExtension\Enum\ElementState;

/**
 * Defines common VAHI Portal Behat OAuth vocabs.
 */
class OauthContext extends RawDrupalContext {

  use UtilityContextTrait;

  /**
   * The Drupal context.
   *
   * @var \Drupal\DrupalExtension\Context\DrupalContext|null
   */
  private $drupalContext;

  /**
   * Mink context.
   *
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  private $minkContext;

  /**
   * Config context.
   *
   * @var \Drupal\DrupalExtension\Context\ConfigContext
   */
  private $configContext;

  /**
   * Utility context.
   *
   * @var \Drupal\VahiBehatExtension\Context\ElementContext
   */
  private $elementContext;

  /**
   * Gather the contexts available before running and setup evironment.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope): void {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext('Drupal\DrupalExtension\Context\DrupalContext');
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
    $this->configContext = $environment->getContext('Drupal\DrupalExtension\Context\ConfigContext');
    $this->elementContext = $environment->getContext('Drupal\VahiBehatExtension\Context\ElementContext');

    $envDir = __DIR__ . '/../../';
    $dotenv = DotEnv::createImmutable($envDir);
    if (file_exists($envDir . '.env')) {
      $dotenv->load();
    }
  }

  /**
   * Login to VAHI Portal using AD credentials.
   *
   * @param string $role
   *   The desired AD role.
   *
   * @Given I am logged in as a VAHI AD with role :role
   */
  public function iAmLoggedInAsaVahiAdWithRole(string $role): void {
    // Get account username, password & secret for desired role.
    // Ideally pass in passwords and secrets via environment variable.
    $username = $_ENV['TEST_USER_ACCOUNT_' . strtoupper($role)];
    $password = $_ENV['TEST_USER_PASSWORD_' . strtoupper($role)];
    $secret = $_ENV['TEST_USER_SECRET_' . strtoupper($role)];

    // Click sign-in link.
    $this->minkContext->clickLink('Sign in');

    $this->elementContext->waitForAndFillElement('Microsoft authentication username', $username);
    $this->minkContext->pressButton('Next');
    $this->elementContext->waitForAndFillElement('#userNameInput', $username);

    $this->elementContext->waitForAndFillElement('Microsoft authentication password', $password);

    $signIn = $this->getSession()->getPage()->find('css', '#submitButton');
    $signIn->click();

    if ($this->elementContext->waitForElementState('Microsoft authentication otp', ElementState::VISIBLE, 3)) {
      // Page 3: 2FA entry.
      // Calculate OTP.
      $tfa = new TwoFactorAuth('VAHI Portal');
      $token_text = $tfa->getCode(strtoupper($secret));

      $this->elementContext->waitForAndFillElement('Microsoft authentication otp', $token_text);
      $this->minkContext->pressButton('Verify');

      // Sometimes the token needs to be entered again.
      if ($this->elementContext->waitForElementState('iMicrosoft authentication otp', ElementState::VISIBLE, 5) != NULL) {
        // Wait for the token to cycle.
        $this->spin(function () use ($token_text, $tfa, $secret) {
          $new_token = $tfa->getCode(strtoupper($secret));
          if ($new_token === $token_text) {
            return FALSE;
          }
          else {
            return $new_token;
          }
        });

        $token_text = $tfa->getCode(strtoupper($secret));

        $this->minkContext->fillField('otc', $token_text);
        $this->minkContext->pressButton('Verify');
      }
    }

    // Sometimes the user is asked if they ant to stay signed in.
    if ($this->getSession()->getPage()->hasContent('Stay signed in?') != NULL) {
      $this->minkContext->pressButton('Yes');
    }

    // Wait for redirect to VAHI Portal.
    $page = $this->getSession()->getPage();
    $page->waitFor(5, function () {
        return (strpos($this->getSession()->getDriver()->getCurrentUrl(), $this->minkContext->getMinkParameter('base_url')) !== NULL);
    });

    // Page 4: Back to VAHI portal.
    // User may be on the Terms and conditions page. If so, accept.
    if (strpos($this->getSession()->getDriver()->getCurrentUrl(), 'terms-and-conditions') !== FALSE) {
      $this->minkContext->pressButton('I accept');
    }

    // Assert on the VAHI portal and signed in.
    $this->minkContext->assertHomepage();

    $this->minkContext->clickLink('My account');
    $this->minkContext->assertTextVisible($username);
  }

  /**
   * Configure an external API response.
   *
   * @Given the :service service returns a mock :http response:
   */
  public function mockGenericServiceResponse(string $service, string $http_code, PyStringNode $response): void {
    $vahiBehatServiceName = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '_', $service), '_'));
    $this->configContext->setConfig('vahi_behat.settings', $vahiBehatServiceName . '_response', (string) $response);
    $this->configContext->setConfig('vahi_behat.settings', $vahiBehatServiceName . '_http_code', $http_code);
  }

  /**
   * Simulate an OAuth failure when logging in.
   *
   * @Given I fail to login with OAuth error code :error
   */
  public function iFailToLogin(string $error): void {
    $authUrl = '/authenticate?';

    if (!empty($error)) {
      $query = http_build_query([
        "error" => "interaction_required",
        "error_description" => $error . ": Error Description",
        "error_uri" => "https://login.microsoftonline.com/error?code=" . str_replace("AADSTS", "", $error),
        "state" => "Lw==",
      ]);
      $authUrl .= $query;
    }

    $this->minkContext->visitPath($authUrl);
  }

}
