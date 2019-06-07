<?php

namespace Forge\SonarqubeApiClient;

use Forge\SonarqubeApiClient\HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use Exception;

class SonarqubeProject {

  protected $httpclient;
  protected $key;
  protected $name;
  protected $visibility;
  protected $owner;

  //Class constructor. Initializes object with httpclient to Sonarqube API and project key (existing or to be created)
  public function __construct($httpclient, $projectKey) {
      $this->httpclient = $httpclient;
      $this->key = $projectKey;
  }

  //Test if sonarqube project exists
  public function exists() {
    try {
      //If data is returned, then project exists
      $response = $this->httpclient->request('GET', 'components/show?component='. $this->key);
      return true;
    } catch (BadResponseException $e) {
      //Else sonarqube returns a 404 error code
      $errorcode = json_decode($e->getResponse()->getStatusCode(), true);
      $errormsg = json_decode($e->getResponse()->getBody()->getContents(), true);
      if ($errorcode == 404) {
        return false;
      } else {;
        throw new Exception(\GuzzleHttp\Psr7\str($e->getResponse()));
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
    return $sonarqubeComponentProperties['component'];
  }

  //Retrieve Sonarqube project measures
  public function getMeasures() {
    $projects_measures = array();
    $projects_measures['sonarqube_key'] = $this->key;

    //Extract the project quality measures from sonarqube
    $response = $this->httpclient->request('GET', 'measures/component?metricKeys=coverage,sqale_index,sqale_rating,bugs,reliability_remediation_effort,security_rating,vulnerabilities,security_remediation_effort&component='. $this->key);
    $sonarqubeProjectsMetrics = json_decode($response->getBody(), true);

    //Parse measures and inject in result array
    foreach ($sonarqubeProjectsMetrics['component']['measures'] as $measure) {
      //Generic extraction of sonarqube metrics and value for injection in the result array
      $metric = $measure['metric'];
      $value = $measure['value'];
      $projects_measures[$metric] = $value;
    }
    return $projects_measures;
  }

}

?>
