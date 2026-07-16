<?php
$project_dir = 'c:\xampp\htdocs\stock_supply\wp-content\themes\astra-child';
$dir = new RecursiveDirectoryIterator($project_dir . '/model');
$iterator = new RecursiveIteratorIterator($dir);
$files = [];
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getPathname();
    }
}
$dir = new RecursiveDirectoryIterator($project_dir . '/view');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $files[] = $file->getPathname();
    }
}
// Add dashboard sidebar
$files[] = $project_dir . '/sidebar.php';

$replacements = [
    '⚙️ Edit' => '<i class="fa-solid fa-gear"></i> Edit',
    '⚙️Edit' => '<i class="fa-solid fa-gear"></i> Edit',
    '🔍 View Details' => '<i class="fa-solid fa-magnifying-glass"></i> View Details',
    '🔍 View ' => '<i class="fa-solid fa-magnifying-glass"></i> View ',
    '🔍 Search' => '<i class="fa-solid fa-magnifying-glass"></i> Search',
    '🖨️ Print Label' => '<i class="fa-solid fa-print"></i> Print Label',
    '🖨️ Print Labels' => '<i class="fa-solid fa-print"></i> Print Labels',
    '📦 Receive' => '<i class="fa-solid fa-box"></i> Receive',
    '🛠 Maintenance' => '<i class="fa-solid fa-screwdriver-wrench"></i> Maintenance',
    '⚫ Retired' => '<i class="fa-solid fa-circle text-dark"></i> Retired',
    '🟢 Available' => '<i class="fa-solid fa-circle text-success"></i> Available',
    '🔴 In Use' => '<i class="fa-solid fa-circle text-danger"></i> In Use',
    '🟡 Maintenance' => '<i class="fa-solid fa-circle text-warning"></i> Maintenance',
    '↩️ Return' => '<i class="fa-solid fa-rotate-left"></i> Return',
    '🗑 Delete' => '<i class="fa-solid fa-trash-can"></i> Delete',
    '🗑️ Delete' => '<i class="fa-solid fa-trash-can"></i> Delete',
    '📥 Import CSV' => '<i class="fa-solid fa-file-import"></i> Import CSV',
    '📥 Import ' => '<i class="fa-solid fa-file-import"></i> Import ',
    '📤 Export CSV' => '<i class="fa-solid fa-file-export"></i> Export CSV',
    '\'🟢\'' => '\'<i class="fa-solid fa-circle text-success" style="font-size:12px;"></i>\'',
    '\'🔴\'' => '\'<i class="fa-solid fa-circle text-danger" style="font-size:12px;"></i>\'',
    '\'🟡\'' => '\'<i class="fa-solid fa-circle text-warning" style="font-size:12px;"></i>\'',
    '\'⚫\'' => '\'<i class="fa-solid fa-circle text-dark" style="font-size:12px;"></i>\'',
    '"🟢"' => '"<i class=\'fa-solid fa-circle text-success\' style=\'font-size:12px;\'></i>"',
    '"🔴"' => '"<i class=\'fa-solid fa-circle text-danger\' style=\'font-size:12px;\'></i>"',
    '"🟡"' => '"<i class=\'fa-solid fa-circle text-warning\' style=\'font-size:12px;\'></i>"',
    '"⚫"' => '"<i class=\'fa-solid fa-circle text-dark\' style=\'font-size:12px;\'></i>"',
    '🟢' => '<i class="fa-solid fa-circle text-success"></i>',
    '🔴' => '<i class="fa-solid fa-circle text-danger"></i>',
    '🟡' => '<i class="fa-solid fa-circle text-warning"></i>',
    '⚫' => '<i class="fa-solid fa-circle text-dark"></i>',
    '🖨️' => '<i class="fa-solid fa-print"></i>',
    '⚙️' => '<i class="fa-solid fa-gear"></i>',
    '🔍' => '<i class="fa-solid fa-magnifying-glass"></i>',
    '📥' => '<i class="fa-solid fa-file-import"></i>',
    '📤' => '<i class="fa-solid fa-file-export"></i>',
    '↩️' => '<i class="fa-solid fa-rotate-left"></i>',
    '🗑' => '<i class="fa-solid fa-trash-can"></i>',
    '🗑️' => '<i class="fa-solid fa-trash-can"></i>',
    '📦' => '<i class="fa-solid fa-box"></i>',
    '🛠' => '<i class="fa-solid fa-screwdriver-wrench"></i>'
];

$changedFiles = 0;
foreach ($files as $file) {
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $newContent = str_replace(array_keys($replacements), array_values($replacements), $content);
    if ($content !== $newContent) {
        file_put_contents($file, $newContent);
        echo "Updated: $file\n";
        $changedFiles++;
    }
}
echo "Total files changed: $changedFiles\n";
