<?php

declare(strict_types=1);
/*
 * By adding type hints and enabling strict type checking, code can become
 * easier to read, self-documenting and reduce the number of potential bugs.
 * By default, type declarations are non-strict, which means they will attempt
 * to change the original type to match the type specified by the
 * type-declaration.
 *
 * In other words, if you pass a string to a function requiring a float,
 * it will attempt to convert the string value to a float.
 *
 * To enable strict mode, a single declare directive must be placed at the top
 * of the file.
 * This means that the strictness of typing is configured on a per-file basis.
 * This directive not only affects the type declarations of parameters, but also
 * a function's return type.
 *
 * For more info review the Concept on strict type checking in the PHP track
 * <link>.
 *
 * To disable strict typing, comment out the directive below.
 */


/**
 *
 */
class Tournament {
  private $matches;
  private $teamsAndResults;

  /**
   *
   */
  public function __construct() {
    $this->matches = [];
    $this->teamsAndResults = [];
  }

  /**
   *
   */
  public function tally($input) {
    $this->convertInputToArrayOfMatches($input);

    $this->processMatches();
    $this->calculatePoints();

    return $this->generateResultTable();

  }

  /**
   *
   */
  private function convertInputToArrayOfMatches($input) {
    $matches = [];
    if ($input) {
      $matchesStringArray = explode("\n", $input);
      foreach ($matchesStringArray as $matchString) {
        $matches[] = explode(";", $matchString);
      }
    }

    $this->matches = $matches;

  }

  /**
   *
   */
  private function processMatches() {

    foreach ($this->matches as $match) {
      $i = 0;
      foreach ($match as $teamOrResult) {

        // Team name.
        if ($i != 2) {
          if (!isset($this->teamsAndResults[$teamOrResult])) {
            $this->teamsAndResults[$teamOrResult] = ['NAME' => $teamOrResult, 'MP' => 0, 'W' => 0, 'D' => 0, 'L' => 0, 'P' => 0];
          }
          $this->teamsAndResults[$teamOrResult]['MP'] = $this->teamsAndResults[$teamOrResult]['MP'] + 1;
        }
        else {
          // Team result.
          if ($teamOrResult == 'win') {
            $this->teamsAndResults[$match[0]]['W'] = $this->teamsAndResults[$match[0]]['W'] + 1;
            $this->teamsAndResults[$match[1]]['L'] = $this->teamsAndResults[$match[1]]['L'] + 1;
          }
          elseif ($teamOrResult == 'loss') {
            $this->teamsAndResults[$match[1]]['W'] = $this->teamsAndResults[$match[1]]['W'] + 1;
            $this->teamsAndResults[$match[0]]['L'] = $this->teamsAndResults[$match[0]]['L'] + 1;
          }
          elseif ($teamOrResult == 'draw') {
            $this->teamsAndResults[$match[1]]['D'] = $this->teamsAndResults[$match[1]]['D'] + 1;
            $this->teamsAndResults[$match[0]]['D'] = $this->teamsAndResults[$match[0]]['D'] + 1;
          }
        }

        $i++;
      }

    }
  }

  /**
   *
   */
  private function calculatePoints() {
    foreach ($this->teamsAndResults as $teamName => $results) {

      foreach ($results as $result => $resultCount) {
        switch ($result) {
          case 'W':
            $this->teamsAndResults[$teamName]['P'] = $this->teamsAndResults[$teamName]['P'] + (3 * $resultCount);
            break;

          case 'D':
            $this->teamsAndResults[$teamName]['P'] = $this->teamsAndResults[$teamName]['P'] + (1 * $resultCount);
            break;

        }
      }
    }
  }

  /**
   *
   */
  private function generateResultTable() {
    $header = "Team                           | MP |  W |  D |  L |  P";
    if (!$this->teamsAndResults) {
      return $header;
    }
    else {
      usort($this->teamsAndResults, function ($a, $b) {
        if ($a['P'] < $b['P']) {
            return 1;
        }
        elseif ($a['P'] > $b['P']) {
            return -1;
        }
        elseif ($a['P'] == $b['P']) {
          if ($a['NAME'] < $b['NAME']) {
            return -1;
          }
          else {
            return 1;
          }
        }
        else {
            return 0;
        }
      });
      foreach ($this->teamsAndResults as $results) {
        $teamName = $results['NAME'];
        unset($results['NAME']);
        $output[] = str_pad($teamName, 31) . "|  " . implode(" |  ", $results);
      }
      return $header . "\n" . implode("\n", $output);
    }
  }

}
