<?php

namespace Tests\ForgeQC\SonarqubeApiClient;

use PHPUnit\Framework\TestCase;
use ForgeQC\SonarqubeApiClient\HttpClient;
use ForgeQC\SonarqubeApiClient\SonarqubeInstance;
use GuzzleHttp\Exception\ClientException;
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

  //Test getProjects() function on sonarqube online instance
  public function testGetMultipleProjectsMeasures()
  {
    //Connect to sonarqube API
    $api = new HttpClient('https://sonarcloud.io/api/');
    $instance = new SonarqubeInstance($api);

    $measures = $instance->getMultipleProjectsMeasures('Board-Voting,paysuper_paysuper-currencies');

    $this->assertArrayHasKey('Board-Voting', $measures);
    $this->assertArrayHasKey('paysuper_paysuper-currencies', $measures);
    $this->assertArrayHasKey('sqale_rating', $measures['Board-Voting']);
    $this->assertArrayHasKey('bugs', $measures['Board-Voting']);
    $this->assertArrayHasKey('reliability_remediation_effort', $measures['Board-Voting']);
    $this->assertArrayHasKey('security_rating', $measures['Board-Voting']);
    $this->assertArrayHasKey('vulnerabilities', $measures['Board-Voting']);
    $this->assertArrayHasKey('sqale_rating', $measures['Board-Voting']);
    $this->assertArrayHasKey('security_remediation_effort', $measures['Board-Voting']);
    $this->assertArrayHasKey('coverage', $measures['Board-Voting']);

    $measuresCustom = $instance->getMultipleProjectsMeasures('Board-Voting,paysuper_paysuper-currencies','sqale_index');
    $this->assertArrayHasKey('Board-Voting', $measuresCustom);
    $this->assertArrayHasKey('sqale_index', $measuresCustom['Board-Voting']);
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

    //Test delete an existing group
    $this->assertSame(true, $instance->deleteGroup('TestGroup'));

    //Test delete a non existing group
    $this->assertSame(false, $instance->deleteGroup('NonExistingGroup'));

  }

  //Test userExists() function
  public function testUserExists()
  {
    //Connect to sonarqube API
    $api = new HttpClient('https://next.sonarqube.com/sonarqube/api/');
    $instance = new SonarqubeInstance($api);

    $this->assertSame(true, $instance->userExists('admin'));
    $this->assertSame(false, $instance->userExists('non-existing-user'));
  }

  //Test createUser() function
  public function testCreateDeleteUser()
  {
    //Tel PHPUNIT that the correct behavior of the tested function is to throw an exception
    $this->expectException(ClientException::class);

    global $sonar_api_key;

    //Connect to sonarqube API
    $api = new HttpClient('https://sonarcloud.io/api/', $sonar_api_key);
    $instance = new SonarqubeInstance($api, 'testapi');

    $user = $instance->createUser('jdoe', 'John DOE', 'test@user.local');
    $userUpdated = $instance->updateUser('jdoe', 'John DOE Updated', 'test-updated@user.local');
  }

}
