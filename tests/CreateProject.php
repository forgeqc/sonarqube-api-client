<?php
  require '../vendor/autoload.php';

  use Forge\SonarqubeApiClient\HttpClient;
  use Forge\SonarqubeApiClient\SonarqubeProject;

  $api = new HttpClient('https://sonarcloud.io/api/', $token);

  $projectKey = 'testProjectFromApi';
  $project = new SonarqubeProject($api, $projectKey);

  if (!$project->exists()) {
    $project->create('Test Project From Api', 'public', 'testapi');
  }

 ?>
