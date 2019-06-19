<?php

namespace Tests\ForgeQC\SonarqubeApiClient;

use PHPUnit\Framework\TestCase;
use ForgeQC\SonarqubeApiClient\HttpClient;
use ForgeQC\SonarqubeApiClient\SonarqubeProject;
use ForgeQC\SonarqubeApiClient\SonarqubeInstance;
use GuzzleHttp\Exception\RequestException;
use Dotenv\Dotenv;
use \UnexpectedValueException;

//Use Dotenv to retrieve secret variables from .env files
//Required to use travis-ci secret variables
$dotenv = Dotenv::create(dirname(__DIR__, 1));
$dotenv->load();
$sonar_api_key = getenv('SONAR_PHPUNIT_TOKEN');

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

  //Test addGroupPermission() function
  //Valid permission values are 'admin', 'codeviewer', 'issueadmin', 'securityhotspotadmin', 'scan', 'user'
  public function testAddRemoveGroupPermission()
  {
    global $sonar_api_key;

    //Connect to sonarqube API
    $api = new HttpClient('https://sonarcloud.io/api/', $sonar_api_key);
    $instance = new SonarqubeInstance($api, 'testapi');

    //Define group name used for the test scenario
    $testGroup = 'TestGroupPermissions';

    //Create group
    $group = $instance->createGroup($testGroup);

    //Grant permission on testProjectFromApi project in 'testapi' sonarcloud.io organization
    $projectKey = 'testProjectFromApi';
    $project = new SonarqubeProject($api, $projectKey, 'testapi');

    $this->assertSame(true,$project->addGroupPermission($testGroup, 'admin'));
    $this->assertSame(true,$project->addGroupPermission($testGroup, 'codeviewer'));
    $this->assertSame(true,$project->addGroupPermission($testGroup, 'issueadmin'));
    $this->assertSame(true,$project->addGroupPermission($testGroup, 'securityhotspotadmin'));
    $this->assertSame(true,$project->addGroupPermission($testGroup, 'scan'));
    $this->assertSame(true,$project->addGroupPermission($testGroup, 'user'));

    $this->assertSame(true,$project->removeGroupPermission($testGroup, 'admin'));
    $this->assertSame(true,$project->removeGroupPermission($testGroup, 'issueadmin'));
    $this->assertSame(true,$project->removeGroupPermission($testGroup, 'securityhotspotadmin'));
    $this->assertSame(true,$project->removeGroupPermission($testGroup, 'scan'));

    //Delete group after test scenario
    $result = $instance->deleteGroup($testGroup);

  }

  //Test addUserPermission() function
  //Valid permission values are 'admin', 'codeviewer', 'issueadmin', 'securityhotspotadmin', 'scan', 'user'
  public function testAddRemoveUserPermission()
  {
    global $sonar_api_key;

    //Connect to sonarqube API
    $api = new HttpClient('https://sonarcloud.io/api/', $sonar_api_key);
    $instance = new SonarqubeInstance($api, 'testapi');

    //Define group name used for the test scenario
    $testUser = 'matt6697@github';

    //Grant permission on testProjectFromApi project in 'testapi' sonarcloud.io organization
    $projectKey = 'testProjectFromApi';
    $project = new SonarqubeProject($api, $projectKey, 'testapi');

    $this->assertSame(true,$project->addUserPermission($testUser, 'admin'));
    $this->assertSame(true,$project->addUserPermission($testUser, 'codeviewer'));
    $this->assertSame(true,$project->addUserPermission($testUser, 'issueadmin'));
    $this->assertSame(true,$project->addUserPermission($testUser, 'securityhotspotadmin'));
    $this->assertSame(true,$project->addUserPermission($testUser, 'scan'));
    $this->assertSame(true,$project->addUserPermission($testUser, 'user'));

    $this->assertSame(true,$project->removeUserPermission($testUser, 'admin'));
    $this->assertSame(true,$project->removeUserPermission($testUser, 'issueadmin'));
    $this->assertSame(true,$project->removeUserPermission($testUser, 'securityhotspotadmin'));
    $this->assertSame(true,$project->removeUserPermission($testUser, 'scan'));
  }
}
