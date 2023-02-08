<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Exception\UnsupportedDriverActionException;

/**
 * Utility context trait.
 */
trait UtilityContextTrait {

  /**
   * Execute a callback until it returns a truthy value, until a certain time.
   *
   * @param callable $lambda
   *   Condition to check.
   * @param int $wait
   *   Maximum seconds to wait until the condition is valid.
   *
   * @return null
   *   value of $lambda
   *
   * @throws \Exception
   *   When the spin function times out.
   */
  public function spin(callable $lambda, int $wait = 30) {
    $startTime = time();
    while ((time() - $startTime) < $wait) {
      $result = call_user_func($lambda);
      if ($result) {
        return $result;
      }

      usleep(500);
    }

    $backtrace = debug_backtrace();

    throw new \Exception(
      'Timeout thrown by ' . $backtrace[1]['class'] . '::' . $backtrace[1]['function'] . "()\n"
    );

  }

  /**
   * Returns a region selector.
   *
   * @param string $region_name
   *   Region to look for.
   *
   * @throws \Exception
   *   When the region is not found on the page.
   */
  private function getRegion(string $region_name): string {
    $session = $this->getSession();
    $page = $session->getPage();
    $regionObj = $page->find('region', $region_name);

    if (!$regionObj) {
      throw new \Exception(sprintf('No region "%s" found on the page %s.', $region_name, $session->getCurrentUrl()));
    }

    if ($region_name !== 'banner') {
      // Replace spaces with hyphens.
      $region_name = 'region-' . preg_replace('/\s+/', '-', $region_name);
    }

    $region_selector = '.' . $region_name;

    return $region_selector;
  }

  /**
   * Returns Mink session.
   *
   * @param string|null $name
   *   Name of the session OR active session will be used.
   *
   * @return \Behat\Mink\Session
   *   Mink session.
   */
  abstract public function getSession(?string $name = NULL); // phpcs:ignore

  /**
   * Asserts that the driver is a Selenium 2 driver.
   *
   * @param Behat\Mink\Driver\DriverInterface $driver
   *   Driver.
   * @param string $message
   *   Message for exception if driver is not the correct type.
   *
   * @throws Behat\Mink\Exception\UnsupportedDriverActionException
   *   When the driver is not the correct type.
   */
  public function assertDriverIsSelenium2(DriverInterface $driver, string $message): void {
    if (!$driver instanceof Selenium2Driver) {
      throw new UnsupportedDriverActionException($message, $driver);
    }
  }

}
