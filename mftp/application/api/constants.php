<?php
    /**
     * This is REALLY for constants, not configuration, not intended to be edited per install
     */

    /* TODO: make this into abstract class so that we might be able to easily serialize this to JSON to make the
    "constants" available to clients */

    define("MONSTA_VERSION", trim(file_get_contents(dirname(__FILE__) . '/VERSION')));
    define("FTP_DEFAULT_PORT", 21);
    define("FTP_SYS_TYPE_UNIX", 0);
    define("FTP_SYS_TYPE_WINDOWS", 1);
    define("SFTP_DEFAULT_PORT", 22);
    define("PERMISSION_BIT_MASK", 0x1FF); // last 9 bits from mode

    define("MOCK_DEFAULT_USERNAME", "user");
    define("MOCK_DEFAULT_PASSWORD", "password");

    define("PREFERRED_CIPHER_METHODS", "aes-256-cbc|bf-cbc");

    define("PUBKEY_PATH", dirname(__FILE__) . '/resources/monsta_public.pem');

    define("XHR_DEFAULT_TIMEOUT_SECONDS", 30);

    define("MONSTA_DEBUG", file_exists(dirname(__FILE__) . "/DEBUG"));

    define("MONSTA_PRODUCT_EDITION_BUSINESS", 0);
    define("MONSTA_PRODUCT_EDITION_HOST", 1);

    define("USE_MFTP_LIBRARY", 1);
    define("USE_SECLIB_LIBRARY", 1);

    define("MFTP_UNIX_LIST_FORMAT", '/^([A-Za-z+-]{10})\s+(\d+)\s+([^ ]+)\s+([^ ]+)\s+(\d+)\s+(.{12}) (.*)$/');
    define("MFTP_WIN_LIST_FORMAT", '/^(\d+)-(\d+)-(\d+)\s+(\d+):(\d+)(A|P)M(.{22})(.*)$/');

    if (!defined("FTP_ASCII"))
        define("FTP_ASCII", 1);

    if (!defined("FTP_BINARY"))
        define("FTP_BINARY", 2);

    define("MFTP_EXTRACT_UPLOAD_TIME_LIMIT_SECONDS", 50);
    define("MFTP_UPLOAD_PROGRESS_CALLBACK_TIME_SECONDS", 30);

    define("MFTP_SESSION_KEY_PREFIX", "MFTP");
    define("MFTP_LATEST_VERSION_ARCHIVE_PATH", "https://www.monstaftp.com/downloads/mftp.php");
    define("MFTP_LATEST_VERSION_ARCHIVE_TEMP_NAME", "mftp-install.zip");
    define("MFTP_LATEST_VERSION_EXTRACT_DIR_NAME", "mftp-upgrade");
    define("MFTP_TMP_UPLOAD_TIMEOUT_SECONDS", 86400);
    define("MFTP_CAPABILITY_CACHE_TIMEOUT_SECONDS", 86400);

    function formattedSizeToBytes($formattedSize) {
        $formattedSize = trim($formattedSize);
        $unit = strtolower(substr($formattedSize, -1));
        $multiplier = 1;

        switch ($unit) {
            case 'g':
                $multiplier *= 1024;
            case 'm':
                $multiplier *= 1024;
            case 'k':
                $multiplier *= 1024;
        }

        $size = $multiplier == 1 ? (int)$formattedSize : (int)substr($formattedSize, 0, strlen($formattedSize) - 1);

        return $size * $multiplier;
    }

    function includeMonstaConfig() {
        $configDir = dirname(__FILE__) . "/../../settings/";

        if (!defined("MONSTA_CONFIG_DIR_PATH"))
            define("MONSTA_CONFIG_DIR_PATH", $configDir);

        if (file_exists($configDir . "config_debug.php"))
            require_once($configDir . "config_debug.php");
        else
            require_once($configDir . "config.php");
    }

    $vendorPath = dirname(__FILE__) . "/vendor/";

    $vendorLibDirectories = array("phpseclib1.0.11");

    $joinedVendorDirs = join(PATH_SEPARATOR, array_map(function ($libDir) {
        return dirname(__FILE__) . "/vendor/" . $libDir;
    }, $vendorLibDirectories));

    set_include_path(get_include_path() . PATH_SEPARATOR . $joinedVendorDirs);

    $mftpAffiliateDefinitionPath = dirname(__FILE__) . "/affiliate.php";

    if (file_exists($mftpAffiliateDefinitionPath))
        require_once($mftpAffiliateDefinitionPath);
