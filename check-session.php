<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Session Driver: " . config('session.driver') . "\n";
echo "Session Lifetime: " . config('session.lifetime') . "\n";
echo "Session Path: " . storage_path('framework/sessions') . "\n";
echo "Session Path Exists: " . (file_exists(storage_path('framework/sessions')) ? 'YES' : 'NO') . "\n";
echo "Session Files: " . count(glob(storage_path('framework/sessions/*'))) . "\n";

$sessionFiles = array_slice(glob(storage_path('framework/sessions/*')), 0, 3);
foreach ($sessionFiles as $file) {
    echo "  - " . basename($file) . "\n";
}
