<?php

namespace ForgeQC\SonarqubeApiClient;

use ForgeQC\SonarqubeApiClient\HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use \UnexpectedValueException;

class SonarqubeProject {

  protected $httpclient;
  protected $organization;
  protected $key;
  protected $name;
  protected $visibility;
  protected $owner;

  private const COMPONENT = 'component';

  //Class constructor. Initializes object with httpclient to Sonarqube API and project key (existing or to be created)
  public function __construct($httpclient, $projectKey, $organization = null) {
      $this->httpclient = $httpclient;
      $this->key = $projectKey;
      $this->organization = $organization;
  }

  //Test if sonarqube project exists
  public function exists() {
    try {
      //If data is returned, then project exists
      $response = $this->httpclient->request('GET', 'components/show?component='. $this->key);
      $data = json_decode($response->getBody(), true);

      //the expression will evaluate as true if both values are equal
      return $data[self::COMPONENT]['key'] == $this->key;
    } catch (BadResponseException $e) {
      //Else sonarqube returns a 404 error code
      $errorcode = json_decode($e->getResponse()->getStatusCode(), true);
      if ($errorcode == 404) {
        return false;
      } else {
        throw new RequestException(\GuzzleHttp\Psr7\str($e->getResponse()), $e->getRequest());
      }
    }
  }

  //Create a new sonarqube project
  //$organization parameter is optional as it is only required for sonarcloud.io
  public function create($projectName, $visibility, $organization = null) {
    $params['name'] = $projectName;
    $params['project'] = $this->key;
    $params['visibility'] = $visibility;
    if(isset($organization)) {
      $params['organization'] = $organization;
    }
    $response = $this->httpclient->request('POST', 'projects/create', ['form_params' => $params]);
    return json_decode($response->getBody(), true);
  }

  //Get sonarqube project metadata
  public function getProperties() {
    $response = $this->httpclient->request('GET', 'components/show?component='. $this->key);
    $sonarqubeComponentProperties = json_decode($response->getBody(), true);
    return $sonarqubeComponentProperties[self::COMPONENT];
  }

  //Retrieve Sonarqube project measures
  public function getMeasures() {
    $projects_measures = array();

    //Extract the project quality measures from sonarqube
    $response = $this->httpclient->request('GET', 'measures/component?metricKeys=coverage,sqale_index,sqale_rating,bugs,reliability_remediation_effort,security_rating,vulnerabilities,security_remediation_effort&component='. $this->key);
    $sonarqubeProjectsMetrics = json_decode($response->getBody(), true);

    //Parse measures and inject in result array
    foreach ($sonarqubeProjectsMetrics[self::COMPONENT]['measures'] as $measure) {
      //Generic extraction of sonarqube metrics and value for injection in the result array
      $metric = $measure['metric'];
      $value = $measure['value'];
      $projects_measures[$metric] = $value;
    }
    return $projects_measures;
  }

  //Retrieve Sonarqube project measures
  public function getMeasuresHistory($date) {
    //Check if date is set and valid or return HTTP/400 Bad Request error
    if(preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$date)) {
      $projects_measures = array();

      //Extract the project quality measures from sonarqube
      $response = $this->httpclient->request('GET', 'measures/search_history?metrics=coverage,sqale_index,sqale_rating,bugs,reliability_remediation_effort,security_rating,vulnerabilities,security_remediation_effort&component='. $this->key.'&from='.$date);
      $sonarqubeProjectsMetrics = json_decode($response->getBody(), true);

      //Parse measures and inject in result array
      foreach ($sonarqubeProjectsMetrics['measures'] as $measure) {
        //Generic extraction of sonarqube metrics and value for injection in the result array
        $metric = $measure['metric'];
        $valuetable = $measure['history'];
        $projects_measures[$metric] = $valuetable;
      }
      return $projects_measures;
    }
    else {
      throw new UnexpectedValueException('The \'date\' parameter is missing or is not a valid YYYY-MM-DD date format.');
    }
  }

  //Grant project permissions to a Group
  public function addGroupPermission($groupName, $permission) {
    //Permissions parameter validation
    $permissionValues = array('admin', 'codeviewer', 'issueadmin', 'securityhotspotadmin', 'scan', 'user');
    if(!$permissionValues || !in_array($permission, $permissionValues, true)) {
        throw new UnexpectedValueException("The 'permission' parameter is missing or is equal to 'admin', 'codeviewer', 'issueadmin', 'securityhotspotadmin', 'scan' or 'user'.");
    }

    $params['groupName'] = $groupName;
    $params['projectKey'] = $this->key;
    $params['permission'] = $permission;
    if(isset($this->organization)) {
      $params['organization'] = $this->organization;
    }

    $this->httpclient->request('POST', 'permissions/add_group', ['form_params' => $params]);
    return true;
  }

  //Remove project permissions to a Group
  public function removeGroupPermission($groupName, $permission) {
    //Permissions parameter validation
    $permissionValues = array('admin', 'codeviewer', 'issueadmin', 'securityhotspotadmin', 'scan', 'user');
    if(!$permissionValues || !in_array($permission, $permissionValues, true)) {
        throw new UnexpectedValueException("The 'permission' parameter is missing or is equal to 'admin', 'codeviewer', 'issueadmin', 'securityhotspotadmin', 'scan' or 'user'.");
    }

    $params['groupName'] = $groupName;
    $params['projectKey'] = $this->key;
    $params['permission'] = $permission;
    if(isset($this->organization)) {
      $params['organization'] = $this->organization;
    }

    $this->httpclient->request('POST', 'permissions/remove_group', ['form_params' => $params]);
    return true;
  }

}

?>
