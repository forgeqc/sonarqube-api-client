# sonarqube-api-client
PHP client library for Sonarqube API access from a PHP project.

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

## Contributing
This project is currently under development. Feel free to fork this project, apply modifications and send pull requests. SonarQube official API is not part of this project.
