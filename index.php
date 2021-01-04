<?php

require_once __DIR__ . "/api/Youtube.php";

use API\Youtube;

if (!isset($_PORT['limit']) && !isset($_POST['q'])) {
    echo "Failed";

    return false;
}

$files = $_POST['files'] ?? 1;
$fileName = $_POST['name'] ?? "channels";
$allFiles = [];
$nextPage = '';
for ($x = 0; $x < $files; $x++) {
    $data = (new Youtube)->init($_POST['limit'], $nextPage, $_POST['q'], $_POST['name'] ?? "channels")->action()->convertIntoCSV()->download();
    $nextPage = $data['nextPage'];
    $allFiles[] = $data;
}



if (count($allFiles) === 1) {
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=" . $data['name'] . ".csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo $allFiles[0]['data'];
} else {
    $zip = new ZipArchive();
    $zipName = "$fileName.zip";

    if ($zip->open(getcwd() . '/' . $zipName, ZipArchive::CREATE) === TRUE) {
        for ($x = 0; $x < count($allFiles); $x++) {
            $zip->addFromString($fileName . "_$x.csv",  $allFiles[$x]['data']);
        }
        $zip->close();
    } else {
        echo 'failed to generate the ZIP file';
        die;
    }

    header('Content-type: application/zip');
    header("Content-Disposition: attachment; filename=" . $zipName);
    header('Content-Length: ' . filesize($zipName));

    flush();
    readfile($zipName);
    // delete file
    unlink($zipName);
}
