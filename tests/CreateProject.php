<?php
  require '../vendor/autoload.php';

  use ForgeQC\SonarqubeApiClient\HttpClient;
  use ForgeQC\SonarqubeApiClient\SonarqubeProject;
  use ForgeQC\SonarqubeApiClient\SonarqubeInstance;

  $api = new HttpClient('https://sonarcloud.io/api/', $argv[1]);

  $projectKey = 'forgeqc_sonarqube-api-client';
  $project = new SonarqubeProject($api, $projectKey);

  //if (!$project->exists()) {
  //  $project->create('Test Project From Api', 'public', 'testapi');
  //}

  var_dump($project->getMeasuresHistory('2019-06-45'));

  //$instance = new SonarqubeInstance($api);
  //var_dump(count($instance->getProjects()));

 ?>
