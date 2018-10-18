#!/usr/bin/env php
<?php

namespace Dployr\Bin;

require 'vendor/autoload.php';

use Aws\DynamoDb\Marshaler;
use Aws\Sdk;
use Dployr\ARH\AwsRegisterHost;

$sdk = new Sdk([
  'region' => 'us-east-1',
  'version' => 'latest',
]);

$dynamoDbClient = $sdk->createDynamoDb();
$marshaler = new Marshaler();
$awsRegisterHost = new AwsRegisterHost($dynamoDbClient, $marshaler);
$awsRegisterHost->createTables();
$awsRegisterHost->registerHost($argv[1], $argv[2], $argv[3]);