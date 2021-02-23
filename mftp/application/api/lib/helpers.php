<?php
    require_once(dirname(__FILE__) . "/../constants.php");
    require_once(dirname(__FILE__) . "/../file_sources/PathOperations.php");
    require_once(dirname(__FILE__) . '/../licensing/KeyPairSuite.php');
    require_once(dirname(__FILE__) . '/../licensing/LicenseReader.php');
    require_once(dirname(__FILE__) . '/../licensing/LicenseFactory.php');

    if (!MONSTA_DEBUG)
        includeMonstaConfig();

    function monstaUploadDebug($message) {
        if (defined("MONSTA_UPLOAD_LOGGING") && MONSTA_UPLOAD_LOGGING) {
            error_log($message);
        }
    }

    function monstaGetTempDirectory() {
        // a more robust way of getting the temp directory

        $configTempDir = defined("MONSTA_TEMP_DIRECTORY") ? MONSTA_TEMP_DIRECTORY : "";

        if ($configTempDir != "")
            return $configTempDir;

        return ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
    }

    function generateRandomString($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            try {
                $randomVal = random_int(0, $charactersLength - 1);
            } catch (TypeError $e) {
                $randomVal = rand(0, $charactersLength - 1);
            } catch (Error $e) {
                // This is required, if you do not need to do anything just rethrow.
                throw $e;
            } catch (Exception $e) {
                $randomVal = rand(0, $charactersLength - 1);
            }

            $randomString .= $characters[$randomVal];
        }

        return $randomString;
    }

    function languageCmp($a, $b) {
        strcmp($a[1], $b[1]);
    }

    function readLanguagesFromDirectory($languageDir) {
        $languageFiles = scandir($languageDir);

        $languages = array();

        foreach ($languageFiles as $languageFile) {
            if (strlen($languageFile) < 6)
                continue;

            $splitFileName = explode(".", $languageFile);

            if (count($splitFileName) != 2)
                continue;

            if ($splitFileName[1] != "json")
                continue;

            $fullFilePath = PathOperations::join($languageDir, $languageFile);

            $languageContentsRaw = file_get_contents($fullFilePath);
            if ($languageContentsRaw === false)
                continue;

            $languageContents = json_decode($languageContentsRaw, true);

            if ($languageContents === false)
                continue;

            if (!isset($languageContents['Language Display Name']))
                continue;

            $languages[] = array($splitFileName[0], $languageContents['Language Display Name']);
        }

        usort($languages, "languageCmp");

        return $languages;
    }

    function monstaBasename($path) {
        // manual basename splitting because built in function may not work with special characters
        $splitPath = explode("/", $path);
        return $splitPath[count($splitPath) - 1];
    }

    function monstaReplaceExtension($filename, $new_extension) {
        $info = pathinfo($filename);
        return ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '') 
            . $info['filename'] 
            . '.' 
            . $new_extension;
    }

    function getMonstaSharedTransferDirectory() {
        // Directory that any transfers in the system go into so it can be cleared out regularly
        $uploadDirPath = PathOperations::join(monstaGetTempDirectory(), "mftp-transfers");

        if (!file_exists($uploadDirPath)) {
            mkdir($uploadDirPath);
        }

        return $uploadDirPath;
    }

    function monstaTempnam($dir, $prefix = "") {
        if (strlen($prefix) > 32) {
            // tempnam on Windows appears to fail if the prefix is too long, so limit it to 32 chars using MD5.
            // It's not the best hashing algorithm but it's not being used for security here
            $prefix = md5($prefix);
        }

        return tempnam($dir, $prefix);
    }

    function clearOldTransfers() {
        $sharedUploadDir = getMonstaSharedTransferDirectory();

        if (!file_exists($sharedUploadDir) || !is_dir($sharedUploadDir))
            return;

        $dirHandle = @opendir($sharedUploadDir);

        while (false !== ($entry = @readdir($dirHandle))) {
            if ($entry !== "." && $entry !== "..") {
                $itemPath = PathOperations::join($sharedUploadDir, $entry);
                $modifiedTime = filemtime($itemPath);
                if (time() - $modifiedTime > MFTP_TMP_UPLOAD_TIMEOUT_SECONDS) {
                    recursiveUnlink($itemPath);
                }
            }
        }
    }

    function getTempTransferPath($remotePath) {
        $fileName = monstaBasename($remotePath);

        $monstaSharedDir = getMonstaSharedTransferDirectory();

        if (!file_exists($monstaSharedDir)) {
            @mkdir($monstaSharedDir);
        }

        $tempName = monstaTempnam($monstaSharedDir, "");

        if (file_exists($tempName))
            unlink($tempName);

        mkdir($tempName);

        return $tempName . "/" . $fileName;
    }

    function recursiveUnlink($path) {
        if (!@file_exists($path))
            return;

        if (!@is_dir($path)) {
            if (@unlink($path)) {
                monstaUploadDebug("UNLINK SUCCESS 1: $path");
            } else {
                monstaUploadDebug("UNLINK FAILURE 1: $path");
            }
            return;
        }

        $dirHandle = @opendir($path);

        if ($dirHandle === false) {
            monstaUploadDebug("OPENDIR FAILURE 1: $path");
            return;
        }

        while (false !== ($entry = @readdir($dirHandle))) {
            if ($entry !== "." && $entry !== "..") {
                $itemPath = PathOperations::join($path, $entry);
                if (is_dir($itemPath)) {
                    recursiveUnlink($itemPath);
                } else {
                    if (@unlink($itemPath)) {
                        monstaUploadDebug("UNLINK SUCCESS 2: $itemPath");
                    } else {
                        monstaUploadDebug("UNLINK FAILURE 2: $itemPath");
                    }
                }
            }
        }

        @closedir($dirHandle);
        if (@rmdir($path)) {
            monstaUploadDebug("RMDIR SUCCESS 1: $path");
        } else {
            monstaUploadDebug("RMDIR FAILURE 1: $path");
        }
    }

    function cleanupTempTransferPath($transferPath) {
        if (!@unlink($transferPath)) {
            monstaUploadDebug("UNLINK TRANSFER PATH FAILURE 1: $transferPath");
            return;
        } else {
            monstaUploadDebug("UNLINK TRANSFER PATH SUCCESS 1: $transferPath");
        }

        $transferDir = dirname($transferPath);

        recursiveUnlink($transferDir);
    }

    function readUpload($uploadPath, $mode="w+") {
        $inputHandler = fopen('php://input', "r");
        $fileHandler = fopen($uploadPath, $mode);

        while (FALSE !== ($buffer = fgets($inputHandler, 65536)))
            fwrite($fileHandler, $buffer);

        fclose($inputHandler);
        fclose($fileHandler);
    }

    function readDefaultMonstaLicense() {
        $keyPairSuite = new KeyPairSuite(PUBKEY_PATH);
        $licenseReader = new LicenseReader($keyPairSuite);
        $licenseArr = $licenseReader->readLicense(MONSTA_LICENSE_PATH);

        return LicenseFactory::getMonstaLicenseFromArray($licenseArr);
    }

    function b64DecodeUnicode($rawData) {
        if ($rawData == "")
            return "";

        $decodedData = base64_decode($rawData);

        $urlEncodedData = "";

        foreach (str_split($decodedData) as $char)
            $urlEncodedData .= sprintf("%%%02x", ord($char));

        return urldecode($urlEncodedData);
    }

    function b64EncodeUnicode($rawData) {


        $urlEncodedData = rawurlencode($rawData);

        $ordinalizedData = preg_replace_callback("/%([0-9A-F]{2})/",
            function ($matches) {
                $intVal = intval($matches[1], 16);
                return chr($intVal);
            }, $urlEncodedData);

        return base64_encode($ordinalizedData);
    }

    function attemptToUtf8String($data) {
        if (!function_exists("mb_convert_encoding") || !function_exists("mb_detect_encoding"))
            return $data;

        $inputDataEncoding = @mb_detect_encoding($data, 'UTF-8, ISO-8859-1', true);

        if ($inputDataEncoding === false)
            return $data;

        return mb_convert_encoding($data, 'UTF-8', $inputDataEncoding);

    }

    function fileGetContentsInUtf8($filePath) {
        $content = file_get_contents($filePath);

        return attemptToUtf8String($content);
    }

    function succeededFailedText($successFailure) {
        return $successFailure ? "succeeded" : "failed";
    }

    function getNormalizedOSName() {
        if (strtoupper(substr(PHP_OS, 0, 5)) == 'LINUX')
            return "Linux";
        elseif (strtoupper(substr(PHP_OS, 0, 7)) == 'FREEBSD')
            return "FreeBSD";
        elseif (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
            return "Windows";
        else
            return PHP_OS;
    }

    function booleanToJsValue($boolVal) {
        return $boolVal ? "true" : "false";
    }

    function ftpConnectionAvailable() {
        return function_exists("fsockopen") || function_exists("ftp_connect");
    }

    function outputStreamKeepAlive() {
        echo(" ");
        // safe to have spaces in json data, but need to output some data so there's something to flush
        // and then PHP will know if the client is gone

        ob_flush();
        flush();

        // we probably won't get here because script will stop after flush() fails because connection closed
        if (connection_aborted())
            throw new Exception("Connection was closed");
    }

    function getMonstaInstallUrl() {
        $serverName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '');
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        if (preg_match("/\\.php$/", $requestUri))
            $requestUri = dirname($requestUri);

        $https = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? "s" : "";

        return "http" . $https . "://" . $serverName . $requestUri;
    }

    function generateVersionQueryString($isLicensed, $isHostEdition) {
        $installUrl = getMonstaInstallUrl();

        $debugArg = MONSTA_DEBUG ? "&amp;d=1" : "";

        $mftpEdition = $isLicensed ? ($isHostEdition ? 'e' : 'p') : 's';

        $versionQS = "v=" . MONSTA_VERSION . "&amp;r=" . urlencode($installUrl) . "&amp;os=" . getNormalizedOSName() . "&amp;e=" . $mftpEdition . $debugArg;
        return $versionQS;
    }

    function normalizePath($path) {
        // turn windows separators into unix
        $path = str_replace("\\", "/", $path);

        // Remove any kind of funky unicode whitespace
        $normalized = preg_replace('#\p{C}+|^\./#u', '', $path);

        // Path remove self referring paths ("/./").
        $normalized = preg_replace('#/\.(?=/)|^\./|\./$#', '', $normalized);

        // Regex for resolving relative paths
        $regex = '#\/*[^/\.]+/\.\.#Uu';

        while (preg_match($regex, $normalized)) {
            $normalized = preg_replace($regex, '', $normalized);
        }

        if (preg_match('#/\.{2}|\.{2}/#', $normalized)) {
            throw new LogicException('Path is outside of the defined root, path: [' . $path . '], resolved: [' . $normalized . ']');
        }

        return $normalized;
    }

    // Below functions are temp for moving theme.css around
    function removeEmptyValuesFromArray($haystack) {
        $newAr = array();
        foreach ($haystack as $needle) {
            if ($needle !== "")
                $newAr[] = $needle;
        }
        return $newAr;
    }

    function calculateMatchingPathComponentCount($resourcePathComponents, $destinationPathComponents) {
        $lesserCount = min(count($resourcePathComponents), count($destinationPathComponents));

        for ($matchIndex = 0; $matchIndex < $lesserCount; ++$matchIndex) {
            if ($resourcePathComponents[$matchIndex] != $destinationPathComponents[$matchIndex])
                return $matchIndex;
        }

        return -1;
    }

    function calculateRelativeResourcePath($fullResourcePath, $destinationPath) {
        $fullResourcePathComponents = removeEmptyValuesFromArray(explode("/", $fullResourcePath));
        $destinationPathComponents = removeEmptyValuesFromArray(explode("/", $destinationPath));
        $matchingComponentCount = calculateMatchingPathComponentCount($fullResourcePathComponents, $destinationPathComponents);

        $newRelativePathComponents = array_slice($fullResourcePathComponents, $matchingComponentCount);

        $depthDifference = count($destinationPathComponents) - $matchingComponentCount;

        for ($parentPathCount = 0; $parentPathCount < $depthDifference; ++$parentPathCount)
            array_unshift($newRelativePathComponents, "..");

        return join("/", $newRelativePathComponents);
    }

    function performTempThemeFix($destinationDir, $rootSourceDir, $frontendSourceDir) {
        $sourceDirs = array($rootSourceDir, $frontendSourceDir);

        if (substr($destinationDir, -1) != "/")
            $destinationDir .= "/";

        $themeFileName = "theme.css";
        $destinationPath = $destinationDir . $themeFileName;

        if (file_exists($destinationPath))
            return;

        foreach ($sourceDirs as $sourceDir) {
            if (substr($sourceDir, -1) != "/")
                $sourceDir .= "/";

            $sourceFile = $sourceDir . $themeFileName;

            if (!file_exists($sourceFile))
                continue;

            $outputFile = @fopen($destinationPath, "w");

            if ($outputFile === false)
                return;

            $sourceLines = file($sourceFile);

            foreach ($sourceLines as $sourceLine) {
                if (preg_match("/background: url\\(([\"'])([^\"']+)(\\1)\\)/", $sourceLine, $matches)) {
                    $oldRelativeResourcePath = $matches[2];
                    $fullResourcePath = "/" . normalizePath($sourceDir . $oldRelativeResourcePath);

                    $newRelativePath = calculateRelativeResourcePath($fullResourcePath, $destinationDir);

                    $newLine = preg_replace("/(\\s+background: url\\()([\"'])([^\"']+)(\\2)(\\).*)/",
                        "$1$2" . $newRelativePath . "$4$5", $sourceLine);
                } else
                    $newLine = $sourceLine;

                fwrite($outputFile, $newLine);
            }

            fclose($outputFile);

            @unlink($sourceFile);
        }
    }

    function getMonstaPageTitle($isHostEdition) {
        if (defined("MFTP_PAGE_TITLE") && $isHostEdition)
            return MFTP_PAGE_TITLE;

        return "Monsta FTP";
    }

    function addressIsIpV6($address) {
        return strpos($address, ":") !== false;
    }

    function escapeIpAddress($address) {
        return addressIsIpV6($address) ? "[$address]" : $address;
    }

    function validateAddressIsAllowedAccess($allowedAddresses, $remoteAddress) {
        if (!is_array($allowedAddresses))
            return true;

        if (addressIsIpV6($remoteAddress))
            return false;  // no support for this so failsafe

        $remoteAddressLong = ip2long($remoteAddress);

        foreach ($allowedAddresses as $allowedAddress) {
            $slashCount = substr_count($allowedAddress, "/");
            if ($slashCount > 1)
                return false; // invalid CIDR representation, failsafe to failure
            else if ($slashCount == 0) {
                $network = $allowedAddress;
                $mask = 32;
            } else {
                list ($network, $mask) = explode("/", $allowedAddress);
            }

            $networkLong = ip2long($network);
            $maskLong = ~((1 << (32 - $mask)) - 1);

            $remoteAddressNetworkLong = $remoteAddressLong & $maskLong;

            if ($remoteAddressNetworkLong == $networkLong)
                return true;
        }

        return false;
    }

