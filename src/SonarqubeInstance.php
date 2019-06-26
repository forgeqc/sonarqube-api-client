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
