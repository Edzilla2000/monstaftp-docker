<?php

    require_once(dirname(__FILE__) . "/../constants.php");

    includeMonstaConfig();

    function _mftpLogPriorityToText($priority) {
        switch ($priority) {
            case LOG_EMERG:
                return "EMERG";
            case LOG_ALERT:
                return "ALERT";
            case LOG_CRIT:
                return "CRIT";
            case LOG_ERR:
                return "ERR";
            case LOG_WARNING:
                return "WARNING";
            case LOG_NOTICE:
                return "NOTICE";
            case LOG_INFO:
                return "INFO";
            case LOG_DEBUG:
                return "DEBUG";
            default:
                return "";
        }
    }

    function _mftpLogToFile($path, $priority, $message) {
        $handle = @fopen($path, "a");
        if ($handle === false)
            return;

        fprintf($handle, "[%s] (%s) %s\n", _mftpLogPriorityToText($priority), date("c"), $message);

        fclose($handle);
    }

    function _mftpLogToSyslog($priority, $message) {
        $facility = defined("MFTP_LOG_SYSLOG_FACILITY") ? MFTP_LOG_SYSLOG_FACILITY : LOG_USER;

        if(!@openlog("MONSTAFTP", LOG_ODELAY, $facility))
            return;

        syslog($priority, $message);

        closelog();
    }

    function _mftpShouldLogToFile() {
        return defined("MFTP_LOG_TO_FILE") && MFTP_LOG_TO_FILE
        && defined("MFTP_LOG_FILE_PATH") && !is_null(MFTP_LOG_FILE_PATH)
        && defined("MFTP_LOG_LEVEL_THRESHOLD");
    }

    function _mftpShouldLogToSyslog() {
        return defined("MFTP_LOG_TO_SYSLOG") && MFTP_LOG_TO_SYSLOG;
    }

    function mftpLog($priority, $message) {
        if (_mftpShouldLogToFile() && $priority <= MFTP_LOG_LEVEL_THRESHOLD)
            _mftpLogToFile(MFTP_LOG_FILE_PATH, $priority, $message);

        if(_mftpShouldLogToSyslog())
            _mftpLogToSyslog($priority, $message);
    }