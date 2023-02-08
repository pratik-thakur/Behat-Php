<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Google Maps Context.
 */
class GoogleMapsContext extends RawDrupalContext {

  use UtilityContextTrait;

  /**
   * Verified Google maps is rendered in a given region.
   *
   * @Then I should see a Google map in the :region region
   *
   * @todo Re-factor to assert a Google map coordinates.
   *
   * @throws \Exception
   *   When the Google Map was not loaded in the specified region.
   */
  public function assertGoogleMapInRegion(string $region): void {
    // @todo Assert the coordinates:
    // @codingStandardsIgnoreStart
    // $map_lat = $this->getSession()->evaluateScript('
    //   try {
    //     return drupalSettings.geofield_google_map['geofield-map-view-search-attachment-1'].map_settings.map.getCenter().lat();
    //   }
    //   catch (e) {}
    // ');
    // $map_lng = $this->getSession()->evaluateScript('
    //   try {
    //     return drupalSettings.geofield_google_map['geofield-map-view-search-attachment-1'].map_settings.map.getCenter().lng();
    //   }
    //   catch (e) {}
    // ');
    // @codingStandardsIgnoreEnd
    $google_maps = $this->googleMapsLoadedInRegion($region);
    if (!$google_maps) {
      throw new \Exception(sprintf('No Google map loaded in region "%s".', $region));
    }
  }

  /**
   * Verified Google maps is not rendered in a given region.
   *
   * @Then I should not see a Google map in the :region region
   *
   * @throws \Exception
   *   When the Google Map is loaded in the specified region.
   */
  public function assertNoGoogleMapInRegion(string $region): void {
    $google_maps = $this->googleMapsLoadedInRegion($region);
    if ($google_maps) {
      throw new \Exception(sprintf('Google map is loaded in region "%s".', $region));
    }
  }

  /**
   * Returns the number of Google Maps loaded in a given region.
   *
   * @param string $region
   *   Region to look for Google Maps.
   */
  public function googleMapsLoadedInRegion(string $region): int {
    $region_selector = $this->getRegion($region);
    $google_maps_in_region = $this->getSession()
      ->evaluateScript(sprintf('
        return Object.values(drupalSettings.geofield_google_map).map(map_config =>
          document.querySelector("%s #" + map_config.mapid)).filter(i => i).length;
      ', $region_selector));

    return intval($google_maps_in_region);
  }

}
