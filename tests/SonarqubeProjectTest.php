<?php

namespace Tests\ForgeQC\SonarqubeApiClient;

use PHPUnit\Framework\TestCase;
use ForgeQC\SonarqubeApiClient\HttpClient;
use ForgeQC\SonarqubeApiClient\SonarqubeProject;
use GuzzleHttp\Exception\RequestException;
use \UnexpectedValueException;

class SonarqubeProjectTest extends TestCase
{
  //Test exists() function on sonarqube sample project
  public function testExists()
  {
      //Connect to sonarqube API
      $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/');

      //Test if the exists() fucntion returns true when project exists in Sonarqube
      $projectKey1 = 'org.sonarsource.scanner.cli:sonar-scanner-cli';
      $project1 = new SonarqubeProject($api, $projectKey1);
      $this->assertSame(true, $project1->exists());

      //Test if the exists() fucntion returns false when project does not exist in Sonarqube
      $projectKey2 = 'not-existing-sample-project';
      $project2 = new SonarqubeProject($api, $projectKey2);
      $this->assertSame(false, $project2->exists());
  }

  //Test exists() function error handling if wrong token is used
  public function testExistsError()
  {
      //Tel PHPUNIT that the correct behavior of the tested function is to throw an exception
      $this->expectException(RequestException::class);

      //Define sonarcloud.io project key
      $projectKey = 'org.sonarsource.scanner.cli:sonar-scanner-cli';

      //Connect to sonarqube API
      $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/', 'wrongtoken');
      $project = new SonarqubeProject($api, $projectKey);

      $result = $project->exists();
      //Should throw a RequestException
  }

  //Test sonarqube project metadata extraction
  public function testGetProperties()
  {
      //Define sonarcloud.io project key
      $projectKey = 'org.sonarsource.scanner.cli:sonar-scanner-cli';

      //Connect to sonarqube API
      $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/');
      $project = new SonarqubeProject($api, $projectKey);

      $properties = $project->getProperties();

      $this->assertArrayHasKey('key', $properties);
      $this->assertArrayHasKey('name', $properties);
      $this->assertArrayHasKey('tags', $properties);
      $this->assertArrayHasKey('visibility', $properties);
  }

  //Test sonarqube project measures extraction
  public function testGetMeasures()
  {
      //Define sonarcloud.io project key
      $projectKey = 'org.sonarsource.scanner.cli:sonar-scanner-cli';

      //Connect to sonarqube API
      $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/');
      $project = new SonarqubeProject($api, $projectKey);

      $measures = $project->getMeasures();

      $this->assertArrayHasKey('sqale_rating', $measures);
      $this->assertArrayHasKey('bugs', $measures);
      $this->assertArrayHasKey('reliability_remediation_effort', $measures);
      $this->assertArrayHasKey('security_rating', $measures);
      $this->assertArrayHasKey('vulnerabilities', $measures);
      $this->assertArrayHasKey('sqale_index', $measures);
      $this->assertArrayHasKey('security_remediation_effort', $measures);
      $this->assertArrayHasKey('coverage', $measures);
  }

  //Test sonarqube project measures history extraction with valid date parameter
  public function testGetMeasuresHistory()
  {
      //Define sonarcloud.io project key
      $projectKey = 'org.sonarsource.scanner.cli:sonar-scanner-cli';

      //Connect to sonarqube API
      $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/');
      $project = new SonarqubeProject($api, $projectKey);

      $measures = $project->getMeasuresHistory('2019-01-01');

      $this->assertArrayHasKey('sqale_rating', $measures);
      $this->assertArrayHasKey('bugs', $measures);
      $this->assertArrayHasKey('reliability_remediation_effort', $measures);
      $this->assertArrayHasKey('security_rating', $measures);
      $this->assertArrayHasKey('vulnerabilities', $measures);
      $this->assertArrayHasKey('sqale_index', $measures);
      $this->assertArrayHasKey('security_remediation_effort', $measures);
      $this->assertArrayHasKey('coverage', $measures);
  }

    //Test sonarqube project measures history extraction with wrong date parameter
  public function testGetMeasuresHistoryWrongDate()
  {
      $this->expectException(UnexpectedValueException::class);

      //Define sonarcloud.io project key
      $projectKey = 'org.sonarsource.scanner.cli:sonar-scanner-cli';

      //Connect to sonarqube API
      $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/');
      $project = new SonarqubeProject($api, $projectKey);

      $measures = $project->getMeasuresHistory('2019-18-43');
      //Should throw a UnexpectedValueException
  }


}
