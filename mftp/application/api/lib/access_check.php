<?php
    require_once(dirname(__FILE__) . "/../constants.php");
    includeMonstaConfig();
    require_once(dirname(__FILE__) . '/../system/ApplicationSettings.php');

    $applicationSettings = new ApplicationSettings(APPLICATION_SETTINGS_PATH);

    $license = readDefaultMonstaLicense();

    if(!is_null($license) && $license->isLicensed()) {
        if (!validateAddressIsAllowedAccess($applicationSettings->getAllowedClientAddresses(), $_SERVER['REMOTE_ADDR'])) {
            header('HTTP/1.0 403 Forbidden');
            die($applicationSettings->getDisallowedClientMessage() == null ? "" : $applicationSettings->getDisallowedClientMessage());
        }
    }
