<?php

require __DIR__ . '/vendor/autoload.php';

use Mailtrap\Helper\ResponseHelper;
use Mailtrap\MailtrapClient;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\Mime\Address;

echo "Testing Mailtrap API...\n\n";

$apiKey = '9f1e568fad6c6ab746c317f8c55ad3fc';
$inboxId = 4232290;

echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Inbox ID: $inboxId\n";
echo "Mode: Sandbox\n\n";

try {
    $email = (new MailtrapEmail())
        ->from(new Address('test@example.com', 'Test Sender'))
        ->to(new Address('recipient@example.com', 'Test Recipient'))
        ->subject('Test Email from PHP')
        ->text('This is a test email to verify Mailtrap configuration.');

    $mailtrap = MailtrapClient::initSendingEmails(
        apiKey: $apiKey,
        isSandbox: true,
        inboxId: $inboxId
    );

    echo "Sending email...\n";
    $response = $mailtrap->send($email);
    
    echo "\n✅ SUCCESS!\n\n";
    echo "Response:\n";
    print_r(ResponseHelper::toArray($response));
    
} catch (\Exception $e) {
    echo "\n❌ ERROR!\n\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "\nFull trace:\n";
    echo $e->getTraceAsString();
}
