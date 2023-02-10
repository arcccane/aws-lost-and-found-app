<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'aws-autoloader.php';

use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;

// Get credentials from credentials.txt
$file = file_get_contents('credentials.txt');
$contents = explode("\n", $file);
$accessKeyId = explode("=", $contents[0])[1];
$secretAccessKey = explode("=", $contents[1])[1];
$sessionToken = explode("=", $contents[2])[1];

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

// Get the values uploaded by add-notif.html
$email = $_POST['email'];
$category = $_POST['category'];
$topicArn = '';

// Get all topic ARNs
$file = file_get_contents('sns-arns.txt');
$contents = explode("\n", $file);

// Subscribe to an SNS topic based on category selected
switch ($category) {
    case 'Any':
        $topicArn = explode("=", $contents[0])[1];
        break;
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
        
    $snsClient->subscribe(array(
        'Protocol' => 'email',
        'TopicArn' => $topicArn,
        'Endpoint' => $email,
    ));
    
    header("Location: index.php");
    exit;
} catch(AwsException $e) {
    echo $e->getMessage();
}

?>