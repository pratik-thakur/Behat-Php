<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use WebDriver\Key;

/**
 * Defines common VAHI Portal Behat utility vocabs.
 */
class UtilityContext extends RawDrupalContext {

  use UtilityContextTrait;

  /**
   * Wait for a given number of seconds.
   *
   * @param int|float $seconds
   *   Number of seconds to wait for. Floats accepted.
   *
   * @Given I wait for :seconds second(s)
   */
  public function iWaitForSeconds(float $seconds): void {
    usleep(intval($seconds * 1000000));
  }

  /**
   * Wait for a given number of milliseconds.
   *
   * @param int|float $milliseconds
   *   Number of milliseconds to wait for. Floats accepted.
   *
   * @Given I wait for :seconds millisecond(s)
   */
  public function iWaitForMilliseconds(float $milliseconds): void {
    usleep(intval($milliseconds * 1000));
  }

  /**
   * Send key.
   *
   * @param string $key
   *   Key to press.
   * @param int $count
   *   Number of times to press key.
   *
   * @throws Behat\Mink\Exception\UnsupportedDriverActionException
   *   When operation not supported by the driver.
   *
   * @Given I press the :key key
   * @Given I press the :key key :count time(s)
   *
   * @see WebDriver\Key
   *   For the keys supported.
   */
  public function iPressKey(string $key, int $count = 1): void {
    $this->assertDriverIsSelenium2($this->getSession()->getDriver(), 'Sending a key to the browser window is not supported.');
    for ($i = 0; $i < $count; $i++) {
      $keys = ['value' => [constant(Key::class . '::' . strtoupper($key))]];
      $this->getSession()->getDriver()->getWebDriverSession()->keys($keys);
    }
  }

}
