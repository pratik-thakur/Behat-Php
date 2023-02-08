<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Mink\Exception\ExpectationException;
use Behat\Behat\Hook\Scope\AfterScenarioScope;

/**
 * Defines common VAHI Portal Behat Accessibility vocabs.
 */
class AccessibilityContext extends RawDrupalContext {

  /**
   * Warn instead of fail.
   *
   * Determines whether accessibility violations should be reported
   * as warnings or failures.
   *
   * @var bool
   */
  private $warnInsteadOfFail = FALSE;

  /**
   * Constructor.
   *
   * @param bool $warn_instead_of_fail
   *   Flag to turn off errors when running accessibility tests.
   */
  public function __construct(bool $warn_instead_of_fail = FALSE) {
    $this->warnInsteadOfFail = $warn_instead_of_fail;
  }

  /**
   * Run accessibility check after each scenario.
   *
   * Execute an accessibility check after each test to increase
   * coverage.
   *
   * @AfterScenario
   */
  public function postScenarioCheck(AfterScenarioScope $scope): void {
    // Bypass check if scenario has @disable-check--accessibility tag.
    if (in_array('disable-check--accessibility', $scope->getScenario()->getTags())) {
      return;
    }

    $this->runAxeValidation($scope);
  }

  /**
   * Run the axe-core accessibility tests.
   *
   * There are standard tags to ensure WCAG 2.1 A, WCAG 2.1 AA,
   * and Section 508 compliance.
   * It is also possible to specify any desired optional tags.
   *
   * The list of available tags can be found at
   * https://github.com/dequelabs/axe-core/blob/v3.5.5/doc/rule-descriptions.md.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When there are accesibility violations.
   */
  public function runAxeValidation(AfterScenarioScope $scope): void {
    if ($this->getSession()->evaluateScript('typeof axe !== \'undefined\'')) {
      $runOptions = $this->axeConfig();
      $result = $this->getSession()->evaluateScript('await axe.run(' . $runOptions . ')');

      // @todo Re-enable this once definite failures are addressed.
      // print_r("Accessibility requiring manual review found:\n" .
      // $this->formatResults($result['incomplete'])); .
      if (!count($result['violations'])) {
        return;
      }

      if ($this->warnInsteadOfFail) {
        $output = sprintf(
          "%s - %s:\n%s--------------\n",
          $scope->getFeature()->getTitle(),
          $scope->getScenario()->getTitle(),
          $this->formatResults($result['violations'])
        );

        /* Re-enable when there are less accessibility issues.
         * print_r("Accessibility violations found:\n"
         *  . $this->formatResults($result['violations'])
         * );
         */

        file_put_contents('../behat-tests/build/accessibility-violations.txt', $output, FILE_APPEND);
      }
      else {
        throw new ExpectationException("Accessibility violations found:\n" . $this->formatResults($result['violations']), $this->getSession());
      }
    }
  }

  /**
   * Get the configuration to use with Axe.
   *
   * Https://github.com/dequelabs/axe-core/blob/develop/doc/rule-descriptions.md
   * for details of the rules.
   *
   * @return string
   *   The JSON-encoded configuration.
   */
  protected function axeConfig(): string {
    $standardtags = [
      // Meet WCAG 2.0 A requirements.
      'wcag2a',

      // Meet WCAG 2.0 AA requirements.
      'wcag2aa',
    ];

    return json_encode([
      'runOnly' => [
        'type' => 'tag',
        'values' => $standardtags,
      ],
    ]);
  }

  /**
   * Format results from axe into readable format.
   *
   * @param mixed[]|null $results
   *   The results output from axe.
   *
   * @return string
   *   Readable results
   */
  protected function formatResults(array $results): string {
    $output = "";
    foreach ($results as $result) {
      $nodedata = '';
      foreach ($result['nodes'] as $node) {
        $reviewchecks = [];
        foreach (array_merge($node['any'], $node['all'], $node['none']) as $check) {
          $reviewchecks[$check['id']] = $check['message'];
        }

        $nodedata .= sprintf(
          "    - %s:\n      %s\n\n",
          implode(', ', $reviewchecks),
          implode("\n      ", $node['target'])
        );
      }

      $output .= sprintf(
        "  %.03d violations of '%s' (severity: %s)\n%s\n",
        count($result['nodes']),
        $result['description'],
        $result['impact'],
        $nodedata
      );
    }

    return $output;
  }

}
