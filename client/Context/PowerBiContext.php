<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\Mink\Driver\Selenium2Driver;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines common VAHI Portal Behat PowerBI vocabs.
 */
class PowerBiContext extends RawDrupalContext {

  use UtilityContextTrait;

  /**
   * Verify that the desired visualisation event has been emitted.
   *
   * @Then the :visualisation visualisation should be :status
   *
   * @throws \Exception
   *    Desired event was not received in time.
   */
  public function theVisualisationShouldBe(string $visualisation, string $status): object {
    // How long to wait for the events.
    $wait = $this->getMinkParameter('ajax_timeout');

    return $this->spin(function () use ($visualisation, $status) {
      $events = $this->getEvents();
      if (count($events) > 0) {
        foreach ($events as $event) {
          if ($event->visualisationName->name == $visualisation && $event->type == $status) {
            return $event;
          }
        }
      }
    }, $wait);
  }

  /**
   * Verify that the desired visualisation event has not been emitted.
   *
   * @Then the :visualisation visualisation should not be :status
   *
   * @throws \Exception
   *    Desired event was received in time.
   */
  public function theVisualisationShouldNotBe(string $visualisation, string $status): void {
    // How long to wait for the events.
    $wait = 15;

    try {
      $this->spin(function () use ($visualisation, $status): void {
        $events = $this->getEvents();
        if (count($events) > 0) {
          foreach ($events as $event) {
            if ($event->visualisationName->name == $visualisation && $event->type == $status) {
              return;
            }
          }
        }
      }, $wait);
    }
    catch (\Exception $e) {
      return;
    }
    throw new \Exception("Found $visualisation $status event. Expected it not to be triggered.");
  }

  /**
   * Verify that the desired visualisation event has ben emitted.
   *
   * @Then the :visualisation visualisation should be :status with filter :filter
   *
   * @throws \Exception
   *    Desired event was not received in time.
   */
  public function theVisualisationShouldBeWithFilter(string $visualisation, string $status, string $filter): void {
    $event = $this->theVisualisationShouldBe($visualisation, $status);

    if ($event->visualisationName->filterKeyword == $filter) {
      return;
    }
    throw new \Exception("Failed to detect $filter filter for $visualisation");
  }

  /**
   * Get emitted events from vahi-behat.
   *
   * @return object[]
   *   Array of event objects.
   */
  private function getEvents(): array {
    // Only run when the driver can execute JavaScript.
    if (!$this->getSession()->getDriver() instanceof Selenium2Driver) {
      return [];
    }

    // Fetch events from the VAHI Behat module.
    try {
      $json = $this->getSession()->evaluateScript('JSON.stringify(window.vahiBehatJsEventMonitor.getEvents());');
    }
    catch (\Exception $e) {
      // Ignore this exception, because this may be
      // caused by the driver and/or JavaScript.
      return [];
    }

    // Unserialise the events.
    return @json_decode($json);
  }

  /**
   * Cleanup any stored Power BI events.
   *
   * @AfterScenario
   */
  public function cleanUpPowerBiEvents(): void {
    $this->getSession()->evaluateScript(
      'typeof window.vahiBehatJsEventMonitor !== "undefined" && window.vahiBehatJsEventMonitor.clearEvents();'
    );
  }

}
