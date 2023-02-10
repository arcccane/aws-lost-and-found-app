<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\Sns\SnsClient;
use Aws\Rekognition\RekognitionClient;
use Aws\Exception\AwsException;

// Get credentials from credentials.txt
$file = file_get_contents('credentials.txt');
$contents = explode("\n", $file);
$accessKeyId = explode("=", $contents[0])[1];
$secretAccessKey = explode("=", $contents[1])[1];
$sessionToken = explode("=", $contents[2])[1];

// Access S3 using the AWS SDK
$s3Client = new S3Client([
  'region' => 'us-east-1',
  'version' => 'latest',
  'credentials' => [
    'key' => $accessKeyId,
    'secret' => $secretAccessKey,
    'token' => $sessionToken,
  ],
]);

// Access SNS using the AWS SDK
$snsClient = SnsClient::factory([
    'version' => 'latest',
    'region'  => 'us-east-1',
    'credentials' => [
        'key' => $accessKeyId,
        'secret' => $secretAccessKey,
        'token' => $sessionToken,
    ],
]);

// Access Rekognition using the AWK SDK
$rekognitionClient = new RekognitionClient([
    'version' => 'latest',
    'region' => 'us-east-1',
    'credentials' => [
        'key' => $accessKeyId,
        'secret' => $secretAccessKey,
        'token' => $sessionToken,
    ],
]);

// Get RDS endpoint
$file = file_get_contents('rds-endpoint.txt');
$contents = explode("\n", $file);

// Connect to the RDS database
$host = explode("=", $contents[0])[1];
$user = "admin";
$password = "password";
$db = "lost_and_found_db";
$conn = mysqli_connect($host, $user, $password, $db);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");

// If image is uploaded
if (isset($_FILES['image'])) {
  
    $image = $_FILES['image'];
    
    // If AI checkbox is checked. Call the `detectLabels` function to describe the image
    if(isset($_POST['useAI'])) {
      
      try {
        $rekognition_result = $rekognitionClient->detectLabels([
            'Image' => [
              'Bytes' => file_get_contents($image['tmp_name']),
            ],
            'MaxLabels' => 10,
            'MinConfidence' => 75,
        ]);
        
        $labels = $rekognition_result->get('Labels');
        $highestConfidence = 0;
        $highestLabel = "";
        
        // Set the item description to the label with the highest confidence
        foreach ($labels as $label) {
          if ($label['Confidence'] > $highestConfidence) {
              $highestConfidence = $label['Confidence'];
              $highestLabel = $label['Name'] . " (Confidence: " . round($label['Confidence'], 2) . "%)";
          }
        };
        
        $description = $highestLabel;
        $category = 'Other';
        
      } catch (AwsException $e) {
          // output error message if fails
          echo $e->getMessage();
          error_log($e->getMessage());
      }
    } else {
        $description = $_POST['description'];
        $category = $_POST['category'];
    };
    
     // Upload the file to S3
    try {
      $s3_result = $s3Client->putObject([
        'Bucket' => 'lost-and-found-images-for-db',
        'Key' => $description,
        'Body' => fopen($image['tmp_name'], 'r'),
      ]);
    
      // Get the URL of the image
      $imageUrl = $s3_result['ObjectURL'];
    } catch (AwsException $e) {
      echo $e->getMessage();
    }
    
    // Prepare the SQL statement
    $sql = "INSERT INTO lost_and_found (image, description, category) VALUES ('$imageUrl', '$description', '$category')";

    // Execute the SQL statement
    if (mysqli_query($conn, $sql)) {
        
        // Publish message to subscribed topics based on category of item added
        
        // Get all topic ARNs
        $file = file_get_contents('sns-arns.txt');
        $contents = explode("\n", $file);
        
        // Publish to subscribers of any item
        try {
          $snsClient->publish(array(
            'TopicArn' => explode("=", $contents[0])[1],
            'Subject' => 'New Item Uploaded',
            'Message' => 'A new item has been uploaded. ' .
                         'Check it out at: ' . 'http://' . file_get_contents('http://checkip.amazonaws.com/'),
          ));
        } catch (AwsException $e) {
            echo $e->getMessage();
        }
        
        $topicArn = '';
        
        switch ($category) {
          case 'Valuable':
            $topicArn = explode("=", $contents[1])[1];
            break;
          case 'Non-Valuable':
            $topicArn = explode("=", $contents[2])[1];
            break;
          case 'Perishable':
            $topicArn = explode("=", $contents[3])[1];
            break;
          case 'Other':
            $topicArn = explode("=", $contents[4])[1];
            break;
          default:
            break;
        };
        
        try {
          // Publish to subscribers of a category
          $snsClient->publish(array(
            'TopicArn' => $topicArn,
            'Subject' => 'New Item Uploaded',
            'Message' => 'A new item of ' . $category . ' category has been uploaded. ' . 
                         'Check it out at: ' . 'http://' . file_get_contents('http://checkip.amazonaws.com/'),
          ));
        } catch(AwsException $e) {
            echo $e->getMessage();
        }
        
        header("Location: index.php");
        exit;
    } else {
        echo "Error adding record: " . mysqli_error($conn);
    }
    
}

// Close the database connection
mysqli_close($conn);

?>