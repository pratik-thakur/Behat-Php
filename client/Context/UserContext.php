<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Drupal\user\Entity\User;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Mink\Exception\ExpectationException;

/**
 * VAHI Context.
 */
class UserContext extends RawDrupalContext {

  /**
   * Assert the field attributes of the currentUser.
   *
   * @Then the logged in user's :attribute should be :value
   *
   * @throws \ExpectationException
   *   When user attribute does not match.
   */
  public function theLoggedInUsersShouldBe(string $attribute, string $value): void {
    // Load Current user and get attribute.
    $current_user = User::load(\Drupal::currentUser()->id());
    $user_attribute = $current_user->get($attribute)->value;
    // Compare @todo: handle checkboxes.
    if ($user_attribute !== $value) {
      throw new ExpectationException(sprintf('User attribute doesnot match : Actual "%s" and Expected "%s".', $user_attribute, $value), $this->getSession());
    }
  }

}
