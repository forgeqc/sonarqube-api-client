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
    $measures = array();

    //Test $projectKeys parameter
    $projectKeys_array = explode(',', $projectKeys);
    if(count($projectKeys_array) > 100) {
      throw new UnexpectedValueException('The \'projectKeys\' list is limited to 100 project');
    }

    //Extract the project quality measures from sonarqube
    if(isset($metricKeys)) {
      $response = $this->httpclient->request('GET', 'measures/search?metricKeys='.$metricKeys.'&projectKeys='. $projectKeys);
    }
    else {
      $response = $this->httpclient->request('GET', 'measures/search?metricKeys=alert_status,bugs,reliability_rating,vulnerabilities,security_rating,code_smells,sqale_rating,duplicated_lines_density,coverage,ncloc,ncloc_language_distribution,reliability_remediation_effort,security_remediation_effort&projectKeys='. $projectKeys);
    }
    $sonarqubeMetrics = json_decode($response->getBody(), true);

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
    //Test $projectKeys parameter
    $projectKeys_array = explode(',', $projectKeys);
    $projectCount = count($projectKeys_array);
    if($projectCount > 100) {
      throw new UnexpectedValueException('The \'projectKeys\' list is limited to 100 project');
    }

    //Extract the project quality measures from sonarqube
    $response = $this->httpclient->request('GET', 'measures/search?metricKeys=alert_status,reliability_rating,sqale_rating,security_rating&projectKeys='. $projectKeys);
    $sonarqubeMetrics = json_decode($response->getBody(), true);

    //Parse measures and inject in result array
    $measures = array();
    $projects_failed_quality_gate = 0;
    //$measures['reliability_rating'] = 1;
    foreach ($sonarqubeMetrics['measures'] as $measure) {
      $metric = $measure['metric'];
      $value = $measure['value'];
      switch($metric) {
        case 'alert_status':
          if ($measure['value'] == 'ERROR') {
            $projects_failed_quality_gate += 1;
          }
          break;
        default:
          if(array_key_exists($metric, $measures)) {
            $measures[$metric] += intval($value);
          }
          else {
            $measures[$metric] = intval($value);
          }
      }
    }

    /*
    The Reliability, Security Vulnerabilities, Security Hotspots Review, and Maintainability ratings for a Portfolio
    are calculated as the average of the ratings for all projects included in the Portfolio.
    SonarQube converts each project's letter rating to a number (see conversion table below),
    calculates an average number for the projects in the portfolio, and converts that average to a letter rating.
    Averages ending with .5 are rounded up resulting in the "lower" of the two possible ratings, so an average of 2.5
    would be rounded up to 3 and result in a "C" rating).
    */
    foreach ($measures as $metric => $ $value) {
      $measures[$metric] = round($value / $projectCount, 0, PHP_ROUND_HALF_UP);
    }

    /*The Releasability rating is the ratio of projects in the Portfolio that have a Passed Quality Gate:
    A: > 80%, B: > 60%, C: > 40%, D: > 20%, E: <= 20% */
    $releasability_rating = ($projectCount - $projects_failed_quality_gate) / $projectCount;
    if ($releasability_rating > 0.8) {
      $measures['releasability_rating'] = 1;
    } elseif ($releasability_rating > 0.6) {
      $measures['releasability_rating'] = 2;
    } elseif ($releasability_rating > 0.4) {
      $measures['releasability_rating'] = 3;
    } elseif ($releasability_rating > 0.2) {
      $measures['releasability_rating'] = 4;
    } else {
      $measures['releasability_rating'] = 5;
    }

    //Return count of projects having failed the quality gate
    $measures['projects_failed_quality_gate'] = $projects_failed_quality_gate;

    return $measures;
  }

  //Create a Sonarqube group. Should return a JSON response with group details.
  public function createGroup($group) {
    $params['name'] = $group;
    if(isset($this->organization)) {
      $params['organization'] = $this->organization;
    }
    $response = $this->httpclient->request('POST', 'user_groups/create', ['form_params' => $params]);
    return json_decode($response->getBody(), true);
  }

  //Delete a Sonarqube group. No response is returned.
  public function deleteGroup($groupName) {
    $params['name'] = $groupName;
    if(isset($this->organization)) {
      $params['organization'] = $this->organization;
    }
    try {
      $this->httpclient->request('POST', 'user_groups/delete', ['form_params' => $params]);
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

    foreach ($data['users'] as $key => $user) {
      if($user['login'] == $login) {
        $exists = true;
        //No need to continue the foreach loop when user has been found
        break;
      }
    }
    return $exists;
  }

  //Create a Sonarqube user. Should return a JSON response with user details.
  public function createUser($login, $name, $email) {
    $params['login'] = $login;
    $params['name'] = $name;
    $params['email'] = $email;
    $params['local'] = 'false'; //External user created without password. Local login is denied.

    $response = $this->httpclient->request('POST', 'users/create', ['form_params' => $params]);
    return json_decode($response->getBody(), true);
  }

  //Update Sonarqube user properties. Should return a JSON response with user details.
  public function updateUser($login, $name, $email) {
    $params['login'] = $login;
    $params['name'] = $name;
    $params['email'] = $email;

    $response = $this->httpclient->request('POST', 'users/update', ['form_params' => $params]);
    return json_decode($response->getBody(), true);
  }

  //Deactivate a Sonarqube user. No response is returned.
  public function deactivateUser($login) {
    $params['login'] = $login;

    $response = $this->httpclient->request('POST', 'users/deactivate', ['form_params' => $params]);
    $data = json_decode($response->getBody(), true);

    //Boolean returned by sonarqube API
    return $data['user']['active'];

  }


}

?>
