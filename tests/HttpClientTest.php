<?php

namespace Tests\Forge\SonarqubeApiClient;

use PHPUnit\Framework\TestCase;
use Forge\SonarqubeApiClient\HttpClient;

class HttpClientTest extends TestCase
{
  //Test basic sonarcloud connectivity without cache and authentication
  public function testHttpClientConnectivityWithoutCache()
  {
      $api = new HttpClient('https://sonarcloud.io/api/');
      $this->assertSame(200, $api->request('GET', 'webservices/list')->getStatusCode());
  }

  //Test basic sonarcloud connectivity with cache and without authentication
  public function testHttpClientConnectivityWithCache()
  {
      $api = new HttpClient('https://sonarcloud.io/api/', null, true, 3200);
      $this->assertSame(200, $api->request('GET', 'webservices/list')->getStatusCode());
  }

}
