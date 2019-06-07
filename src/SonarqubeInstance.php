<?php

namespace Forge\SonarqubeApiClient;

use Forge\SonarqubeApiClient\HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use Exception;

class SonarqubeInstance {

  protected $httpclient;

  //Class constructor. Initializes object with httpclient to Sonarqube API and project key (existing or to be created)
  public function __construct($httpclient) {
      $this->httpclient = $httpclient;
  }

  //Retrieve all Sonarqube projects (projects visibility related to API token access rights)
  public function getProjects() {
    $projects = [];

    //Handle multiple projects pages
    $response = $this->httpclient->request('GET', 'components/search?qualifiers=TRK');
    $projectsTemp = json_decode($response->getBody(), true);
    $projects = $projects + $projectsTemp['components'];

    return $projects;

  }

}

?>
