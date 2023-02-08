<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\VahiBehatExtension\Enum\ElementState;

/**
 * Component Context.
 */
class ComponentContext extends RawDrupalContext {

  use UtilityContextTrait;

  /**
   * Markup context.
   *
   * @var \Drupal\DrupalExtension\Context\MarkupContext
   */
  private $markupContext;

  /**
   * Element context.
   *
   * @var \Drupal\VahiBehatExtension\Context\ElementContext
   */
  private $elementContext;

  /**
   * Image context.
   *
   * @var \Drupal\VahiBehatExtension\Context\ImageContext
   */
  private $imageContext;

  /**
   * Current step component name.
   *
   * @var string
   */
  private $componentName;

  /**
   * Gather the contexts available before running the scenario.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope): void {
    $environment = $scope->getEnvironment();

    $this->markupContext = $environment->getContext('Drupal\DrupalExtension\Context\MarkupContext');
    $this->elementContext = $environment->getContext('Drupal\VahiBehatExtension\Context\ElementContext');
    $this->imageContext = $environment->getContext('Drupal\VahiBehatExtension\Context\ImageContext');
  }

  /**
   * Time in seconds to wait for a component to be present.
   *
   * @var int
   */
  const WAIT_FOR_COMPONENT_TIMEOUT = 30;

  /**
   * Component map.
   *
   * Maps component name => selector.
   *
   * @var array
   */
  private $componentMap = [];

  /**
   * Constructor.
   *
   * @param string[] $component_map
   *   Map of component names to selectors.
   */
  public function __construct(array $component_map = []) {
    $this->componentMap = $component_map;
  }

  /**
   * Transform component to array of NodeElements.
   *
   * @param string $component
   *   Element name or css selector.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The components if found.
   *
   * @Transform :component
   */
  public function transformComponent(string $component): array {
    $this->componentName = $component;
    return $this->getComponents($component);
  }

  /**
   * Transform text match type to bool.
   *
   * @param string $match_type
   *   Element name or css selector.
   *
   * @return bool
   *   The components if found.
   *
   * @Transform :match_type
   */
  public function transformMatchType(string $match_type): bool {
    return ($match_type === 'with');
  }

  /**
   * Wait for a component to be visible on the page.
   *
   * @param string $component_name
   *   Components.
   *
   * @When I wait for the :component_name component to be visible
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the component is not visible and/or clickable after 30 seconds.
   */
  public function iWaitForComponent(string $component_name): void {
    $visible = $this->getSession()->getPage()->waitFor(
      self::WAIT_FOR_COMPONENT_TIMEOUT,
      function () use ($component_name) {
        try {
          $components = $this->getComponents($component_name);
          foreach ($components as $component) {
            if ($component->isVisible()) {
              return TRUE;
            }
          }
        }
        catch (ElementNotFoundException $e) {
        }
        return FALSE;
      }
    );
    if (!$visible) {
      throw new ExpectationException(sprintf('Component "%s" is not visible.', $component_name), $this->getSession());
    }
  }

  /**
   * Wait for a component to be visible on the page.
   *
   * @param string $component_name
   *   Components.
   *
   * @When I wait for the :component_name component to not be visible
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the component is visible and/or clickable after 5 seconds.
   */
  public function iWaitForComponentNotVisible(string $component_name): void {
    $invisible = $this->getSession()->getPage()->waitFor(
      5,
      function () use ($component_name) {
        try {
          $components = $this->getComponents($component_name);
          foreach ($components as $component) {
            if ($component->isVisible()) {
              return FALSE;
            }
          }
        }
        catch (ElementNotFoundException $e) {
        }
        return TRUE;
      }
    );
    if (!$invisible) {
      throw new ExpectationException(sprintf('Component "%s" is not visible.', $component_name), $this->getSession());
    }
  }

  /**
   * Wait for a component element to be visible on the page.
   *
   * @param string $element_name
   *   Element name.
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Component name.
   * @param string $text
   *   Text to match on.
   *
   * @When I wait for the :element_name element of the :component component containing text :text to be visible
   * @When I wait for the :element_name element of the :component component to be visible
   *
   * @throws \Exception
   *   When the component is not visible and/or clickable after 30 seconds.
   */
  public function iWaitForComponentElement(string $element_name, array $component, string $text = ''): NodeElement {
    $element = $this->spin(\Closure::bind(function () use ($element_name, $component, $text) {
      try {
        $element_selector = $this->getComponentElementSelector($this->componentName, $element_name);
        $element = $this->getElementInFirstComponentContainingText($element_selector, $component, $text);
        if ($element instanceof NodeElement && $element->isVisible()) {
          return $element;
        }
      }
      catch (\Exception $e) {
      }
    }, $this), self::WAIT_FOR_COMPONENT_TIMEOUT);
    if (!$element) {
      throw new \Exception(sprintf('Component "%s" element "%s" is not visible on the page', $this->componentName, $element_name));
    }
    return $element;
  }

  /**
   * Click on a component.
   *
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Component.
   *
   * @Then I click the :component component
   */
  public function iClickTheComponent(array $component): void {
    current($component)->click();
  }

  /**
   * Click on a component element.
   *
   * @param string $element_name
   *   Element name.
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Component.
   * @param string $text
   *   Text to match on.
   *
   * @Then I click the :element_name element of the :component component
   * @Then I click the :element_name element of the :component component containing the text :text
   */
  public function iClickTheComponentElement(string $element_name, array $component, string $text = ''): void {
    $element = $this->iWaitForComponentElement($element_name, $component, $text);
    $element->click();
  }

  /**
   * Assert a component element has text.
   *
   * @param string $element_name
   *   Element name.
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Component.
   * @param string $text
   *   Text to match on.
   *
   * @Then the :element_name elements of the :component component contains text :text
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the component can't be found.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When an element doesn't contain the expected text.
   */
  public function assertTextOfElement(string $element_name, array $component, string $text = ''): void {
    $matching_components = $this->getComponentsContainingText($component, $text);
    if (empty($matching_components)) {
      throw new ElementNotFoundException($this->getSession(), 'Component ' . $this->componentName);
    }
    foreach ($matching_components as $match) {
      $element = $match->find('css', $this->getComponentElementSelector($this->componentName, $element_name));
      if ($element instanceof NodeElement && $element->getText() != $text) {
        throw new ExpectationException(sprintf('Element %s within component %s is not visible.', $element_name, $this->componentName), $this->getSession());
      }
    }
  }

  /**
   * Focus on a component.
   *
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Component.
   *
   * @Then I focus on the :component component
   */
  public function iFocusOnTheComponent(array $component): void {
    $this->elementContext->focusElement(current($component));
  }

  /**
   * Assert a component is in a certain state.
   *
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Element.
   * @param string $state
   *   Desired element state.
   *
   * @see Drupal\VahiBehatExtension\Enum\ElementState
   *
   * @Then the :component component should be :state
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the element isn't defined or visible on the page.
   */
  public function assertComponentState(array $component, string $state): void {
    switch ($state) {
      case ElementState::VISIBLE:
        foreach ($component as $comp) {
          if ($comp->isVisible()) {
            return;
          }
        }
        throw new ExpectationException(sprintf('Component "%s" is not visible', $this->componentName), $this->getSession());

      case ElementState::NOT_VISIBLE:
        foreach ($component as $instance) {
          if ($instance->isVisible()) {
            throw new ExpectationException(sprintf('Component "%s" is visible', $this->componentName), $this->getSession());
          }
        }
        break;

      case ElementState::FOCUSED:
        $is_component_focused = FALSE;
        foreach ($component as $match) {
          if ($this->elementContext->isElementFocused($match)) {
            $is_component_focused = TRUE;
            break;
          }
        }

        if (!$is_component_focused) {
          throw new ExpectationException(sprintf('Component %s is not focused.', $this->componentName), $this->getSession());
        }
        break;

      default:
        throw new ExpectationException(sprintf('Desired state "%s" was not matched.', $state), $this->getSession());
    }
  }

  /**
   * Assert a component is not in a certain state.
   *
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Element.
   * @param string $state
   *   Desired element state.
   * @param int $seconds
   *   Number of seconds to wait for. Default is 0.
   *
   * @see Drupal\VahiBehatExtension\Enum\ElementState
   *
   * @Then the :component component should not be :state
   * @Then the :component component should not be :state after :seconds seconds
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the element is in the state it shouldn't be in.
   */
  public function assertComponentNotState(array $component, string $state, int $seconds = 0): void {
    switch ($state) {
      case ElementState::VISIBLE:
        usleep(intval($seconds * 1000000));
        foreach ($component as $instance) {
          if ($instance->isVisible()) {
            throw new ExpectationException(sprintf('Component "%s" is visible', $this->componentName), $this->getSession());
          }
        }
        break;

      case ElementState::FOCUSED:
        $is_component_focused = FALSE;
        foreach ($component as $match) {
          if ($this->elementContext->isElementFocused($match)) {
            $is_component_focused = TRUE;
            break;
          }
        }

        if ($is_component_focused) {
          throw new ExpectationException(sprintf('Component %s is focused.', $this->componentName), $this->getSession());
        }
        break;

      default:
        throw new ExpectationException(sprintf('Desired state "%s" was not matched.', $state), $this->getSession());
    }
  }

  /**
   * Assert a component element is in a certain state.
   *
   * @param string $element_name
   *   Element name.
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Element.
   * @param string $state
   *   Desired element state.
   * @param string $text
   *   Text to match on.
   *
   * @see Drupal\VahiBehatExtension\Enum\ElementState
   *
   * @Then the :element_name element of the :component component containing text :text should be :state
   * @Then the :element_name element of the :component component should be :state
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the element isn't defined or visible on the page.
   */
  public function assertElementOfComponentState(string $element_name, array $component, string $state, string $text = ''): void {
    switch ($state) {
      case ElementState::VISIBLE:
        $matching_components = $this->getComponentsContainingText($component, $text);

        foreach ($matching_components as $match) {
          $element = $match->find('css', $this->getComponentElementSelector($this->componentName, $element_name));
          if ($element instanceof NodeElement && !$element->isVisible()) {
            throw new ExpectationException(sprintf('Element %s within component %s is not visible.', $element_name, $this->componentName), $this->getSession());
          }
        }
        break;

      case ElementState::NOT_VISIBLE:
        $matching_components = $this->getComponentsContainingText($component, $text);

        foreach ($matching_components as $match) {
          $element = $match->find('css', $this->getComponentElementSelector($this->componentName, $element_name));
          if ($element instanceof NodeElement && $element->isVisible()) {
            throw new ExpectationException(sprintf('Element %s within component %s is visible.', $element_name, $this->componentName), $this->getSession());
          }
        }
        break;

      case ElementState::FOCUSED:
        $matching_components = $this->getComponentsContainingText($component, $text);

        $is_element_focused = FALSE;
        foreach ($matching_components as $match) {
          $element = $match->find('css', $this->getComponentElementSelector($this->componentName, $element_name));
          if ($this->elementContext->isElementFocused($element)) {
            $is_element_focused = TRUE;
            break;
          }
        }

        if (!$is_element_focused) {
          throw new ExpectationException(sprintf('Element %s within component %s is not focused.', $element_name, $this->componentName), $this->getSession());
        }
        break;

      default:
        throw new ExpectationException(sprintf('Desired state "%s" was not matched.', $state), $this->getSession());
    }
  }

  /**
   * Assert a component element is not in a certain state.
   *
   * @param string $element_name
   *   Element name.
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Element.
   * @param string $state
   *   Desired element state.
   * @param string $text
   *   Text to match on.
   *
   * @see Drupal\VahiBehatExtension\Enum\ElementState
   *
   * @Then the :element_name element of the :component component containing text :text should not be :state
   * @Then the :element_name element of the :component component should not be :state
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the element isn't in the desired state.
   */
  public function assertElementOfComponentNotInState(string $element_name, array $component, string $state, string $text = ''): void {
    switch ($state) {
      case ElementState::VISIBLE:
        $matching_components = $this->getComponentsContainingText($component, $text);

        foreach ($matching_components as $match) {
          $element = $match->find('css', $this->getComponentElementSelector($this->componentName, $element_name));
          if ($element instanceof NodeElement && $element->isVisible()) {
            throw new ExpectationException(sprintf('Element %s within component %s is visible.', $element_name, $this->componentName), $this->getSession());
          }
        }
        break;

      case ElementState::FOCUSED:
        $matching_components = $this->getComponentsContainingText($component, $text);

        $is_element_focused = FALSE;
        foreach ($matching_components as $match) {
          $element = $match->find('css', $this->getComponentElementSelector($this->componentName, $element_name));
          if ($this->elementContext->isElementFocused($element)) {
            $is_element_focused = TRUE;
            break;
          }
        }

        if ($is_element_focused) {
          throw new ExpectationException(sprintf('Element %s within component %s is focused.', $element_name, $this->componentName), $this->getSession());
        }
        break;

      default:
        throw new ExpectationException(sprintf('Desired state "%s" was not matched.', $state), $this->getSession());
    }
  }

  /**
   * Checks that a number components with or containing text exists on the page.
   *
   * Example: Then I should see 5 "publication"
   * components with the text "sample".
   *
   * @param int $num
   *   Expected number of components.
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Component.
   * @param string $text
   *   Text to match on.
   * @param bool $match_type
   *   Match exact text or containing.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the correct number of components can't be found.
   *
   * @Then I should see :num :component component(s)
   * @Then I should see :num :component component(s) :match_type the text :text
   */
  public function assertNumComponentsContainingText(int $num, array $component, string $text = '', bool $match_type = FALSE): void {
    $matching_components = $this->getComponentsContainingText($component, $text, $match_type);

    if (count($matching_components) !== intval($num)) {
      throw new ExpectationException(sprintf('Expected %s %s components, but found %s.', $num, $this->componentName, count($matching_components)), $this->getSession());
    }
  }

  /**
   * Checks a components attribute value.
   *
   *  @Then I( should) see the :component_name component with the :attribute attribute set to :value in the :region( region)
   */
  public function assertRegionComponentAttribute(string $component_name, string $attribute, string $value, string $region): void {
    $component = $this->getComponentSelector($component_name);
    $this->markupContext->assertRegionElementAttribute($component, $attribute, $value, $region);
  }

  /**
   * Click a component with text on the page.
   *
   * Example: Then I click the "div" component with the text "sample".
   *
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Component.
   * @param string $text
   *   Text to match on.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the component can't be found.
   *
   * @Then I click the :component component with the text :text
   */
  public function clickcomponentWithText(array $component, string $text): void {
    $matching_components = $this->getComponentsContainingText($component, $text, TRUE);
    if (empty($matching_components)) {
      throw new ElementNotFoundException($this->getSession(), 'Component ' . $this->componentName);
    }
    current($matching_components)->click();
  }

  /**
   * Check whether a component contains text.
   *
   * @param string $text
   *   Text to match on.
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Component.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If component or text within it cannot be found.
   *
   * @Then I should see( the text) :text in the :component( component)
   */
  public function assertComponentText(string $text, array $component): void {
    $matching_components = $this->getComponentsContainingText($component, $text, FALSE);

    if (empty($matching_components)) {
      throw new ElementNotFoundException($this->getSession(), 'Component ' . $this->componentName);
    }
  }

  /**
   * Check whether a component contains an image with a tag.
   *
   * @param string $image_tag
   *   Image tag to match on.
   * @param \Behat\Mink\Element\NodeElement[] $component
   *   Component.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If component or tag within it cannot be found.
   *
   * @Then I should see an image with tag :image_tag in the :component( component)
   */
  public function assertComponentImageTag(string $image_tag, array $component): void {
    $this->imageContext->iShouldSeeImageWithAltTagOnPage($image_tag, current($component));
  }

  /**
   * Get components containing text.
   *
   * @param \Behat\Mink\Element\NodeElement[] $components
   *   Component name.
   * @param string $text
   *   Text to search for.
   * @param bool $exact_match
   *   If true, match the text exactly, else contains.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The components if found.
   */
  private function getComponentsContainingText(array $components, string $text, bool $exact_match = FALSE): array {
    if (empty($text)) {
      return $components;
    }
    $matching_components = array_filter($components, function (NodeElement $element) use ($text, $exact_match) {
      return $exact_match ? ($element->getText() === $text) : (strpos($element->getText(), $text) !== FALSE);
    });
    return $matching_components;
  }

  /**
   * Get element of the first component containing text.
   *
   * @param string $element_selector
   *   Element selector.
   * @param \Behat\Mink\Element\NodeElement[] $components
   *   Component name.
   * @param string $text
   *   Text to match on.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The component element if found,
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the component element isn't found on the page.
   */
  public function getElementInFirstComponentContainingText(string $element_selector, array $components, string $text): NodeElement {
    $component = current($this->getComponentsContainingText($components, $text));
    $element = $component->find('css', $element_selector);
    if (!$element) {
      throw new ElementNotFoundException($this->getSession(), 'Component ' . $this->componentName . ' element ' . $element_selector, 'css', $element_selector);
    }
    return $element;
  }

  /**
   * Get all components.
   *
   * @param string $component_name
   *   Component name.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   The component element if found,
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the component isn't defined or visible on the page.
   */
  private function getComponents(string $component_name): array {
    $selector = $this->getComponentSelector($component_name);
    $component = $this->getSession()->getPage()->findAll('css', $selector);
    return $component;
  }

  /**
   * Get the selector for a given component.
   *
   * @param string $component_name
   *   Component name.
   *
   * @return string
   *   Component selector.
   *
   * @throws \Exception
   *   When the component isn't defined.
   */
  private function getComponentSelector(string $component_name): string {
    $component = $this->componentMap[$component_name] ?? NULL;
    if (is_array($component)) {
      $component = $component['selector'];
    }
    if (!$component) {
      throw new \Exception(sprintf('Component "%s" is not defined.', $component_name));
    }

    return $component;
  }

  /**
   * Get the selector for a given component element.
   *
   * @param string $component_name
   *   Component name.
   * @param string $element_name
   *   Element name.
   *
   * @return string
   *   Element selector.
   *
   * @throws \Exception
   *   When the element isn't defined.
   */
  private function getComponentElementSelector(string $component_name, string $element_name): string {
    $selector = $this->componentMap[$component_name]['elements'][$element_name] ?? NULL;
    if (!$selector) {
      throw new \Exception(sprintf('Component "%s" element "%s" is not defined.', $component_name, $element_name));
    }

    return $selector;
  }

}
