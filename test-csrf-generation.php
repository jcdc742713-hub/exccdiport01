<?php

declare(strict_types=1);

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Simulate a GET request to /login
$request = \Illuminate\Http\Request::create('/login', 'GET');
$request = $request->setUserResolver(function () { return null; });

// Make Laravel process this request through the middleware stack
$response = $kernel->handle($request);

// Check if CSRF token is in the response
$content = (string) $response->getContent();

echo "GET /login Response Status: " . $response->getStatusCode() . "\n";
echo "Has csrf-token meta tag: " . (strpos($content, 'csrf-token') !== false ? 'YES' : 'NO') . "\n";
echo "Has Inertia app: " . (strpos($content, 'Inertia') !== false ? 'YES' : 'NO') . "\n";
echo "Has @vite: " . (strpos($content, 'vite') !== false ? 'YES' : 'NO') . "\n";

// Extract the CSRF token if present
if (preg_match('/meta name="csrf-token" content="([^"]+)"/', $content, $matches)) {
    echo "CSRF Token: " . substr($matches[1], 0, 20) . "...\n";
} else {
    echo "CSRF Token: NOT FOUND\n";
}

echo "\n";
