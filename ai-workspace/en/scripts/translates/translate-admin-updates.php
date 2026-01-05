<?php

// Script to translate admin-atualizacoes_en.json
$file = __DIR__ . '/dictionaries/admin-atualizacoes_en.json';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

$content = file_get_contents($file);
$data = json_decode($content, true);

if ($data === null) {
    die("Error decoding JSON\n");
}

$translations = [
    "System Updates" => "System Updates",
    "Run Update" => "Run Update",
    "View Log" => "View Log",
    "Last Execution" => "Last Execution",
    "Status" => "Status",
    "Actions" => "Actions",
    "Update Details" => "Update Details",
    "Execution Log" => "Execution Log",
    "Date" => "Date",
    "Log" => "Log",
    "JSON Plan" => "JSON Plan",
    "Full" => "Full",
    "Files" => "Files",
    "Database" => "Database",
    "Back" => "Back",
    "Select a log or plan." => "Select a log or plan.",
    "Invalid Log" => "Invalid Log",
    "Invalid Plan" => "Invalid Plan",
    "No updates recorded." => "No updates recorded.",
    "Execution History" => "Execution History",
    "Start" => "Start",
    "Tag" => "Tag",
    "Modo" => "Mode",
    "Status" => "Status",
    "Remov." => "Rem.",
    "Copied" => "Copied",
    "End" => "End",
    "Cancel" => "Cancel",
    "Logs Recentes" => "Recent Logs",
    "Progress" => "Progress",
    "Trigger Page" => "Trigger Page",
    "Advanced Options" => "Advanced Options"
];

// Translate variables
foreach ($data['variables'] as $key => $value) {
    if (isset($translations[$value])) {
        $data['variables'][$key] = $translations[$value];
        echo "Translated variable: $key\n";
    }
}

$newContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
file_put_contents($file, $newContent);

echo "✅ Translation completed!\n";

?>
