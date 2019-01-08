<?php

    function isMonstaPostEntry($requestMethod, $postData) {
        if ($requestMethod != 'POST')
            return false;

        return array_key_exists('MFTP_POST', $postData) && $postData['MFTP_POST'] == 'true';
    }

    function upperCaseToCamelCase($varName) {
        $fullVar = "";

        foreach (explode("_", $varName) as $partialVarName) {
            $fullVar .= ucfirst(strtolower($partialVarName));
        }

        return lcfirst($fullVar);
    }

    function convertStringToBoolean($val) {
        if ($val !== "true" && $val !== "false")
            return null;

        return $val === "true";
    }

    function extractSettingVars($postData, $rawKeys, $intKeys, $boolKeys) {
        $loginVars = array();
        for ($varType = 0; $varType <= 2; ++$varType) {
            switch ($varType) {
                case 0:
                    $sourceKeys = $rawKeys;
                    break;
                case 1:
                    $sourceKeys = $intKeys;
                    break;
                case 2:
                default:
                    $sourceKeys = $boolKeys;
            }

            if (is_null($sourceKeys))
                continue;

            foreach ($sourceKeys as $partialPostKey) {
                $postKey = "MFTP_" . $partialPostKey;

                if (array_key_exists($postKey, $postData)) {
                    $varName = upperCaseToCamelCase($partialPostKey);

                    $var = $postData[$postKey];

                    switch ($varType) {
                        case 0:
                            break;
                        case 1:
                            $var = intval($var);

                            if ($var == 0)
                                continue;
                            /*  0 is returned for invalid numbers, or "0", and since it's only used for port at the
                                moment it's invalid if 0 anyway, so skip */
                            break;
                        case 2:
                        default:
                            $var = convertStringToBoolean($var);
                            if (is_null($var))
                                continue;
                    }

                    $loginVars[$varName] = $var;
                }
            }
        }

        return $loginVars;
    }

    function extractMonstaFtpPostEntryVars($postData) {
        return extractSettingVars($postData,
            array("HOST", "USERNAME", "PASSWORD", "INITIAL_DIRECTORY"),
            array("PORT"),
            array("PASSIVE", "SSL")
        );
    }

    function extractMonstaSftpPostEntryVars($postData) {
        return extractSettingVars($postData,
            array("HOST",
                "REMOTE_USERNAME",
                "PASSWORD",
                "INITIAL_DIRECTORY",
                "AUTHENTICATION_MODE_NAME",
                "PUBLIC_KEY_FILE_PATH",
                "PRIVATE_KEY_FILE_PATH"
            ),
            array("PORT"),
            null
        );
    }

    function extractMonstaPostEntryVars($postData) {
        // different from PHP built in extract as it does not extract to variables but to an array
        $postedVars = array();

        if (array_key_exists('MFTP_POST_LOGOUT_URL', $postData) || array_key_exists('MFTP_LOGIN_FAILURE_REDIRECT', $postData)) {
            $postedVars["settings"] = array();

            if (array_key_exists('MFTP_POST_LOGOUT_URL', $postData))
                $postedVars["settings"]['postLogoutUrl'] = $postData['MFTP_POST_LOGOUT_URL'];

            if (array_key_exists('MFTP_LOGIN_FAILURE_REDIRECT', $postData))
                $postedVars["settings"]['loginFailureRedirect'] = $postData['MFTP_LOGIN_FAILURE_REDIRECT'];
        }

        if (!array_key_exists('MFTP_CONNECTION_TYPE', $postData))
            return $postedVars;

        $connectionType = $postData["MFTP_CONNECTION_TYPE"];

        $postedVars["type"] = $connectionType;

        if ($connectionType == "ftp")
            $settingsVars = extractMonstaFtpPostEntryVars($postData);
        else if ($connectionType == "sftp")
            $settingsVars = extractMonstaSftpPostEntryVars($postData);
        else
            $settingsVars = array();

        $postedVars[$connectionType] = $settingsVars;

        return $postedVars;
    }

