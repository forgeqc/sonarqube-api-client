<?php
  require '../vendor/autoload.php';

  use Forge\SonarqubeApiClient\HttpClient;
  use Forge\SonarqubeApiClient\SonarqubeProject;
  use Forge\SonarqubeApiClient\SonarqubeInstance;

  $api = new HttpClient('https://sonarcloud.io/api/', $argv[1]);

  $projectKey = 'testProjectFromApi';
  $project = new SonarqubeProject($api, $projectKey);

  if (!$project->exists()) {
    $project->create('Test Project From Api', 'public', 'testapi');
  }

  var_dump($project->getProperties());

  $instance = new SonarqubeInstance($api);
  var_dump($instance->getProjects());

 ?>
