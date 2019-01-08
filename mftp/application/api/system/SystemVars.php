<?php
    require_once(dirname(__FILE__) . "/../constants.php");

    class SystemVars {
        public static function getMaxFileUploadBytes() {
            return formattedSizeToBytes(ini_get('memory_limit'));  // get the actual memory limit
        }

        public static function getSystemVarsArray() {
            return array(
                "maxFileUpload" => self::getMaxFileUploadBytes(),
                "version" => MONSTA_VERSION,
                "sshAgentAuthEnabled" => defined("SSH_AGENT_AUTH_ENABLED") && SSH_AGENT_AUTH_ENABLED === true,
                "sshKeyAuthEnabled" => defined("SSH_KEY_AUTH_ENABLED") && SSH_KEY_AUTH_ENABLED === true,
                "curlAvailable" => function_exists("curl_init")
            );
        }
    }