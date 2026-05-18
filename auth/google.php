<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// auth/google.php
require_once __DIR__ . '/../config.php';

$client = new Google\Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope(['email', 'profile']);
$client->setPrompt('select_account');
// Remove state creation for compatibility with older Google API versions
// $_SESSION['google_state'] = $client->createState();

header('Location: ' . $client->createAuthUrl());

