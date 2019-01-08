<?php
    require_once(dirname(__FILE__) . '/lib/response_helpers.php');
    require_once(dirname(__FILE__) . '/lib/helpers.php');
    require_once(dirname(__FILE__) . '/system/ApplicationSettings.php');

    require_once(dirname(__FILE__) . '/lib/access_check.php');

    session_start();

    if (!isset($_GET['fileKey']))
        exitWith404("fileKey not supplied in request.");

    $fileKey = $_GET['fileKey'];

    if (!isset($_SESSION[MFTP_SESSION_KEY_PREFIX . $fileKey]))
        exitWith404("fileKey not found in session.");

    $fileInfo = $_SESSION[MFTP_SESSION_KEY_PREFIX . $fileKey];

    unset($_SESSION[MFTP_SESSION_KEY_PREFIX . $fileKey]);

    $outputFileName = $fileInfo['fileName'];
    $sourcePath = $fileInfo['path'];

    $escapedOutputFileName = str_replace('"', '\"', $outputFileName);

    $fileSize = filesize($sourcePath);

    if ($fileSize === false)
        die("File has no size");

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"" . $escapedOutputFileName . "\"");
    header("Content-Description: File Transfer");
    header("Content-Transfer-Encoding: Binary");
    header("Content-Length: " . $fileSize);

    flush();

    $fp = @fopen($sourcePath, "r");
    while (!feof($fp)) {
        echo @fread($fp, 65536);
        @flush();
    }

    @fclose($fp);
    @unlink($sourcePath);