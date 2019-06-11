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
    $page = 0;
    do {
      $page++;
      $response = $this->httpclient->request('GET', 'components/search?qualifiers=TRK&p=' . $page);
      $projectsTemp = json_decode($response->getBody(), true);
      $projects = array_merge($projects, $projectsTemp['components']);

      $projectscount = $projectsTemp['paging']['total'];
      $pagesize = $projectsTemp['paging']['pageSize'];
      $pageindex = $projectsTemp['paging']['pageIndex'];
    } while ($projectscount > $pagesize * $pageindex);

    return $projects;

  }

}

?>
