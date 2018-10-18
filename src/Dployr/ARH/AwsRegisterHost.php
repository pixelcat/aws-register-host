<?php

namespace Dployr\ARH;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

class AwsRegisterHost {

  /**
   * Instance of simpleDbClient.
   *
   * @var \Aws\DynamoDb\DynamoDbClient
   */
  private $dynamoDbClient;

  /**
   * Instance of marshaler.
   *
   * @var \Aws\DynamoDb\Marshaler
   */
  private $marshaler;

  const TABLE_NAME = 'dployr_dployments';

  /**
   * Constructor for AwsRegisterHost.
   *
   * AwsRegisterHost constructor.
   *
   * @param \Aws\DynamoDb\DynamoDbClient $dynamoDbClient
   * @param \Aws\DynamoDb\Marshaler $marshaler
   *
   */
  public function __construct(DynamoDbClient $dynamoDbClient, Marshaler $marshaler) {

    $this->dynamoDbClient = $dynamoDbClient;
    $this->marshaler = $marshaler;
  }

  /**
   * @param string $tugboatPreviewName
   * @param string $serviceName
   * @param string $serviceToken
   */
  public function registerHost($tugboatPreviewName, $serviceName, $serviceToken) {
    $item = \json_encode([
        'hashedId' => sprintf('%s::%s', $tugboatPreviewName, $serviceName),
        'previewName' => $tugboatPreviewName,
        'serviceName' => $serviceName,
        'serviceToken' => $serviceToken,
      ]
    );

    $this->dynamoDbClient->putItem([
      'TableName' => self::TABLE_NAME,
      'Item' => $this->marshaler->marshalJson($item),
    ]);
  }

  public function createTables() {
    $schema = [
      'TableName' => self::TABLE_NAME,
      'KeySchema' => [
        [
          'AttributeName' => 'previewName',
          'KeyType' => 'HASH',
        ],
        [
          'AttributeName' => 'serviceName',
          'KeyType' => 'RANGE',
        ],
      ],
      'AttributeDefinitions' => [
        [
          'AttributeName' => 'previewName',
          'AttributeType' => 'S',
        ],
        [
          'AttributeName' => 'serviceName',
          'AttributeType' => 'S',
        ],
        // [
        //          'AttributeName' => 'serviceToken',
        //          'AttributeType' => 'S',
        //        ],
      ],
      'ProvisionedThroughput' => [
        'ReadCapacityUnits' => 10,
        'WriteCapacityUnits' => 10,
      ],
    ];

    try {
      try {
        $this->dynamoDbClient->describeTable(['TableName' => self::TABLE_NAME]);
      }
      catch (DynamoDbException $ex) {
        $result = $this->dynamoDbClient->createTable($schema);
        print("Created Table.\n");
        print($result['TableDescription']['TableStatus'] . "\n");
      }
    }
    catch (DynamoDbException $ex) {
      print("Unable to create table. Reason: \n");
      print($ex->getMessage() . "\n");
    }
  }

  /**
   * @param string $deploymentName
   *
   * @return array
   */
  public function getHostsForDeployment($deploymentName) {
    $eav = $this->marshaler->marshalJson(\json_encode([':previewName' => $deploymentName]));
    $queryObj = [
      'TableName' => self::TABLE_NAME,
      'KeyConditionExpression' => '#previewName = :previewName',
      'ExpressionAttributeNames' => [ '#previewName' => 'previewName'],
      'ExpressionAttributeValues' => $eav,
    ];
    $result = $this->dynamoDbClient->query($queryObj);
    $items = $result['Items'];
    $returnItems = [];
    foreach ($items as $item) {
      $adjustedItem = [];
      foreach ($item as $key => $value) {
        $adjustedItem[$key] = $value['S'];
      }
      $returnItems[] = $adjustedItem;
    }
    return $returnItems;
  }
}
