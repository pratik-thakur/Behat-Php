<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Drupal\Core\Cache\Cache;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\ScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Url;
use NuvoleWeb\Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Logger\RfcLogLevel;
use Behat\Mink\Exception\ExpectationException;
use Drupal\user\Entity\Role;
use Behat\Gherkin\Node\PyStringNode;
use NuvoleWeb\Drupal\DrupalExtension\Component\PyStringYamlParser;

if (!defined('LANGUAGE_NONE')) {
  define('LANGUAGE_NONE', 'und');
}

/**
 * Defines common VAHI Portal Behat Drupal vocabs.
 */
class DrupalContext extends RawDrupalContext {

  use UtilityContextTrait;

  /**
   * The Drupal context.
   *
   * @var \NuvoleWeb\Drupal\DrupalExtension\Context\DrupalContext|null
   */
  private $drupalContext;
  const CACHE_ID = 'powerbi.embed_access_token';

  /**
   * Test start time.
   *
   * @var int
   */
  private $testStartTime;

  /**
   * Warn instead of fail.
   *
   * Determines whether php errors should be reported
   * as warnings or failures.
   *
   * @var bool
   */
  private $warnInsteadOfFail = FALSE;

  /**
   * Threshold for alerting of PHP errors.
   *
   * @var int
   *
   * @see Drupal\Core\Logger\RfcLogLevel
   */
  private $phpLoggingThreshold = RfcLogLevel::NOTICE;

  /**
   * Constructor.
   *
   * @param bool $php_error_warn_instead_of_fail
   *   Flag to log instead of failing when a php error is thrown.
   * @param string $php_error_threshold
   *   Threshold for alerting of PHP errors.
   */
  public function __construct(bool $php_error_warn_instead_of_fail = FALSE, string $php_error_threshold = 'NOTICE') {
    $this->warnInsteadOfFail = $php_error_warn_instead_of_fail;
    $this->phpLoggingThreshold = constant(RfcLogLevel::class . '::' . strtoupper($php_error_threshold));
  }

  /**
   * Gather the contexts available before running the scenario.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope): void {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext('NuvoleWeb\Drupal\DrupalExtension\Context\DrupalContext');

    // Clear the watchdog table before the test.
    $db = \Drupal::database();
    if ($db->schema()->tableExists('watchdog')) {
      $db->delete('watchdog')
        ->execute();
    }

    $this->testStartTime = time();
  }

  /**
   * Check for errors since the scenario started.
   *
   * @throws \Exception
   *   If PHP errors have been logged.
   *
   * @AfterScenario ~@disable-check--watchdog
   */
  public function checkWatchdog(AfterScenarioScope $scope): void {
    // Bypass the error checking if scenario tagged @disable-check--watchdog.
    if (in_array('disable-check--watchdog', $scope->getScenario()->getTags())) {
      return;
    }

    $db = \Drupal::database();
    if ($db->schema()->tableExists('watchdog')) {
      $log = $db->select('watchdog', 'w')
        ->fields('w')
        ->condition('w.type', ['php', 'powerbi', 'vahi_oauth_client'], 'IN')
        ->condition('w.severity', $this->phpLoggingThreshold, '<=')
        ->execute()
        ->fetchAll();

      if (!empty($log)) {
        foreach ($log as $error) {
          $error->variables = json_decode($error->variables);
          $formattedError = new FormattableMarkup($error->message, $error->variables);
          print_r(sprintf("Watchdog %s error found:\n%s\nLocation: %s\n",
            $error->type,
            strip_tags(html_entity_decode((string) $formattedError)),
            $error->location
          ));
        }
        $summary = sprintf('%s PHP errors logged to watchdog in this scenario.', count($log));
        if ($this->warnInsteadOfFail) {
          print_r($summary);
        }
        else {
          throw new \Exception($summary);
        }
      }
    }
  }

  /**
   * Clear Cache before the scenario started.
   *
   * @BeforeScenario @clear-cache--powerbi
   */
  public function clearCache(BeforeScenarioScope $scope): void {
    if (in_array('clear-cache--powerbi', $scope->getScenario()->getTags())) {
      Cache::getBins()['default']->deleteAll();
      return;
    }
  }

  /**
   * Create an entity defined in YAML format.
   *
   * @param \Behat\Gherkin\Node\String $entity
   *   The entity type to create.
   * @param \Behat\Gherkin\Node\PyStringNode $string
   *   The text in yaml format that represents the content.
   *
   * @Given the following :entity content:
   */
  public function createEntity(String $entity, PyStringNode $string): void {
    $parser = new PyStringYamlParser();
    $values = $parser->parse($string);
    $entity = $this->getCore()->entityCreate($entity, $values);

    $this->entities[] = $entity;
  }

  /**
   * Check for errors since the scenario started.
   *
   * @throws \Exception
   *   If PHP errors have been logged.
   *
   * @BeforeScenario
   * @AfterScenario
   */
  public function removeAllEntities(ScenarioScope $scope): void {
    $tags = array_merge($scope->getFeature()->getTags(), $scope->getScenario()->getTags());
    foreach ($tags as $value) {
      if (strpos($value, 'remove-all-entities--') !== FALSE) {
        $entity = explode('--', $value)[1];
        $entity_storage = \Drupal::entityTypeManager()->getStorage($entity);
        $entities = $entity_storage->loadMultiple();
        $entity_storage->delete($entities);
      }
    }
  }

  /**
   * Clicks on the "Find near me" button within a given region.
   *
   * @param string $region
   *   Region to look for the "Find near me" link.
   *
   * @When I click on Find near me in the :region region
   *
   * @todo Remove this function and use the native click step.
   */
  public function findNearMeInRegion(string $region): void {
    $page = $this->getSession()->getPage();
    $region_selector = $this->getRegion($region);

    $findSelector = $region_selector . ' a.find';
    $findNearMeLink = $page->find('css', $findSelector);
    $findNearMeLink->press();
  }

  /**
   * Clicks on a checkbox element within a region.
   *
   * @param string $checkbox
   *   A checkbox option.
   * @param string $region
   *   Region to look for the checkbox.
   *
   * @When I check the box :checkbox in the :region region
   *
   * @throws \Exception
   *   When the filter option could not be found on the page.
   *
   * @todo Re-factor to extend existing vocab
   */
  public function assertCheckBox(string $checkbox, string $region): void {
    $session = $this->getSession();
    $region_selector = $this->getRegion($region);
    $mobile_filters = [
      'Private hospitals'    => '#edit-field-health-service-type-13--3',
      'Health services'      => '#edit-field-health-service-type-14--3',
      'Public hospitals'     => '#edit-field-health-service-type-12--3',
      'Metropolitan'         => '#edit-field-location-15--3',
      'Regional'             => '#edit-field-location-16--3',
      'Rural'                => '#edit-field-location-17--3',
      'Small rural'          => '#edit-field-location-18--3',
      'Child and adolescent' => '#edit-field-age-group-131--3',
      'Adult'                => '#edit-field-age-group-136--3',
      'Aged persons'         => '#edit-field-age-group-141--3',
    ];
    $filters = [
      'Health services'      => '#edit-field-health-service-type-14--6',
      'Private hospitals'    => '#edit-field-health-service-type-13--6',
      'Public hospitals'     => '#edit-field-health-service-type-12--6',
      'Metropolitan'         => '#edit-field-location-15--6',
      'Regional'             => '#edit-field-location-16--6',
      'Rural'                => '#edit-field-location-17--6',
      'Small rural'          => '#edit-field-location-18--6',
      'Child and adolescent' => '#edit-field-age-group-131--6',
      'Adult'                => '#edit-field-age-group-136--6',
      'Aged persons'         => '#edit-field-age-group-141--6',
    ];

    if (!array_key_exists($checkbox, $filters)) {
      throw new \Exception(sprintf('No "%s" filter option found on the page %s.', $checkbox, $session->getCurrentUrl()));
    }

    $selector = $filters[$checkbox] ?? '';
    if ($region === 'mobile filters') {
      $selector = $mobile_filters[$checkbox];
    }

    $js = sprintf('
      document.querySelector("%s %s").click();
    ', $region_selector, $selector);

    $this->getSession()->executeScript($js);
  }

  /**
   * Select a number of items per page option in a given region.
   *
   * @param string $numberOfItems
   *   The number of items to display per page.
   * @param string $region
   *   Region to look for the items per page selector.
   *
   * @When I select :numberOfItems items per page in the :region region
   */
  public function iSelectItemsPerPageInRegion(string $numberOfItems, string $region): void {
    $region_selector = $this->getRegion($region);

    $js = sprintf('
      jQuery("%s #edit-items-per-page").val("%s");
    ', $region_selector, $numberOfItems);

    $this->getSession()->executeScript($js);
  }

  /**
   * Click on a letter link in a given region.
   *
   * @param string $letter
   *   A letter link.
   * @param string $region
   *   Region to look for the letter link.
   *
   * @throws \Exception
   *   When the leter link wasn't found on the page.
   *
   * @When I click letter :letter in the :region region
   */
  public function iClickLetterInTheRegion(string $letter, string $region): void {
    $letter = strtoupper($letter);
    $session = $this->getSession();
    $page = $session->getPage();
    $region_selector = $this->getRegion($region);

    $letterSelector = $region_selector . ' .view-content span a';
    $letterLinks = $page->findAll('css', $letterSelector);

    if (!$letterLinks) {
      throw new \Exception(sprintf('No letter links found on the page %s.', $session->getCurrentUrl()));
    }

    $availableLetters = [];
    foreach ($letterLinks as $letterLink) {
      $availableLetters[$letterLink->getText()] = $letterLink;
    }

    if (!array_key_exists($letter, $availableLetters)) {
      throw new \Exception(sprintf('No letter "%s" link found on the page %s.', $letter, $session->getCurrentUrl()));
    }

    $availableLetters[$letter]->click();
  }

  /**
   * Wait for page to load.
   *
   * @When I wait for the page to be loaded
   */
  public function iWaitForThePageToBeLoaded(): void {
    $this->getSession()->wait(10000, "document.readyState === 'complete'");
  }

  /**
   * I should be logged in.
   *
   * @Then I should be logged in
   *
   * @throws \Exception
   *   When the user is not logged in.
   */
  public function assertLoggedIn(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    if (!$session->isStarted() || !$page) {
      return;
    }

    if (!$page->has('css', $this->getDrupalSelector('logged_in_selector'))) {
      throw new \Exception('User is logged in.');
    }
  }

  /**
   * I should not be logged in.
   *
   * @Then I should not be logged in
   *
   * @throws \Exception
   *   When the user is logged in.
   */
  public function assertLoggedOut(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    if (!$session->isStarted() || !$page) {
      return;
    }

    if ($page->has('css', $this->getDrupalSelector('logged_in_selector'))) {
      throw new \Exception('User is logged in.');
    }
  }

  /**
   * Exit.
   *
   * @Then I quit
   */
  public function iQuit(): void {
    exit;
  }

  /**
   * Transform tokens in tables.
   *
   * @Transform table:*
   */
  public function tableTokenReplace(TableNode $table): TableNode {
    return new TableNode(array_map(\Closure::bind(function ($row) {
      foreach ($row as &$cell) {
        $cell = $this->tokenReplace($cell);
      }
      return $row;
    }, $this), $table->getRows()));
  }

  /**
   * Transform tokens.
   *
   * @Transform /^(.*\[.*\].*)$/
   *
   * @throws \Exception
   *   When the user token contains an unknown property.
   */
  public function tokenReplace(string $string): string {
    preg_match_all('/\[([^:]*)\:([^\]]*)\]/', $string, $matches);

    for ($i = 0; $i < count($matches[0]); $i++) {
      $token = $matches[1][$i];
      $token_parts = explode('|', $matches[2][$i]);
      $arguments = $token_parts[0];
      $post_process = $token_parts[1] ?? NULL;
      $token_text = '';

      switch ($token) {
        case 'uri':
          $token_text = Url::fromUri($arguments)->toString();
          break;

        case 'url':
          $token_text = Url::fromUri($arguments, ['absolute' => TRUE])->toString();
          // Drupal produces URLs with HTTP schemes using the driver.
          // Assume we're always on HTTPs.
          $token_text = str_replace('http:', 'https:', $token_text);
          break;

        case 'config':
          $settings = explode(':', $arguments);
          $config = \Drupal::config($settings[0]);
          $token_text = $config->get($settings[1]);
          break;

        case 'now':
          // The date format may contain ':' characters, so they must be
          // escaped. E.g. '[now:H\:i\:s:+2 days]' uses the format 'H:i:s'.
          $arguments = preg_split('~(?<!\\\)' . preg_quote(':', '~') . '~', $arguments);
          $date_format = str_replace('\\:', ':', ($arguments[0] ?? ''));
          $date_timestamp = !empty($arguments[1]) ? strtotime($arguments[1]) : time();
          $token_text = date($date_format, $date_timestamp);
          break;

        case 'user':
          $current_user = $this->getUserManager()->getCurrentUser();
          if (!property_exists($current_user, $arguments)) {
            throw new \Exception(sprintf('Unknown user attribute "%s"', $arguments));
          }
          $token_text = $current_user->{$arguments};
          break;
      }

      // Post process the value by calling a function on it.
      if ($post_process) {
        if (function_exists($post_process)) {
          $token_text = $post_process($token_text);
        }
        else {
          throw new \Exception(sprintf('Unknown function %s', $post_process));
        }
      }

      if (!empty($token_text)) {
        $string = str_replace($matches[0][$i], $token_text, $string);
      }
    }

    // If the string is a URL, encode the query parameters.
    if (filter_var($string, FILTER_VALIDATE_URL)) {
      $url_parts = parse_url($string);
      if (!empty($url_parts['query'])) {
        $string = sprintf('%s://%s%s', $url_parts['scheme'], $url_parts['host'], $url_parts['path']);
        parse_str($url_parts['query'], $query_params);
        $string .= '?' . http_build_query($query_params);
      }
    }

    return $string;
  }

  /**
   * Remove a node.
   *
   * @throws \Exception
   *   When entity can't be deleted.
   *
   * @Given I delete the :title :node
   */
  public function deleteEntity(string $title): void {
    try {
      $entity = $this->getCore()->loadNodeByName($title);
    }
    catch (\Exception $e) {
      // If an exception is thrown it is likely the node already doesn't exist.
      return;
    }

    $this->getCore()->entityDelete('node', $entity);
  }

  /**
   * Remove all nodes of a content type.
   *
   * @Given I delete all the :content_type
   */
  public function entityDelete(string $content_type): void {
    $ids = \Drupal::entityQuery('node')
      ->condition('type', $content_type)
      ->execute();

    $storage_handler = \Drupal::entityTypeManager()->getStorage('node');
    $entities = $storage_handler->loadMultiple($ids);
    $storage_handler->delete($entities);
  }

  /**
   * Check the permission for a given role.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the permission doesn't match.
   *
   * @Given the following permissions are set:
   */
  public function checkPermission(TableNode $fields): void {
    $errors = [];

    foreach ($fields->getColumnsHash() as $value) {
      try {
        $permitted = $value['is_permitted'] === 'is permitted' ? TRUE : FALSE;

        $roles = user_role_names();

        $role_id = array_search($value['Role'], $roles);

        if (!$role = Role::load($role_id)) {
          throw new ExpectationException(sprintf('No role "%s" exists.', $role_id), $this->getSession());
        }

        // Check for permission.
        if ($role->hasPermission($value['Permission']) !== $permitted) {
          throw new ExpectationException(sprintf('Role: %s Permission: %s Expected: %s', $value['Role'], $value['Permission'], $value['is_permitted']), $this->getSession());
        }
      }
      catch (ExpectationException $e) {
        array_push($errors, $e->getMessage());
      }
    }

    if (count($errors) > 0) {
      throw new ExpectationException('The following permissions were incorrect:' . PHP_EOL . implode(PHP_EOL, $errors), $this->getSession());
    }
  }

}
