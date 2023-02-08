<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * VAHI Context.
 */
class VahiContext extends RawDrupalContext {

  /**
   * The Drupal context.
   *
   * @var \Drupal\DrupalExtension\Context\DrupalContext|null
   */
  private $drupalContext;

  /**
   * Mink context.
   *
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  private $minkContext;

  /**
   * Gather the contexts available before running the scenario.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope): void {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext('Drupal\DrupalExtension\Context\DrupalContext');
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

  /**
   * Hover on a desktop menu item with given text.
   *
   * @Given I hover on the :element_text desktop menu item
   *
   * @throws \Exception
   *   When the desktop menu item could not be found.
   */
  public function iHoverOnTheDesktopMenuItem(string $element_text): void {
    $menu_item = $this->getSession()
      ->getPage()
      ->find(
        'xpath',
        "//div[@class='visible-lg']//span[contains(@class, 'vahi-main-menu__link') and normalize-space(.)='{$element_text}']"
      );
    if (!$menu_item instanceof NodeElement) {
      $menu_item = $this->getSession()
        ->getPage()
        ->find(
        'xpath',
        "//div[@class='visible-lg']//a[contains(@class, 'vahi-main-menu__link') and normalize-space(.)='{$element_text}']"
        );
    }
    if (!$menu_item instanceof NodeElement) {
      throw new \Exception(sprintf('Could not find desktop menu item with text "%s".', $element_text));
    }

    $menu_item->getParent()->mouseOver();
  }

  /**
   * Hover on a desktop menu item with given text.
   *
   * @Given I hover on the :element_text compendium menu item
   *
   * @throws \Exception
   *   When the desktop menu item could not be found.
   */
  public function iHoverOnTheCompendiumDesktopMenuItem(string $element_text): void {
    $menu_item = $this->getSession()
      ->getPage()
      ->find(
        'xpath',
        ".//div[@class='visible-lg']//div[@class='vahi-main-menu']/ul/li/div/div[@class='vahi-compendium']/ul/li/a[contains(@title, '{$element_text}')]"
      );
    if (!$menu_item instanceof NodeElement) {
      throw new \Exception(sprintf('Could not find compendium menu item with text "%s".', $element_text));
    }

    $menu_item->getParent()->mouseOver();
    usleep(intval(0.2 * 1000000));
  }

  /**
   * Check if a type of element has an attribute with an specific value.
   *
   * @param string $element_text
   *   Element text.
   * @param mixed $attribute
   *   Expected attribute.
   * @param mixed $value
   *   Expected value.
   *
   * @throws \Exception
   *
   * @Then the :element element should have the :attribute attribute with :value value
   */
  public function theElementShouldHaveAttributeWithValue(string $element_text, mixed $attribute, mixed $value): void {
    $element = $this->getSession()
      ->getPage()
      ->find(
      'xpath',
      ".//a[contains(text(),'Link2')]//parent::li//parent::ul"
    );
    if (!$element instanceof NodeElement) {
      throw new \Exception(sprintf('Could not find compendium menu item with text "%s".', $element_text));
    }
    \Drupal::logger('getAttribute')->info(__LINE__ . ':' . print_r($element->getAttribute('style'), TRUE));
    \Drupal::logger('getAttribute')->info(__LINE__ . ':' . print_r($element->getAttribute('class'), TRUE));

    \Drupal::logger('getOuterHtml')->info(__LINE__ . ':' . print_r($element->getOuterHtml(), TRUE));
    \Drupal::logger('getValue')->info(__LINE__ . ':' . print_r($element->getValue(), TRUE));
    if ($element->getAttribute($attribute) !== $value) {
      throw new \Exception(sprintf(
        'Attribute "%s" for element "%s" not found',
        $attribute,
        $element_text
      ));
    }
  }

  /**
   * Login a user with a given role, and accept the user terms and conditions.
   *
   * @Given I am logged in as a VAHI user with the :role role(s)
   * @Given I am logged in as a/an VAHI :role
   */
  public function iLoggedInAndAcceptUserTermsAndConditions(string $role): void {
    $this->iLoggedInAndAcceptUserTermsAndConditionsWithFields($role, new TableNode([]));
  }

  /**
   * Login a user with given fields, and accept the user terms and conditions.
   *
   * @Given I am logged in as a VAHI user with the :role role(s) and I have the following fields:
   */
  public function iLoggedInAndAcceptUserTermsAndConditionsWithFields(string $role, TableNode $fields): void {
    $table = $fields->getTable();
    $table[] = [
      'field_terms_conditions_status',
      'ACCEPTED',
    ];
    $table[] = [
      'field_terms_conditions_timestamp',
      time(),
    ];

    $this->drupalContext->assertAuthenticatedByRoleWithGivenFields(
      $role,
      new TableNode($table)
    );
  }

}
