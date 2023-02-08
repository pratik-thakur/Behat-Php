<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Element Context.
 */
class FieldContext extends RawMinkContext {

  use UtilityContextTrait;

  /**
   * Checks, that checkbox with specified id|name|label|value is visible.
   *
   * Example: Then the "remember_me" checkbox should be visible.
   * Example: And the "remember_me" checkbox is visible.
   *
   * @Then /^the "(?P<checkbox>(?:[^"]|\\")*)" checkbox should be visible$/
   * @Then /^the "(?P<checkbox>(?:[^"]|\\")*)" checkbox is visible$/
   * @Then /^the checkbox "(?P<checkbox>(?:[^"]|\\")*)" (?:is|should be) visible$/
   */
  public function assertCheckboxVisible(string $checkbox): void {
    $this->assertSession()->fieldExists($checkbox);
  }

  /**
   * Checks, that checkbox with specified id|name|label|value is invisible.
   *
   * Example: Then the "newsletter" checkbox should be invisible.
   * Example: Then the "newsletter" checkbox should not be visible.
   * Example: And the "newsletter" checkbox is invisible.
   *
   * @Then /^the "(?P<checkbox>(?:[^"]|\\")*)" checkbox should (?:be invisible|not be visible)$/
   * @Then /^the "(?P<checkbox>(?:[^"]|\\")*)" checkbox is (?:invisible|not visible)$/
   * @Then /^the checkbox "(?P<checkbox>(?:[^"]|\\")*)" should (?:be invisible|not be visible)$/
   * @Then /^the checkbox "(?P<checkbox>(?:[^"]|\\")*)" is (?:invisible|not visible)$/
   */
  public function assertCheckboxNotVisble(string $checkbox): void {
    $this->assertSession()->fieldNotExists($checkbox);
  }

}
