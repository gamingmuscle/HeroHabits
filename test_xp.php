<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$children = \App\Models\Child::all();
foreach ($children as $child) {
    echo "=== {$child->name} ===\n";
    echo "Level: {$child->level}\n";
    echo "XP: {$child->experience_points}\n";
    echo "Progress: {$child->progressPercentage()}%\n";
    echo "XP to next: {$child->experienceToNextLevel()}\n";

    $currentLevelXP = \App\Models\CharacterLevel::experienceForLevel($child->level);
    $nextLevelXP = \App\Models\CharacterLevel::experienceForLevel($child->level + 1);
    echo "Level {$child->level} requires: {$currentLevelXP} XP\n";
    $nextLevel = $child->level + 1;
    echo "Level {$nextLevel} requires: {$nextLevelXP} XP\n";
    echo "\n";
}

// Show all levels
echo "=== ALL LEVEL THRESHOLDS ===\n";
$levels = \App\Models\CharacterLevel::orderBy('level')->get();
foreach ($levels as $level) {
    echo "Level {$level->level}: {$level->experience_required} XP\n";
}
