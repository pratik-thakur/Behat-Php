<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Context for geolocations tests.
 */
class GeolocationContext extends RawDrupalContext {

  /**
   * Whether geolocation is supported.
   *
   * @var bool
   */
  protected $isGeolocationSupported = TRUE;

  /**
   * Mocks the user's current coordinates.
   *
   * @param string $coordinates
   *   Latitude and longitude, in the format "<latitude>,<longitude>".
   *
   * @Given my current location is :coordinates
   *
   * @todo Re-factor to mock the native JavaScript API
   */
  public function setCurrentLocation(string $coordinates): void {
    [$latitude, $longitude] = explode(',', $coordinates);

    $js = sprintf('
      localStorage.setItem("latitude", "%s");
      localStorage.setItem("longitude", "%s");
      localStorage.setItem("geolocation.expiry_time", "%d");
    ', $latitude, $longitude, (int) microtime(TRUE) * 1000);

    $this->getSession()->executeScript($js);
  }

  /**
   * Unset geolocation by clearing local storage.
   *
   * @Given geolocation is not set
   */
  public function unsetGeolocation(): void {
    $js = sprintf('
      localStorage.removeItem("latitude");
      localStorage.removeItem("longitude");
      localStorage.removeItem("geolocation.expiry_time");
    ');

    $this->getSession()->executeScript($js);
  }

}
