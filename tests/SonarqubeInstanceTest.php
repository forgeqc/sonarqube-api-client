<?php

namespace Tests\Forge\SonarqubeApiClient;

use PHPUnit\Framework\TestCase;
use Forge\SonarqubeApiClient\HttpClient;
use Forge\SonarqubeApiClient\SonarqubeInstance;

class SonarqubeInstanceTest extends TestCase
{
  //Test getProjects() function on sonarqube online instance
  public function testGetProjects()
  {
    //Connect to sonarqube API
    $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/');
    $instance = new SonarqubeInstance($api);

    $projects = $instance->getProjects();

    //test if at least a project is retuned with correct property keys
    $this->assertArrayHasKey('key', $projects[0]);
    $this->assertArrayHasKey('name', $projects[0]);
  }
}
