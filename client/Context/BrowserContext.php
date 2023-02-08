<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Browser Context.
 */
class BrowserContext extends RawDrupalContext {
  use UtilityContextTrait;

  /**
   * Switch to a given iframe.
   *
   * @param string $iframe_selector
   *   A CSS selector for the iframe.
   *
   * @Given I switch to the :iframe_selector iframe
   */
  public function iSwitchToIframe(string $iframe_selector): void {
    // Find the iframe (wait a while until it's available on the page).
    $iframe = $this->spin(\Closure::bind(function () use ($iframe_selector) {
      return $this->getSession()->getPage()->find('css', $iframe_selector);
    }, $this));

    // The driver's `switchToIFrame()` method expects the iframe to be named.
    $iframe_name = $iframe->getAttribute('name');
    if (!$iframe_name) {
      $iframe_name = 'behat-iframe';
      $javascript = "
        (function () {
          // Remove the given name from all iframes on the page.
          var iframes = document.querySelectorAll('iframe[name=$iframe_name]');
          for (iframe of iframes) {
            iframe.name = '';
          }
          // Set the name for the iframe we're switching to.
          document.querySelector('$iframe_selector').name = '$iframe_name';
        })()
      ";
      $this->getSession()->executeScript($javascript);
    }

    $this->getSession()->getDriver()->switchToIFrame($iframe_name);
  }

  /**
   * Switch to the main frame.
   *
   * @Given I switch to the main frame
   */
  public function iSwitchToTheMainFrame(): void {
    $this->getSession()->getDriver()->switchToIFrame();
  }

  /**
   * Step to switch to a given window (tab) by index (starting from 1).
   *
   * @Given I switch to window :index
   */
  public function switchToWindow(int $window_index): void {
    $window_names = $this->getSession()->getWindowNames();
    $this->getSession()->switchToWindow($window_names[$window_index - 1]);
  }

  /**
   * Restart the browser.
   *
   * Sometimes clearing cookies using getDriver()->reset() isn't enough.
   * Tests requiring a brand new browser profile (e.g. OAuth login) can call
   * this to restart the browser with a clean profile.
   *
   * Tests tagged with the @restart-browser tag will automatically call this.
   *
   * @Given I restart the browser
   * @BeforeScenario @restart-browser
   * @AfterScenario @restart-browser
   */
  public function iAmInaCleanBrowser(): void {
    $this->getSession()->getDriver()->reset();
    $this->getSession()->getDriver()->stop();
    $this->getSession()->getDriver()->start();
  }

  /**
   * Close the current tab.
   *
   * @Given I close the current tab
   */
  public function closeCurrentTab(): void {
    $this->getSession()->executeScript("window.open('','_self').close();");
  }

  /**
   * Click back button.
   *
   * @Given I press back button
   */
  public function iPressBackButton(): void {
    $webdriver_session = $this->getSession()->getDriver();
    $webdriver_session->back();
  }

}
