<?php

namespace ForgeQC\SonarqubeApiClient;

use ForgeQC\SonarqubeApiClient\HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use \UnexpectedValueException;
use \Exception;

class SonarqubeInstance {

  protected $httpclient;
  protected $organization;

  private const SONARQUBE_MEASURES = 'measures';
  private const SONARQUBE_LOGIN = 'login';
  private const SONARQUBE_FORM_PARAMS = 'form_params';
  private const MEASURES_SECURITY_RATING_WORST = 'security_rating_worst';
  private const MEASURES_SECURITY_RATING_WORST_COUNT = 'security_rating_worst_count';
  private const MEASURES_RELIABILITY_RATING_WORST = 'reliability_rating_worst';
  private const MEASURES_RELIABILITY_RATING_WORST_COUNT = 'reliability_rating_worst_count';

  //Class constructor. Initializes object with httpclient to Sonarqube API and project key (existing or to be created)
  public function __construct($httpclient, $organization = null) {
      $this->httpclient = $httpclient;
      $this->organization = $organization;
  }

  //Retrieve all Sonarqube projects (projects visibility related to API token access rights)
  public function getProjects() {
    $projects = [];

    //Handle multiple projects pages
    $page = 0;
    do {
      $page++;
      $response = $this->httpclient->request('GET', 'components/search?qualifiers=TRK&p=' . $page);
      $projectsTemp = json_decode($response->getBody(), true);
      $projects = array_merge($projects, $projectsTemp['components']);

      $paging = $projectsTemp['paging'];
      $projectscount = $paging['total'];
      $pagesize = $paging['pageSize'];
      $pageindex = $paging['pageIndex'];
    } while ($projectscount > $pagesize * $pageindex);

    return $projects;
  }

  //Retrieve Sonarqube measures for a list of projects
  //projectKeys is limited to 100 values
  public function getMultipleProjectsMeasures($projectKeys, $metricKeys=null) {
    $sonarqubeMetrics = array();
    $measures = array();

    //Test $projectKeys parameter and split it in multiple pages if there are more than 100 project keys
    $projectKeys_array = explode(',', $projectKeys);
    if(count($projectKeys_array) > 100) {
      $projectKeysArrayPages = array_chunk($projectKeys_array, 100);
      $sonarqubeMetrics['measures'] = array();

      foreach($projectKeysArrayPages as $keysArray) {
        //Extract the project quality measures from sonarqube
        $metricsPage = $this->extractMultipleProjectsMeasures(implode(',', $keysArray), $metricKeys);
        foreach ($metricsPage['measures'] as $measure) {
          array_push($sonarqubeMetrics['measures'], $measure);
        }
      }
    }
    else {
      //Extract the project quality measures from sonarqube
      $sonarqubeMetrics = $this->extractMultipleProjectsMeasures($projectKeys, $metricKeys);
    }

    //Parse measures and inject in result array
    foreach ($sonarqubeMetrics['measures'] as $measure) {
      //Generic extraction of sonarqube metrics and value for injection in the result array
      $metric = $measure['metric'];
      $value = $measure['value'];
      $component = $measure['component'];

      $measures[$component][$metric] = $value;
    }
    return $measures;
  }

  //Aggregate Sonarqube measures for a list of projects
  //Implements https://docs.sonarqube.org/latest/user-guide/portfolios/ sonarqube aggregation logic
  //ProjectKeys is limited to 100 values ; Aggregation function restricted to specific metricKeys only
  public function aggregateMultipleProjectsMeasures($projectKeys) {
    $sonarqubeMetrics = array();

    //Test $projectKeys parameter
    $projectKeys_array = explode(',', $projectKeys);
    $projectCount = count($projectKeys_array);

    if($projectCount > 100) {
      $projectKeysArrayPages = array_chunk($projectKeys_array, 100);
      $sonarqubeMetrics['measures'] = array();

      foreach($projectKeysArrayPages as $keysArray) {
        //Extract the project quality measures from sonarqube
        $metricsPage = $this->extractMultipleProjectsMeasures(implode(',', $keysArray), 'alert_status,reliability_rating,sqale_rating,security_rating');
        foreach ($metricsPage['measures'] as $measure) {
          array_push($sonarqubeMetrics['measures'], $measure);
        }
      }
    }
    else {
      //Extract the project quality measures from sonarqube
      $sonarqubeMetrics = $this->extractMultipleProjectsMeasures($projectKeys, 'alert_status,reliability_rating,sqale_rating,security_rating');
    }

    //If measures are returned by sonarqube, parse project measures and inject in result array
    if (count($sonarqubeMetrics['measures']) > 0) {
      //Initialize variables
      $projects_failed_quality_gate = 0;
      $sonarqubeProjectsWithMeasures = [];
      $measures = []; //Measures array

      //Array containing worst rating value for security_rating and reliability_rating
      $measuresWorst[self::MEASURES_SECURITY_RATING_WORST] = 1;
      $measuresWorst[self::MEASURES_SECURITY_RATING_WORST_COUNT] = 0;
      $measuresWorst[self::MEASURES_RELIABILITY_RATING_WORST] = 1;
      $measuresWorst[self::MEASURES_RELIABILITY_RATING_WORST_COUNT] = 0;


      foreach ($sonarqubeMetrics['measures'] as $measure) {
        $component = $measure['component'];
        $metric = $measure['metric'];
        $value = $measure['value'];

        //Push project id in $sonarqubeProjectsWithMeasures array to count projects with measures
        $sonarqubeProjectsWithMeasures[$component] = 1;

        //Sum the measures per metric, except for alert_status
        if ($metric == 'alert_status') {
          if ($value == 'ERROR') {
            $projects_failed_quality_gate += 1;
          }
        }
        elseif (array_key_exists($metric, $measures)) {
          $measures[$metric] += intval($value);
        }
        else {
          $measures[$metric] = intval($value);
        }

        //Get worst security_rating and project count having this worst rate
        if ($metric == 'security_rating') {
          if(intval($value) > $measuresWorst[self::MEASURES_SECURITY_RATING_WORST]) {
            $measuresWorst[self::MEASURES_SECURITY_RATING_WORST] = intval($value);
            $measuresWorst[self::MEASURES_SECURITY_RATING_WORST_COUNT] = 1;
          }
          elseif(intval($value) == $measuresWorst[self::MEASURES_SECURITY_RATING_WORST]) {
            $measuresWorst[self::MEASURES_SECURITY_RATING_WORST_COUNT] += 1;
          }
        }

        //Get worst reliability_rating and project count having this worst rate
        if ($metric == 'reliability_rating') {
          if(intval($value) > $measuresWorst[self::MEASURES_RELIABILITY_RATING_WORST]) {
            $measuresWorst[self::MEASURES_RELIABILITY_RATING_WORST] = intval($value);
            $measuresWorst[self::MEASURES_RELIABILITY_RATING_WORST_COUNT] = 1;
          }
          elseif(intval($value) == $measuresWorst[self::MEASURES_RELIABILITY_RATING_WORST]) {
            $measuresWorst[self::MEASURES_RELIABILITY_RATING_WORST_COUNT] += 1;
          }
        }
      }

      //The number of projects with measures. can be different than the number of projects in the query list
      $sonarqubeProjectsWithMeasuresCount = count($sonarqubeProjectsWithMeasures);

      /*
      The Reliability, Security Vulnerabilities, Security Hotspots Review, and Maintainability ratings for a Portfolio
      are calculated as the average of the ratings for all projects included in the Portfolio.
      Averages ending with .5 are rounded up resulting in the "lower" of the two possible ratings, so an average of 2.5
      would be rounded up to 3 and result in a "C" rating).
      */
      foreach ($measures as $metric => $value) {
        $measures[$metric] = round($value / $sonarqubeProjectsWithMeasuresCount, 0, PHP_ROUND_HALF_UP);
      }

      //Insert the releasability_rating
      $measures['releasability_rating'] = $this->getReleasabilityRating($sonarqubeProjectsWithMeasuresCount, $projects_failed_quality_gate);

      //Insert the count of projects wfor which sonarqube measures have been requested
      $measures['projects_count_request'] = $projectCount;

      //Insert the count of projects with sonarqube measures in the result array
      $measures['projects_count_with_measures'] = $sonarqubeProjectsWithMeasuresCount;

      //Insert the count of projects having failed the quality gate in the result array
      $measures['projects_failed_quality_gate'] = $projects_failed_quality_gate;

      //return the result array with aggregated measures
      return array_merge($measures, $measuresWorst);
    }
    else {
      //Return an empty array if no measures have been returned by sonarqube.
      return [];
    }
  }

  //Create a Sonarqube group. Should return a JSON response with group details.
  public function createGroup($group) {
    $params['name'] = $group;
    if(isset($this->organization)) {
      $params['organization'] = $this->organization;
    }
    $response = $this->httpclient->request('POST', 'user_groups/create', [self::SONARQUBE_FORM_PARAMS => $params]);
    return json_decode($response->getBody(), true);
  }

  //Delete a Sonarqube group. No response is returned.
  public function deleteGroup($groupName) {
    $params['name'] = $groupName;
    if(isset($this->organization)) {
      $params['organization'] = $this->organization;
    }
    try {
      $this->httpclient->request('POST', 'user_groups/delete', [self::SONARQUBE_FORM_PARAMS => $params]);
      return true;
    } catch (ClientException $e) {
      return false;
    }
  }

  //Test if a sonarqube user exists. Boolean returned
  public function userExists($login) {
    $response = $this->httpclient->request('GET', 'users/search?q='.$login);
    $data = json_decode($response->getBody(), true);

    $exists = false;

    foreach ($data['users'] as $user) {
      if($user[self::SONARQUBE_LOGIN] == $login) {
        $exists = true;
        //No need to continue the foreach loop when user has been found
        break;
      }
    }
    return $exists;
  }

  //Create a Sonarqube user. Should return a JSON response with user details.
  public function createUser($login, $name, $email) {
    $params[self::SONARQUBE_LOGIN] = $login;
    $params['name'] = $name;
    $params['email'] = $email;
    $params['local'] = 'false'; //External user created without password. Local login is denied.

    $response = $this->httpclient->request('POST', 'users/create', [self::SONARQUBE_FORM_PARAMS => $params]);
    return json_decode($response->getBody(), true);
  }

  //Update Sonarqube user properties. Should return a JSON response with user details.
  public function updateUser($login, $name, $email) {
    $params[self::SONARQUBE_LOGIN] = $login;
    $params['name'] = $name;
    $params['email'] = $email;

    $response = $this->httpclient->request('POST', 'users/update', [self::SONARQUBE_FORM_PARAMS => $params]);
    return json_decode($response->getBody(), true);
  }

  //Deactivate a Sonarqube user. No response is returned.
  public function deactivateUser($login) {
    $params[self::SONARQUBE_LOGIN] = $login;

    $response = $this->httpclient->request('POST', 'users/deactivate', [self::SONARQUBE_FORM_PARAMS => $params]);
    $data = json_decode($response->getBody(), true);

    //Boolean returned by sonarqube API
    return $data['user']['active'];

  }

  /*The Releasability rating is the ratio of projects in the Portfolio that have a Passed Quality Gate:
  A: > 80%, B: > 60%, C: > 40%, D: > 20%, E: <= 20% */
  protected function getReleasabilityRating($sonarqubeProjectsWithMeasuresCount, $projects_failed_quality_gate) {
    $releasability_rating = ($sonarqubeProjectsWithMeasuresCount - $projects_failed_quality_gate) / $sonarqubeProjectsWithMeasuresCount;
    if ($releasability_rating > 0.8) {
      $rating = 1;
    } elseif ($releasability_rating > 0.6) {
      $rating = 2;
    } elseif ($releasability_rating > 0.4) {
      $rating = 3;
    } elseif ($releasability_rating > 0.2) {
      $rating = 4;
    } else {
      $rating = 5;
    }
    return $rating;
  }

  protected function extractMultipleProjectsMeasures($projectKeys, $metricKeys=null) {
    if(isset($metricKeys)) {
      $response = $this->httpclient->request('GET', 'measures/search?metricKeys='.$metricKeys.'&projectKeys='. $projectKeys);
    }
    else {
      $response = $this->httpclient->request('GET', 'measures/search?metricKeys=alert_status,bugs,reliability_rating,vulnerabilities,security_rating,code_smells,sqale_rating,duplicated_lines_density,coverage,ncloc,ncloc_language_distribution,reliability_remediation_effort,security_remediation_effort&projectKeys='. $projectKeys);
    }
    return json_decode($response->getBody(), true);
  }

}

?>
