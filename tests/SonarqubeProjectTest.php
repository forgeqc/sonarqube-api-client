<?php

namespace Tests\Forge\SonarqubeApiClient;

use PHPUnit\Framework\TestCase;
use Forge\SonarqubeApiClient\HttpClient;
use Forge\SonarqubeApiClient\SonarqubeProject;

class SonarqubeProjectTest extends TestCase
{
  //Test exists() function on sonarqube sample project
  public function testSonarqubeProjectExists()
  {
      //Define sonarcloud.io project key
      $projectKey = 'org.sonarsource.scanner.cli:sonar-scanner-cli';

      //Connect to sonarqube API
      $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/');
      $project = new SonarqubeProject($api, $projectKey);

      $this->assertSame(true, $project->exists());
  }

  //Test if exists() function retuns false on a not existing sonarqube project
  public function testSonarqubeProjectNotExists()
  {
      //Define sonarcloud.io project key
      $projectKey = 'not-existing-sample-project';

      //Connect to sonarqube API
      $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/');
      $project = new SonarqubeProject($api, $projectKey);

      $this->assertSame(false, $project->exists());
  }

  //Test sonarqube project measures extraction
  public function testSonarqubeProjectMeasures()
  {
      //Define sonarcloud.io project key
      $projectKey = 'org.sonarsource.scanner.cli:sonar-scanner-cli';

      //Connect to sonarqube API
      $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/');
      $project = new SonarqubeProject($api, $projectKey);

      $measures = $project->getMeasures();

      $this->assertArrayHasKey('sonarqube_key', $measures);
      $this->assertArrayHasKey('sqale_rating', $measures);
      $this->assertArrayHasKey('bugs', $measures);
      $this->assertArrayHasKey('reliability_remediation_effort', $measures);
      $this->assertArrayHasKey('security_rating', $measures);
      $this->assertArrayHasKey('vulnerabilities', $measures);
      $this->assertArrayHasKey('sqale_index', $measures);
      $this->assertArrayHasKey('security_remediation_effort', $measures);
      $this->assertArrayHasKey('coverage', $measures);
  }

}
