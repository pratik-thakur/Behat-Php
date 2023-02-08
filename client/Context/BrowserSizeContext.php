<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Context class to modify browser window size.
 *
 * This context allows easy browser window size change to a predefined sizes.
 *
 * Context params:
 *   'sizes': An array of predefined sizes. Each entry is a key-value pair with
 *     the key eing the size name and the value and array with the width and
 *     the height for that size.
 *
 * @see __construct().
 */
class BrowserSizeContext extends RawDrupalContext {

  /**
   * Context sizes.
   *
   * @var array
   */
  protected $customSizes;

  /**
   * Constructor.
   *
   * Save class params, if any.
   *
   * @param mixed[][] $sizes
   *   A list of sizes to setup BrowserSizeContext.
   */
  public function __construct(array $sizes = []) {
    // Default value for screen size.
    // This matches Bootstrap 3 breakpoints.
    // @see https://getbootstrap.com/docs/3.4/customize/#media-queries-breakpoints
    // Extra must be added to the width, to account for the browser edge.
    $width_offset = 10;
    $this->customSizes = [
      'screen-xs' => ['width' => 485 + $width_offset, 'height' => 1200],
      'screen-sm' => ['width' => 768 + $width_offset, 'height' => 1200],
      'screen-md' => ['width' => 992 + $width_offset, 'height' => 1200],
      'screen-lg' => ['width' => 1200 + $width_offset, 'height' => 1200],
    ];
    // Collect received sizes.
    if (!empty($sizes)) {
      // Filter any invalid sizes.
      $parameters_filtered = array_diff_key($sizes, $this->customSizes);
      // Apply sizes.
      $this->customSizes = array_replace_recursive($this->customSizes, $parameters_filtered);
    }
  }

  /**
   * Set browser window size to default (screen-lg).
   *
   * @BeforeScenario
   */
  public function setDefaultBrowserSize(): void {
    $this->browserWindowSizeIs('screen-lg');
  }

  /**
   * Set browser window size to screen-xs.
   *
   * @BeforeScenario @screen-xs
   */
  public function setBrowserWindowSizeXs(): void {
    $this->browserWindowSizeIs('screen-xs');
  }

  /**
   * Set browser window size to screen-sm.
   *
   * @BeforeScenario @screen-sm
   */
  public function setBrowserWindowSizeSm(): void {
    $this->browserWindowSizeIs('screen-sm');
  }

  /**
   * Set browser window size to screen-md.
   *
   * @BeforeScenario @screen-md
   */
  public function setBrowserWindowSizeMd(): void {
    $this->browserWindowSizeIs('screen-md');
  }

  /**
   * Set browser window size to screen-lg.
   *
   * @BeforeScenario @screen-lg
   */
  public function setBrowserWindowSizeLg(): void {
    $this->browserWindowSizeIs('screen-lg');
  }

  /**
   * Step to resize the window to a given size.
   *
   * This step is executed only if the stage has the tag @javascript.
   *
   * @Given (that) browser window size is :size size
   *
   * @throws \InvalidArgumentException
   *   When the provided size is unknown.
   */
  public function browserWindowSizeIs(string $size): void {
    if (array_key_exists($size, $this->customSizes)) {
      $size = $this->customSizes[$size];
      if (!$this->getSession()->isStarted()) {
        $this->getSession()->start();
      }
      $this->getSession()->resizeWindow($size['width'], $size['height'], 'current');
    }
    else {
      $sizes = [];
      foreach ($this->customSizes as $size_name => $size_dimensions) {
        $sizes[] = "\"$size_name\" (" . $size_dimensions['width'] . "x" . $size_dimensions['height'] . ')';
      }
      throw new \InvalidArgumentException("Unknown size $size. It should be one of: " . implode(', ', $sizes));
    }
  }

}
