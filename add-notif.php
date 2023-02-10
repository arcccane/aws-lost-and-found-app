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

// Subscribe to an SNS topic based on category selected
switch ($category) {
    case 'Any':
        $topicArn = 'arn:aws:sns:us-east-1:027535722782:lost-and-found-any';
        break;
    case 'Valuable':
        $topicArn = 'arn:aws:sns:us-east-1:027535722782:lost-and-found-valuable';
        break;
    case 'Non-Valuable':
        $topicArn = 'arn:aws:sns:us-east-1:027535722782:lost-and-found-non-valuable';
        break;
    case 'Perishable':
        $topicArn = 'arn:aws:sns:us-east-1:027535722782:lost-and-found-perishable';
        break;
    case 'Other':
        $topicArn = 'arn:aws:sns:us-east-1:027535722782:lost-and-found-other';
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