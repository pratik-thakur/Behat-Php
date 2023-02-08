<?php

require 'vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;

$procs = array();
// $myfiles = array("config/single.conf.yml", "config/test.conf.yml", "config/test.conf2.yml", "config/test.conf3.yml", "config/test.conf4.yml");
$mydir = '/Users/pratik/Documents/Bstack/auto/behat-browserstack/config';
 
$myfiles = array_slice(scandir($mydir),2); 

// print_r($myfiles);
foreach ($myfiles as $key => $value) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
      // Windows
      $cmd = "set TASK_ID=$key& \"./bin/behat\" --config=config/$value \n";
    } else {
      // Linux or  Mac
      $cmd = "TASK_ID=$key ./bin/behat --config=config/$value \n";
    }
    print_r($cmd);

    $procs[$key] = popen($cmd, "r");
}

foreach ($procs as $key => $value) {
    while (!feof($value)) {
        print fgets($value, 4096);
    }
    pclose($value);
}

?>
