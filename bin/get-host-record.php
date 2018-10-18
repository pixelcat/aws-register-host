#!/usr/bin/env php
<?php

namespace Dployr\Bin;

require 'vendor/autoload.php';

use Aws\DynamoDb\Marshaler;
use Dployr\ARH\AwsRegisterHost;

$sdk = new \Aws\Sdk([
  'region' => 'us-east-1',
  'version' => 'latest',
]);
$dynamoDbClient = $sdk->createDynamoDb();
$marshaler = new Marshaler();
$awsRegisterHost = new AwsRegisterHost($dynamoDbClient, $marshaler);
$deploymentName = $argv[1];
print(\GuzzleHttp\json_encode($awsRegisterHost->getHostsForDeployment($deploymentName), JSON_PRETTY_PRINT));
