<?php

    // Before changing these variables please view the README at
    // http://redirect.monstaftp.com/readme
    // Further settings are available in "settings.json"

    // GENERAL VARIABLES //

    $configPathSettings = dirname(__FILE__) . "/settings.json";
    $configTimeZone = "UTC";
    $configTempDir = "";
    $configMaxFileSize = "1024M";
    $configChunkUploadSize = "default";
    $configMaxExecutionTimeSeconds = 1800;
    $configSSHAgentAuthEnabled = false;
    $configSSHKeyAuthEnabled = false;
    $configPageTitle = "Monsta FTP";

    $configMaxLoginFailures = 3;
    $configLoginFailuresResetTimeMinutes = 5;

    $configMftpActionLogPath = null;
    $configMftpActionLogFunction = null;

    $configLogToSyslog = false;
    $configMftpSyslogFacility = LOG_USER;

    $configLogToFile = false;
    $configMftpLogFilePath = null;
    $configMftpLogLevelThreshold = LOG_WARNING;
    $configDisableLatestVersionCheck = false;

    // DEFINE THE VARIABLES //

    define("APPLICATION_SETTINGS_PATH", $configPathSettings);
    define("MONSTA_TEMP_DIRECTORY", $configTempDir);
    define("SSH_AGENT_AUTH_ENABLED", $configSSHAgentAuthEnabled);
    define("SSH_KEY_AUTH_ENABLED", $configSSHKeyAuthEnabled);
    define("MFTP_PAGE_TITLE", $configPageTitle);
    define("MFTP_MAX_LOGIN_FAILURES", $configMaxLoginFailures);
    define("MFTP_LOGIN_FAILURES_RESET_TIME_MINUTES", $configLoginFailuresResetTimeMinutes);

    define("MFTP_ACTION_LOG_PATH", $configMftpActionLogPath);
    define("MFTP_ACTION_LOG_FUNCTION", $configMftpActionLogFunction);

    define("MFTP_LOG_TO_SYSLOG", $configLogToSyslog);
    define("MFTP_LOG_SYSLOG_FACILITY", $configMftpSyslogFacility);

    define("MFTP_LOG_TO_FILE", $configLogToFile);
    define("MFTP_LOG_FILE_PATH", $configMftpLogFilePath);
    define("MFTP_LOG_LEVEL_THRESHOLD", $configMftpLogLevelThreshold);

    define("MFTP_DISABLE_LATEST_VERSION_CHECK", $configDisableLatestVersionCheck);

    date_default_timezone_set($configTimeZone);

    $currentMemoryLimitFormatted = ini_get('memory_limit');
    $currentMemoryLimit = formattedSizeToBytes($currentMemoryLimitFormatted);

    $maxUploadSizeBytes = formattedSizeToBytes($configMaxFileSize);

    if ($currentMemoryLimit != -1) {
        if ($maxUploadSizeBytes > $currentMemoryLimit)
            ini_set('memory_limit', $configMaxFileSize);
    }

    define("MFTP_MAX_UPLOAD_SIZE", $maxUploadSizeBytes);

    define("MFTP_CHUNK_UPLOAD_SIZE", $configChunkUploadSize);

    ini_set('max_execution_time', $configMaxExecutionTimeSeconds);

    $proConfigurationPath = dirname(__FILE__) . "/../license/config_pro.php";

    if(file_exists($proConfigurationPath))
        require_once($proConfigurationPath);
    else {
        define("AUTHENTICATION_FILE_PATH", "");
        define("MONSTA_LICENSE_PATH", "");
    }

    define("MONSTA_UPLOAD_LOGGING", false);
