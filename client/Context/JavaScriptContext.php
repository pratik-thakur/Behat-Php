<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\Mink\Driver\Selenium2Driver;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
 * Defines common VAHI Portal Behat JavaScript vocabs.
 */
class JavaScriptContext extends RawDrupalContext {

  /**
   * Ensure VAHI Behat module is enabled.
   *
   * @BeforeSuite
   */
  public static function enableVahiBehatModule(): void {
    \Drupal::service('module_installer')->install(['vahi_behat']);
    drupal_flush_all_caches();
  }

  /**
   * Un-install the VAHI Behat module.
   *
   * @AfterSuite
   */
  public static function disableVahiBehatModule(): void {
    \Drupal::service('module_installer')->uninstall(['vahi_behat']);
  }

  /**
   * Error whitelist.
   *
   * @var array
   */
  private $errorWhitelist = [];

  /**
   * Constructor.
   *
   * @param string[] $javascript_error_whitelist
   *   Map of component names to selectors.
   */
  public function __construct(array $javascript_error_whitelist = []) {
    $this->errorWhitelist = $javascript_error_whitelist;
  }

  /**
   * Check for JavaScript errors.
   *
   * @param \Behat\Behat\Hook\Scope\AfterScenarioScope $scope
   *   After step scope event.
   *
   * @throws \Exception
   *   When JavaScript errors were thrown.
   *
   * @AfterScenario
   */
  public function checkForJsErrors(AfterScenarioScope $scope): void {
    // Only run when the driver can execute JavaScript.
    if (!$this->getSession()->getDriver() instanceof Selenium2Driver) {
      return;
    }

    // Fetch errors from the VAHI Behat module.
    try {
      $json = $this->getSession()->evaluateScript('JSON.stringify(window.vahiBehatJsErrorMonitor.getErrors());');
    }
    catch (\Exception $e) {
      // Ignore this exception, because this may be
      // caused by the driver and/or JavaScript.
      return;
    }

    // Unserialise the errors.
    $errors = @json_decode($json);
    if (json_last_error() === JSON_ERROR_NONE) {
      $messages = [];
      $whitelist_found = FALSE;
      foreach ($errors as $error) {
        if (is_string($error)) {
          foreach ($this->errorWhitelist as $whitelist_item) {
            if (preg_match($whitelist_item, $error) === 1) {
              $whitelist_found = TRUE;
              print_r(sprintf('A javascript error was found but was on the whitelist matching %s', $whitelist_item) . PHP_EOL);
            }
          }
          if (!$whitelist_found) {
            $messages[] = '- ' . $error;
          }
        }
        elseif (property_exists($error, 'type') && ($error->type === 'xhr')) {
          $messages[] = "- {$error->message} ({$error->method} {$error->url}): {$error->statusCode} {$error->response}";
        }
      }

      if (!empty($messages)) {
        $this->getSession()->evaluateScript('window.vahiBehatJsErrorMonitor.clearErrors();');
        throw new \Exception('JavaScript errors:' . PHP_EOL . implode(PHP_EOL, $messages));
      }
    }

    $this->getSession()->evaluateScript(
      'typeof window.vahiBehatJsErrorMonitor !== "undefined" && window.vahiBehatJsErrorMonitor.clearErrors();'
    );
  }

  /**
   * Print `console.log` calls.
   *
   * @Given I print all console logs
   */
  public function iPrintAllConsoleLogs(): void {
    print_r($this->getSession()->evaluateScript(
      'JSON.stringify(window.vahiBehatJsConsoleMonitor.getLogs());'
    ));
  }

  /**
   * Cleanup all cached `console.log` calls.
   *
   * @AfterScenario
   */
  public function cleanUpCosoleLogs(): void {
    $this->getSession()->evaluateScript(
      'typeof window.vahiBehatJsConsoleMonitor !== "undefined" && window.vahiBehatJsConsoleMonitor.clearLogs();'
    );
  }

  /**
   * Wait for AJAX to finish.
   *
   * @param int $seconds
   *   Max time to wait for AJAX.
   *
   * @Given I wait for AJAX to finish at most :seconds seconds
   *
   * @throws \Exception
   *   Ajax call didn't finish on time.
   */
  public function iWaitForAjaxToFinish(int $seconds): void {
    $finished = $this->getSession()->wait($seconds * 1000, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
    if (!$finished) {
      throw new \Exception("Ajax call didn't finished within $seconds seconds.");
    }
  }

}
