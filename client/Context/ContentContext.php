<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Content Context.
 */
class ContentContext extends RawDrupalContext {

  /**
   * Assert a link with given text and URL.
   *
   * @Then I should see the :link_text link with URL :link_url
   *
   * @throws \Exception
   *   When the link with the specified text could not be found.
   */
  public function iShouldSeeLinkWithTextAndUrl(string $link_text, string $link_url): void {
    $link = $this->getSession()->getPage()->findLink($link_text);
    if (!$link instanceof NodeElement) {
      throw new \Exception(sprintf(
        'No link with text "%s" found on the page %s',
        $link_text,
        $this->getSession()->getCurrentUrl()
      ));
    }

    $this->assertLinkAndText($link, $link_url);
  }

  /**
   * Assert a link with given text and URL in a given region.
   *
   * @Then I should see the :link_text link with URL :link_url in the :region region
   *
   * @throws \Exception
   *   When the region could not be found on the page.
   *   When the link with specified text could not be found in the given region.
   */
  public function iShouldSeeLinkWithTextAndUrlInRegion(string $link_text, string $link_url, string $region_name): void {
    $region = $this->getSession()->getPage()->find('region', $region_name);
    if (!$region instanceof NodeElement) {
      throw new \Exception(sprintf(
        'Region "%s" was not found on the page',
        $region_name
      ));
    }

    $link = $region->findLink($link_text);
    if (!$link instanceof NodeElement) {
      throw new \Exception(sprintf(
        'No link with text "%s" found in the region %s on the page %s',
        $link_text,
        $region_name,
        $this->getSession()->getCurrentUrl()
      ));
    }

    $this->assertLinkAndText($link, $link_url);
  }

  /**
   * Assert a link with given URL.
   *
   * @Then I should see the link with URL :link_url in the :region region
   *
   * @throws \Exception
   *   When the link with the specified URL could not be found.
   */
  public function iShouldSeeLinkWithUrl(string $link_url, string $region_name): void {
    $region = $this->getSession()->getPage()->find('region', $region_name);
    if (!$region instanceof NodeElement) {
      throw new \Exception(sprintf(
        'Region "%s" was not found on the page',
        $region_name
      ));
    }

    $link = $region->findLink($link_url);
    if (!$link instanceof NodeElement) {
      throw new \Exception(sprintf(
        'No link with URL "%s" found in the region %s on the page %s',
        $link_url,
        $region_name,
        $this->getSession()->getCurrentUrl()
      ));
    }

    $this->assertLinkAndText($link, $link_url);
  }

  /**
   * Asserts a link's visibility and text.
   *
   * @param \Behat\Mink\Element\NodeElement $link
   *   Link to assert.
   * @param string $expected_link_text
   *   Expected link text.
   *
   * @throws \Exception
   *   When the link is not visible or the URL doesn't match the expectation.
   */
  private function assertLinkAndText(NodeElement $link, string $expected_link_text): void {
    if (!$link->isVisible()) {
      throw new \Exception(sprintf(
        'Link with text "%s" is not visible',
        $expected_link_text
      ));
    }
    if ($link->getAttribute('href') !== $expected_link_text) {
      throw new \Exception(sprintf(
        'Link has URL "%s" not "%s"',
        $link->getAttribute('href'),
        $expected_link_text
      ));
    }

  }

}
