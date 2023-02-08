<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException as ExpectationException;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Image Context.
 */
class ImageContext extends RawDrupalContext {

  /**
   * Check if image with alt tag exists on the page.
   *
   * @Then I should see an image with tag :imagetag on the page
   *
   * @throws \ExpectationException
   *   When the image is not found.
   */
  public function iShouldSeeImageWithAltTagOnPage(string $image_tag, NodeElement $container = NULL): void {
    // Find the image.
    $selector = "img[alt='$image_tag']";
    $container = is_null($container) ? $this->getSession()->getPage() : $container;
    $image = $container->find('css', $selector);
    if (!$image instanceof NodeElement) {
      throw new ExpectationException('The image with alt tag ' . $image_tag . ' could not be found', $this->getSession());
    }
    if (!$image->isVisible()) {
      throw new ExpectationException('The image with alt tag ' . $image_tag . ' was not visible', $this->getSession());
    }
  }

  /**
   * Check if image with URL exists on the page.
   *
   * @Then I should see an image with URL :imageUrl on the page
   *
   * @throws \ExpectationException
   *   When the image is not found.
   */
  public function iShouldSeeImageWithUrlOnPage(string $imageUrl): void {
    // Find the image.
    $selector = "img[src*='$imageUrl']";
    $image = $this->getSession()->getPage()->find('css', $selector);
    if (!$image->isVisible()) {
      throw new ExpectationException('The image with URL ' . $imageUrl . ' was not visible', $this->getSession());
    }
  }

}
