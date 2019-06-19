# sonarqube-api-client
[![Build Status](https://travis-ci.org/forgeqc/sonarqube-api-client.svg?branch=master)](https://travis-ci.org/forgeqc/sonarqube-api-client)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=forgeqc_sonarqube-api-client&metric=alert_status)](https://sonarcloud.io/dashboard?id=forgeqc_sonarqube-api-client)

PHP client library for Sonarqube API access from a PHP project. The library has been extensively tested with **sonarqube** and **sonarcloud.io** as it is fully compatible with sonarcloud.io organizations.

## Installation

Via [composer](https://getcomposer.org)

```bash
composer require forgeqc/sonarqube-api-client
```

## General API Usage

### List all projects of a Sonarqube instance
The getprojects() function returns an array containing all the projects of a sonarqube instance. The example below retrieves all projects from [https://sonarcloud.io].

```
require '../vendor/autoload.php';

use Forge\SonarqubeApiClient\HttpClient;
use Forge\SonarqubeApiClient\SonarqubeProject;
use Forge\SonarqubeApiClient\SonarqubeInstance;

$api = new HttpClient('https://sonarcloud.io/api/');

$instance = new SonarqubeInstance($api);
$projects = $instance->getProjects();

```

### Manage a single Sonarqube project
The **SonarqubeProject** class allows creation of a new sonarqube project or metadata / measures extraction from an existing sonarqube project.

```
require '../vendor/autoload.php';

use Forge\SonarqubeApiClient\HttpClient;
use Forge\SonarqubeApiClient\SonarqubeProject;
use Forge\SonarqubeApiClient\SonarqubeInstance;

$api = new HttpClient('https://sonarcloud.io/api/', '<secret token>');

$projectKey = 'testProjectFromApi';
$project = new SonarqubeProject($api, $projectKey);

if (!$project->exists()) {
  $project->create('Test Project From Api', 'public', 'testapi');
}

$measures = $project->getMeasures();

$measuresHistory = $project->getMeasuresHistory('2019-06-45');
```

### Manage Permissions
Create or delete a Sonarqube group :

```
$api = new HttpClient('https://sonarcloud.io/api/', $sonar_api_key);
$sonarcloudOrganization = 'testapi';
$instance = new SonarqubeInstance($api, $sonarcloudOrganization);

//Group creation
$group = $instance->createGroup('TestGroup');

//Group deletion
$result = $instance->deleteGroup('TestGroup');
```

Grant project permissions to a group. The library provides functions to **add** or **remove** projects permissions. The **codeviewer** and **user** permissions can't be removed from a public project. Functions return `true` if permissions are successfully granted or removed.

```
$api = new HttpClient('https://sonarcloud.io/api/', $sonar_api_key);
$sonarcloudOrganization = 'testapi';

//Grant permission on testProjectFromApi project in 'testapi' sonarcloud.io organization
$projectKey = 'testProjectFromApi';
$project = new SonarqubeProject($api, $projectKey, $sonarcloudOrganization);

//Define group name used for the test scenario
$testGroup = 'TestGroupPermissions';

$project->addGroupPermission($testGroup, 'admin');
$project->addGroupPermission($testGroup, 'codeviewer');
$project->addGroupPermission($testGroup, 'issueadmin');
$project->addGroupPermission($testGroup, 'securityhotspotadmin');
$project->addGroupPermission($testGroup, 'scan');
$project->addGroupPermission($testGroup, 'user');

$project->removeGroupPermission($testGroup, 'admin');
$project->removeGroupPermission($testGroup, 'issueadmin');
$project->removeGroupPermission($testGroup, 'securityhotspotadmin');
$project->removeGroupPermission($testGroup, 'scan');
```

## Contributing
This project is currently under development. Feel free to fork this project, apply modifications and send pull requests. SonarQube official API is not part of this project.
