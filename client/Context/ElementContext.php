<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\VahiBehatExtension\Enum\ElementState;

/**
 * Element Context.
 */
class ElementContext extends RawDrupalContext {

  use UtilityContextTrait;

  /**
   * Element map.
   *
   * Maps element name => selector.
   *
   * @var array
   */
  private $elementMap = [];

  /**
   * Default wait time.
   *
   * @var int
   */
  const DEFAULT_WAIT_TIME = 20;

  /**
   * Constructor.
   *
   * @param string[] $element_map
   *   Map of element names to selectors.
   */
  public function __construct(array $element_map = []) {
    $this->elementMap = $element_map;
  }

  /**
   * Transform element to NodeElement.
   *
   * @param string $element
   *   Element name or css selector.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element if found.
   *
   * @Transform :element
   */
  public function castElementStringToNodeElement(string $element): NodeElement {
    return $this->getElement($element);
  }

  /**
   * Get an element by it's mapped name or css selector.
   *
   * @param string $element_name_or_selector
   *   Element name or css selector.
   *
   * @return string
   *   The element selector.
   */
  public function getElementSelector(string $element_name_or_selector): string {
    $selector = $this->elementMap[$element_name_or_selector] ?? $element_name_or_selector;
    return $selector;
  }

  /**
   * Get an element by it's mapped name or css selector.
   *
   * @param string $element_name_or_selector
   *   Element name or css selector.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element if found.
   *
   * @throws Behat\Mink\Exception\ElementNotFoundException
   *   When the element is not found on the page.
   */
  public function getElement(string $element_name_or_selector): NodeElement {
    $selector = $this->getElementSelector($element_name_or_selector);
    $page = $this->getSession()->getPage();

    $element = $page->waitFor(
      self::DEFAULT_WAIT_TIME,
      function () use ($page, $selector) {
        $field = $page->find('css', $selector);
        if ($field instanceof NodeElement) {
          return $field;
        }
        else {
          $field = $page->find('named', ['id_or_name', $selector]);
          return ($field instanceof NodeElement) ? $field : FALSE;
        }
      }
    );
    return $element;
  }

  /*
   * Click on a element by CSS selector or named selector.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   Element.
   *
   * @Given I click the :element element

  public function clickElement(NodeElement $element): void {
  $element->click();
  }
   */

  /**
   * Click on a element by CSS selector or named selector.
   *
   * @param \Behat\Mink\Element\NodeElement|null $element
   *   Element.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the element isn't defined or visible on the page.
   *
   * @Given I click the :element element
   */
  public function clickElement(NodeElement $element = NULL): void {
    if (!$element instanceof NodeElement) {
      throw new ExpectationException(sprintf('element "%s" was not found.', $element), $this->getSession());
    }
    $element->click();
  }

  /**
   * Wait for an element to be visible.
   *
   * Doesn't fail if the element isn't visible.
   *
   * @param string $element_name
   *   Element.
   * @param string $state
   *   Desired element state.
   * @param int|float $wait_time
   *   How many seconds to wait for.
   *
   * @Given I wait up to :wait_time seconds for :element_name_or_selector element to be :state
   * @Given I wait for :element_name_or_selector element to be :state
   */
  public function waitForElementState(string $element_name, string $state, float $wait_time = self::DEFAULT_WAIT_TIME): bool {
    $selector = $this->getElementSelector($element_name);
    $page = $this->getSession()->getPage();

    return $page->waitFor(
      $wait_time,
      function () use ($page, $selector, $state) {
        $field = $page->find('css', $selector);

        switch ($state) {
          case ElementState::VISIBLE:
            return ($field instanceof NodeElement) ? $field->isVisible() : FALSE;

          case ElementState::NOT_VISIBLE:
            return ($field instanceof NodeElement) ? !$field->isVisible() : TRUE;

          case ElementState::FOCUSED:
            return ($field instanceof NodeElement) ? $this->isElementFocused($field) : FALSE;

          default:
            throw new ExpectationException(sprintf('Desired state "%s" was not matched.', $state), $this->getSession());
        }
      }
    );
  }

  /**
   * Assert an element is in a certain state.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   Element.
   * @param string $state
   *   Desired element state.
   *
   * @see Drupal\VahiBehatExtension\Enum\ElementState
   *
   * @Then the :element element should be :state
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the element isn't defined or visible on the page.
   */
  public function assertElementState(NodeElement $element, string $state): void {
    switch ($state) {
      case ElementState::VISIBLE:
        if ($element instanceof NodeElement && !$element->isVisible()) {
          throw new ExpectationException(sprintf('Element: %s is not visible.', $element->getXpath()), $this->getSession());
        }
        break;

      case ElementState::NOT_VISIBLE:
        if ($element instanceof NodeElement && $element->isVisible()) {
          throw new ExpectationException(sprintf('Element "%s" is visible', $element->getXpath()), $this->getSession());
        }
        break;

      case ElementState::FOCUSED:
        if ($element instanceof NodeElement && !$this->isElementFocused($element)) {
          throw new ExpectationException(sprintf('Element: %s is not focused.', $element->getXpath()), $this->getSession());
        }
        break;

      default:
        throw new ExpectationException(sprintf('Desired state "%s" was not matched.', $state), $this->getSession());
    }
  }

  /**
   * Wait for element and assert the field.
   *
   * @param string $element_name
   *   Element.
   * @param string $value
   *   Assert the value in the field.
   *
   * @Then I wait for :element_name element and see :value
   *
   * @throws \ExpectationException
   *   When user attribute doesnot match.
   */
  public function waitForAndAssertElement(string $element_name, string $value): void {
    $this->waitForElementState($element_name, ElementState::VISIBLE);
    $actual_value = $this->getElement($element_name)->getValue();
    if ($actual_value !== $value) {
      throw new ExpectationException(sprintf('User attribute doesnot match : Actual "%s" and Expected "%s".', $actual_value, $value), $this->getSession());
    }
  }

  /**
   * Wait for and fill in a field.
   *
   * @param string $element_name
   *   Element.
   * @param string $value
   *   Value to fill into the field.
   *
   * @Given I wait for :element_name element and fill in :value
   */
  public function waitForAndFillElement(string $element_name, string $value): void {
    $this->waitForElementState($element_name, ElementState::VISIBLE);
    $this->getElement($element_name)->setValue($value);
  }

  /**
   * Attaches file to element with specified id|name|label|value.
   *
   * Example: When I attach "bprofile.png" to "profileImageUpload".
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   Element.
   * @param string $path
   *   Path to the file to be attached.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element isn't defined or visible on the page.
   *
   * @When I attach the file :path to the :element element
   */
  public function attachFileToElement(NodeElement $element, string $path): void {
    if ($this->getMinkParameter('files_path')) {
      $full_path = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
      if (is_file($full_path)) {
        $path = $full_path;
      }
    }

    $element->attachFile($path);
  }

  /**
   * Verifies whether an element is focused.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   Element.
   *
   * @return bool
   *   Is the element focused.
   */
  public function isElementFocused(NodeElement $element): bool {
    $this->assertDriverIsSelenium2($this->getSession()->getDriver(), 'Getting focused element not supported with the selected driver.');
    $webdriver_session = $this->getSession()->getDriver()->getWebDriverSession();
    $expected_element = $webdriver_session->element('xpath', $element->getXpath());

    return $webdriver_session->activeElement()->equals($expected_element->getID());
  }

  /**
   * Set focus on an element.
   *
   * @param Behat\Mink\Element\NodeElement $element
   *   Element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element isn't defined or visible on the page.
   *
   * @Given I focus on the :element element
   */
  public function focusElement(NodeElement $element): void {
    $this->getSession()->getDriver()->focus($element->getXpath());
  }

}
