<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines scroll related vocabs.
 */
class ScrollContext extends RawDrupalContext {

  /**
   * Scroll to the bottom of the page.
   *
   * @When I scroll to the bottom of the page
   */
  public function iScrollToBottomOfPage(): void {
    $this->getSession()->getDriver()->evaluateScript(
      'window.scrollTo(0, document.body.scrollHeight);'
    );
  }

  /**
   * Scroll to the top of the page.
   *
   * @When I scroll to the top of the page
   */
  public function iScrollToTopOfPage(): void {
    $this->getSession()->getDriver()->evaluateScript(
      'window.scrollTo(0,0);'
    );
  }

  /**
   * Asserts the user is scrolled to the top of the page.
   *
   * @Then I should be a the top of the page
   *
   * @throws \RuntimeException
   *   When the expected scrol position doesn't match the actual one.
   */
  public function assertScrollPositionTopOfPage(): void {
    $scroll_y_position = $this->getSession()->getDriver()->evaluateScript('window.scrollY');
    if ((int) $scroll_y_position !== 0) {
      throw new \RuntimeException(sprintf(
        'Expected scroll position 0 does not match the actual scroll position %s.',
        $scroll_y_position
      ));
    }
  }

  /**
   * Scroll to element.
   *
   * @When I scroll the :selector element into view
   *
   * @throws \RuntimeException
   *   When the element with the given selector was not found.
   */
  public function iScrollIntoView(string $selector): void {
    if (empty($this->getSession()->getPage()->find('css', $selector))) {
      throw new \RuntimeException("No element with selector '$selector' found.");
    }

    $this->getSession()
      ->getDriver()
      ->executeScript("document.querySelector('$selector').scrollIntoView(true);");
  }

}
