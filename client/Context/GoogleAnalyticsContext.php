<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Driver\Selenium2Driver;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines Google Analytics related vocabs.
 */
class GoogleAnalyticsContext extends RawDrupalContext {

  use UtilityContextTrait;

  /**
   * VAHI Drupal context.
   *
   * @var \Drupal\VahiBehatExtension\Context\DrupalContext|null
   */
  private $drupalContext;

  /**
   * Gather the contexts available before running the scenario.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope): void {
    $this->drupalContext = $scope->getEnvironment()
      ->getContext('Drupal\VahiBehatExtension\Context\DrupalContext');
  }

  /**
   * Wait for the library to load.
   *
   * @throws \Exception
   *   When the VAHI Behat Google Analytics Monitor library is not loaded.
   */
  public function waitForLibraryToLoad(): void {
    $is_library_loaded = $this->spin(\Closure::bind(function () {
      return $this->getSession()->evaluateScript(
        'typeof window.vahiBehatGoogleAnalyticsMonitor === "object" && typeof gtag === "function"'
      );
    }, $this));
    if (!$is_library_loaded) {
      throw new \Exception('VAHI Behat Google Analytics Monitor library not loaded.');
    }
  }

  /**
   * Assert a Google Analytic event was not fired.
   *
   * @Then this Google Analytics event should not be fired:
   *
   * @throws \Exception
   *   When the event was fired.
   */
  public function assertGoogleAnalyticsEventNotFired(PyStringNode $unexpected_event): void {
    $unexpected_event = @json_decode(
      $this->drupalContext->tokenReplace($unexpected_event->getRaw()),
      TRUE
    );

    $this->waitForLibraryToLoad();
    $commands_fired = $this->getSession()->evaluateScript(
      'window.vahiBehatGoogleAnalyticsMonitor.getCommands()'
    );
    foreach ($commands_fired as $command_fired) {
      if (
        $command_fired['0'] === 'event'
        && $command_fired[1] === $unexpected_event['name']
        && $command_fired[2] === $unexpected_event['params']
      ) {
        throw new \Exception(sprintf(
          'Event "%s" was fired.',
          $unexpected_event['name']
        ));
      }
    }
  }

  /**
   * Assert a Google Analytic event was fired.
   *
   * @Then this Google Analytics event should be fired:
   *
   * @throws \Exception
   *   When the specified event was not fired.
   */
  public function assertGoogleAnalyticsEventFired(PyStringNode $expected_event): void {
    $expected_event = @json_decode(
      $this->drupalContext->tokenReplace($expected_event->getRaw()),
      TRUE
    );

    $this->waitForLibraryToLoad();
    $commands_fired = $this->getSession()->evaluateScript(
      'window.vahiBehatGoogleAnalyticsMonitor.getCommands()'
    );
    foreach ($commands_fired as $command_fired) {
      if (
        $command_fired['0'] === 'event'
        && $command_fired[1] === $expected_event['name']
        && $command_fired[2] === $expected_event['params']
      ) {
        return;
      }
    }

    throw new \Exception(sprintf(
      'Event "%s" was not fired. Commands fired: %s',
      $expected_event['name'],
      print_r($commands_fired, TRUE)
    ));
  }

  /**
   * Assert a Google Analytic command was fired.
   *
   * @Then this Google Analytics command should be fired:
   *
   * @throws \Exception
   *   When the command was not fired.
   */
  public function assertGoogleAnalyticsCommandFired(PyStringNode $expected_command): void {
    $expected_command = @json_decode(
      $this->drupalContext->tokenReplace($expected_command->getRaw()),
      TRUE
    );

    $this->waitForLibraryToLoad();
    $commands_fired = $this->getSession()->evaluateScript(
      'window.vahiBehatGoogleAnalyticsMonitor.getCommands()'
    );
    foreach ($commands_fired as $command_fired) {
      if (
        $command_fired['0'] === $expected_command['type']
        && $command_fired[1] === $expected_command['params']
      ) {
        return;
      }
    }

    throw new \Exception(sprintf(
      'Command "%s" was not fired. Commands fired: %s',
      $expected_command['name'],
      print_r($commands_fired, TRUE)
    ));
  }

  /**
   * Print out all Google Analytics events fired.
   *
   * @Given I print all Google Analytics commands fired
   */
  public function iPrintAllGoogleAnalyticsCommandsFired(): void {
    print_r($this->getSession()->evaluateScript(
      'window.vahiBehatGoogleAnalyticsMonitor.getCommands()'
    ));
  }

  /**
   * Cleanup the recorded Google Analytics API calls.
   *
   * @AfterScenario
   */
  public function clearRecordedEvents(): void {
    // Only run when the driver can execute JavaScript.
    if ($this->getSession()->getDriver() instanceof Selenium2Driver) {
      $this->getSession()->evaluateScript(
        'typeof window.vahiBehatGoogleAnalyticsMonitor !== "undefined" && window.vahiBehatGoogleAnalyticsMonitor.clearCommands()'
      );
    }
  }

}
