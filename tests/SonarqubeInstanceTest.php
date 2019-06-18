<?php

namespace Tests\ForgeQC\SonarqubeApiClient;

use PHPUnit\Framework\TestCase;
use ForgeQC\SonarqubeApiClient\HttpClient;
use ForgeQC\SonarqubeApiClient\SonarqubeInstance;
use Dotenv\Dotenv;

//Use Dotenv to retrieve secret variables from .env files
//Required to use travis-ci secret variables
$dotenv = Dotenv::create(dirname(__DIR__, 1));
$dotenv->load();
$sonar_api_key = getenv('SONAR_PHPUNIT_TOKEN');

class SonarqubeInstanceTest extends TestCase
{
  //Test getProjects() function on sonarqube online instance
  public function testGetProjects()
  {
    global $sonar_api_key;

    //Connect to sonarqube API
    $api = new HttpClient('https://sonarcloud.io/api/');
    $instance = new SonarqubeInstance($api, $sonar_api_key);

    $projects = $instance->getProjects();

    //test if paging if correctly handled (result count > 100 when testingith sonarcloud.io)
    $this->assertGreaterThan(100,count($projects));

    //test if at least a project is retuned with correct property keys
    $this->assertArrayHasKey('key', $projects[0]);
    $this->assertArrayHasKey('name', $projects[0]);
  }

  //Test createGroup() function
  public function testCreateDeleteGroup()
  {
    global $sonar_api_key;

    //Connect to sonarqube API
    $api = new HttpClient('https://sonarcloud.io/api/', $sonar_api_key);
    $instance = new SonarqubeInstance($api, 'testapi');

    $group = $instance->createGroup('TestGroup');
    $this->assertSame('TestGroup', $group['group']['name']);

    $result = $instance->deleteGroup('TestGroup');
    $this->assertNull($result);

  }

}
