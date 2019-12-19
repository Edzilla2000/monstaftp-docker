<?php
    require_once(dirname(__FILE__) . "/../constants.php");

    /**
     * // by default IIS limits to 30MB uploads. Save a couple MB for overhead.
     */
    define('WIN_DEFAULT_FILE_UPLOAD_SIZE_MB', 28);

    /**
     * divide memory limit by this amount to get size of chunks to send.
     * should allow for this * chunk size = max ram
     */
    define('CHUNK_MAX_SIMULTANEOUS_UPLOADS', 5);

    class SystemVars {
        public static function getChunkUploadSizeBytes() {
            if (defined("MFTP_CHUNK_UPLOAD_SIZE") && MFTP_CHUNK_UPLOAD_SIZE != "default") {
                return formattedSizeToBytes(MFTP_CHUNK_UPLOAD_SIZE);
            }

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                return WIN_DEFAULT_FILE_UPLOAD_SIZE_MB * 1024 * 1024;
            }

            $maxUploadBytes = self::getMaxFileUploadBytes();

            if ($maxUploadBytes <= 0) {
                $maxUploadBytes = 1024 * 1024 * 1024; // 1GB
            }

            return $maxUploadBytes / 10;  // Use a tenth of the maximum memory
        }

        public static function getMaxFileUploadBytes() {
            return formattedSizeToBytes(ini_get('memory_limit'));  // get the actual memory limit
        }

        public static function getSystemVarsArray() {
            return array(
                "chunkUploadSize" => self::getChunkUploadSizeBytes(),
                "maxFileUpload" => self::getMaxFileUploadBytes(),
                "version" => MONSTA_VERSION,
                "sshAgentAuthEnabled" => defined("SSH_AGENT_AUTH_ENABLED") && SSH_AGENT_AUTH_ENABLED === true,
                "sshKeyAuthEnabled" => defined("SSH_KEY_AUTH_ENABLED") && SSH_KEY_AUTH_ENABLED === true,
                "curlAvailable" => function_exists("curl_init")
            );
        }
    }