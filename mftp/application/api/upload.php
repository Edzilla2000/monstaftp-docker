<?php
    session_start();

    require_once(dirname(__FILE__) . "/constants.php");
    includeMonstaConfig();
    require_once(dirname(__FILE__) . '/system/ApplicationSettings.php');
    require_once(dirname(__FILE__) . '/request_processor/RequestMarshaller.php');
    require_once(dirname(__FILE__) . '/lib/helpers.php');
    require_once(dirname(__FILE__) . '/lib/response_helpers.php');
    require_once(dirname(__FILE__) . '/file_sources/PathOperations.php');
    require_once(dirname(__FILE__) . '/file_sources/connection/ArchiveExtractor.php');

    dieIfNotPOST();

    require_once(dirname(__FILE__) . '/lib/access_check.php');

    $marshaller = new RequestMarshaller();

    clearOldTransfers();

    try {
        $rawRequest = $_SERVER['HTTP_X_MONSTA'];

        $jsonEncodedRequest = b64DecodeUnicode($rawRequest);

        $request = json_decode($jsonEncodedRequest, true);

        $marshaller->testConfiguration($request, false);

        $uploadPath = getTempTransferPath($request['context']['remotePath']);

        monstaUploadDebug("STARTED  READING UPLOAD TO $uploadPath");

        readUpload($uploadPath);

        monstaUploadDebug("FINISHED READING UPLOAD TO $uploadPath");

        $request['context']['localPath'] = $uploadPath;
        try {
            if ($request['actionName'] == "uploadArchive") {
                $applicationSettings = new ApplicationSettings(APPLICATION_SETTINGS_PATH);

                $extractor = new ArchiveExtractor($uploadPath, null, $applicationSettings->getSkipMacOsSpecialFiles());

                $archiveFileCount = $extractor->getFileCount(); // will throw exception if it's not valid

                $fileKey = generateRandomString(16);

                $_SESSION[MFTP_SESSION_KEY_PREFIX . $fileKey] = array(
                    "archivePath" => $uploadPath,
                    "extractDirectory" => PathOperations::remoteDirname($request['context']['remotePath'])
                );

                $response = array(
                    "success" => true,
                    "fileKey" => $fileKey,
                    "fileCount" => $archiveFileCount
                );

                print json_encode($response);
            } else {
                print $marshaller->marshallRequest($request);
                cleanupTempTransferPath($uploadPath);
            }
        } catch (Exception $e) {
            cleanupTempTransferPath($uploadPath);
            throw $e;
        }
        // this should be done in a finally to avoid repeated code but we need to support PHP < 5.5
    } catch (Exception $e) {
        handleExceptionInRequest($e);
    }

    $marshaller->disconnect();