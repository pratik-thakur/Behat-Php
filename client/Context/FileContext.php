<?php

declare(strict_types = 1);

namespace Drupal\VahiBehatExtension\Context;

/**
 * @file
 * Feature context Behat testing using files.
 */

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Gherkin\Node\PyStringNode;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Client;

/**
 * Defines application features from the specific context.
 */
class FileContext extends RawDrupalContext {
  /**
   * The current working directory.
   *
   * @var string
   */
  private $workingDir;

  /**
   * Constructor.
   *
   * Save class params, if any.
   */
  public function __construct() {
    $this->workingDir = '../behat-tests/files/temp';
  }

  /**
   * Cleans test folders in the temporary directory.
   *
   * @BeforeScenario
   * @AfterScenario
   */
  public function cleanTestFolders(): void {
    if (is_dir($this->workingDir)) {
      $this->clearDirectory($this->workingDir);
    }
  }

  /**
   * Creates a file with specified name and dummy content.
   *
   * @param string $filename
   *   Name of the file (relative path)
   *
   * @Given /^(?:there is )?a file named "([^"]*)"$/
   */
  public function aFileNamed(string $filename): void {
    $content = strtr((string) "Sample Content", ["'''" => '"""']);
    $this->createFile($this->workingDir . '/' . $filename, $content);
  }

  /**
   * Creates a file with specified name and context in current workdir.
   *
   * @param string $filename
   *   Name of the file (relative path)
   * @param \Behat\Gherkin\Node\PyStringNode $content
   *   PyString string instance.
   *
   * @Given /^(?:there is )?a file named "([^"]*)" with:$/
   */
  public function aFileNamedWithContent(string $filename, PyStringNode $content): void {
    $content = strtr((string) $content, ["'''" => '"""']);
    $this->createFile($this->workingDir . '/' . $filename, $content);
  }

  /**
   * Create a file with content.
   */
  private function createFile(string $filename, string $content): void {
    $path = dirname($filename);
    $this->createDirectory($path);

    file_put_contents($filename, $content);
  }

  /**
   * Create a directory.
   */
  private function createDirectory(string $path): void {
    if (!is_dir($path)) {
      mkdir($path, 0777, TRUE);
    }
  }

  /**
   * Empty a directory.
   */
  private function clearDirectory(string $path): void {
    $files = scandir($path);
    array_shift($files);
    array_shift($files);

    foreach ($files as $file) {
      $file = $path . DIRECTORY_SEPARATOR . $file;
      if (is_dir($file)) {
        $this->clearDirectory($file);
      }
      else {
        unlink($file);
      }
    }

    rmdir($path);
  }

  /**
   * Download a url and verify the contents.
   *
   * @throws \Exception
   *   When the file contents don't match.
   *
   * @Then the URL :url contains the following:
   */
  public function assertUrlContent(string $url, PyStringNode $content): void {
    $cookies = $this->getSession()->getDriver()->getWebDriverSession()->getCookie()[0];
    $cookie = new SetCookie();
    $cookie->setName($cookies['name']);
    $cookie->setValue($cookies['value']);
    $cookie->setDomain($cookies['domain']);

    $jar = new CookieJar();
    $jar->setCookie($cookie);

    $client = new Client();
    $res = $client->request('GET', $url, [
      'cookies' => $jar,
      'verify' => FALSE,
    ]);

    $expected_string = preg_replace('/\r/', '', (string) $content);
    $actual_string = $res->getBody()->getContents();

    if ($expected_string !== $actual_string) {
      print_r('Expected String: ' . PHP_EOL . $expected_string . PHP_EOL);
      print_r('Actual String: ' . PHP_EOL . $actual_string);
      throw new \Exception('Contents of download do not match');
    }
  }

}
