<?php

    require_once(dirname(__FILE__) . "/../lib/LocalizableException.php");
    require_once(dirname(__FILE__) . "/../lib/nicejson.php");

    class ApplicationSettings {
        /**
         * @var array|mixed
         */
        private $settings;

        /**
         * @var string
         */
        private $settingsPath;

        /**
         * @var bool
         */
        private $settingsReadFailed;

        /**
         * @var string
         */
        private $settingsReadError;

        private static $KEY_SHOW_DOT_FILES = "showDotFiles";
        private static $KEY_LANGUAGE = "language";
        private static $KEY_EDIT_NEW_FILES_IMMEDIATELY = "editNewFilesImmediately";
        private static $KEY_EDITABLE_FILE_EXTENSIONS = "editableFileExtensions";
        private static $KEY_CONNECTION_RESTRICTIONS = "connectionRestrictions";
        private static $KEY_HIDE_PRO_UPGRADE_MESSAGES = "hideProUpgradeMessages";
        private static $KEY_DISABLE_MASTER_LOGIN = "disableMasterLogin";
        private static $KEY_ENCODE_EDITOR_SAVES = "encodeEditorSaves";
        private static $KEY_DISABLE_CHMOD = "disableChmod";
        private static $KEY_DISABLE_FILE_VIEW = "disableFileView";
        private static $KEY_DISABLE_FILE_EDIT = "disableFileEdit";
        private static $KEY_DISABLE_ADD_ONS_BUTTON = "disableAddOnsButton";
        private static $KEY_DISABLE_HELP_BUTTON = "disableHelpButton";
        private static $KEY_HELP_URL = "helpUrl";
        private static $KEY_XHR_TIMEOUT_SECONDS = "xhrTimeoutSeconds";
        private static $KEY_POST_LOGOUT_URL = "postLogoutUrl";
        private static $KEY_DISABLE_REMOTE_SERVER_ADDRESS_DISPLAY = "disableRemoteServerAddressDisplay";
        private static $KEY_DISABLE_CHANGE_SERVER_BUTTON = "disableChangeServerButton";
        private static $KEY_FOOTER_ITEM_DISPLAY = "footerItemDisplay";
        private static $KEY_SIDEBAR_ITEM_DISPLAY = "sidebarItemDisplay";
        private static $KEY_CONTEXT_MENU_ITEM_DISPLAY = "contextMenuItemDisplay";
        private static $KEY_FILE_BROWSER_COLUMN_DISPLAY = "fileBrowserColumnDisplay";
        private static $KEY_HEADER_ITEM_DISPLAY = "headerItemDisplay";
        private static $KEY_HIDE_HISTORY_BAR = "hideHistoryBar";
        private static $KEY_ENABLE_RESET_PASSWORD = "enableResetPassword";
        private static $KEY_ENABLE_FORGOT_PASSWORD = "enableForgotPassword";
        private static $KEY_DISABLE_LOGIN_LINK_BUTTON = "disableLoginLinkButton";
        private static $KEY_LOGIN_ITEM_DISPLAY = "loginItemDisplay";
        private static $KEY_EDITOR_LINE_SEPARATOR = "editorLineSeparator";
        private static $KEY_ALLOWED_CLIENT_ADDRESSES = "allowedClientAddresses";
        private static $KEY_DISALLOWED_CLIENT_MESSAGE = "disallowedClientMessage";
        private static $KEY_LOGIN_FAILURE_REDIRECT = "loginFailureRedirect";
        private static $KEY_DISABLE_LOGIN_FORM = "disableLoginForm";
        private static $KEY_RESUME_SESSION_INFO_DISPLAY_SECONDS = "resumeSessionInfoDisplaySeconds";
        private static $KEY_DISABLE_UPLOAD_OVERWRITE_CONFIRMATION = "disableUploadOverwriteConfirmation";
        private static $KEY_DISABLE_DELETE_CONFIRMATION = "disableDeleteConfirmation";
        private static $KEY_EDITOR_OPTIONS = "editorOptions";
        private static $KEY_SKIP_MAC_OS_SPECIAL_FILES = "skipMacOsSpecialFiles";

        private static $DEFAULT_LANGUAGE = "en_us";
        private static $DEFAULT_EDITABLE_FILE_EXTENSIONS =
            "txt,htm,html,php,asp,aspx,js,css,xhtml,cfm,pl,py,c,cpp,rb,java,xml,json";

        private function getValidKeys() {
            // this is kind of like an instance var getter thing so it's up here
            return array(
                self::$KEY_SHOW_DOT_FILES,
                self::$KEY_LANGUAGE,
                self::$KEY_EDIT_NEW_FILES_IMMEDIATELY,
                self::$KEY_EDITABLE_FILE_EXTENSIONS,
                self::$KEY_CONNECTION_RESTRICTIONS,
                self::$KEY_HIDE_PRO_UPGRADE_MESSAGES,
                self::$KEY_DISABLE_MASTER_LOGIN,
                self::$KEY_ENCODE_EDITOR_SAVES,
                self::$KEY_DISABLE_CHMOD,
                self::$KEY_DISABLE_FILE_VIEW,
                self::$KEY_DISABLE_FILE_EDIT,
                self::$KEY_DISABLE_ADD_ONS_BUTTON,
                self::$KEY_DISABLE_HELP_BUTTON,
                self::$KEY_HELP_URL,
                self::$KEY_XHR_TIMEOUT_SECONDS,
                self::$KEY_POST_LOGOUT_URL,
                self::$KEY_DISABLE_REMOTE_SERVER_ADDRESS_DISPLAY,
                self::$KEY_DISABLE_CHANGE_SERVER_BUTTON,
                self::$KEY_FOOTER_ITEM_DISPLAY,
                self::$KEY_SIDEBAR_ITEM_DISPLAY,
                self::$KEY_CONTEXT_MENU_ITEM_DISPLAY,
                self::$KEY_FILE_BROWSER_COLUMN_DISPLAY,
                self::$KEY_HEADER_ITEM_DISPLAY,
                self::$KEY_HIDE_HISTORY_BAR,
                self::$KEY_ENABLE_RESET_PASSWORD,
                self::$KEY_ENABLE_FORGOT_PASSWORD,
                self::$KEY_DISABLE_LOGIN_LINK_BUTTON,
                self::$KEY_LOGIN_ITEM_DISPLAY,
                self::$KEY_EDITOR_LINE_SEPARATOR,
                self::$KEY_ALLOWED_CLIENT_ADDRESSES,
                self::$KEY_DISALLOWED_CLIENT_MESSAGE,
                self::$KEY_LOGIN_FAILURE_REDIRECT,
                self::$KEY_DISABLE_LOGIN_FORM,
                self::$KEY_RESUME_SESSION_INFO_DISPLAY_SECONDS,
                self::$KEY_DISABLE_UPLOAD_OVERWRITE_CONFIRMATION,
                self::$KEY_DISABLE_DELETE_CONFIRMATION,
                self::$KEY_EDITOR_OPTIONS,
                self::$KEY_SKIP_MAC_OS_SPECIAL_FILES
            );
        }

        private function getDefaults() {
            return array(
                self::$KEY_SHOW_DOT_FILES => true,
                self::$KEY_LANGUAGE => self::$DEFAULT_LANGUAGE,
                self::$KEY_EDIT_NEW_FILES_IMMEDIATELY => true,
                self::$KEY_EDITABLE_FILE_EXTENSIONS => self::$DEFAULT_EDITABLE_FILE_EXTENSIONS,
                self::$KEY_CONNECTION_RESTRICTIONS => null,
                self::$KEY_HIDE_PRO_UPGRADE_MESSAGES => false,
                self::$KEY_DISABLE_MASTER_LOGIN => false,
                self::$KEY_ENCODE_EDITOR_SAVES => false,
                self::$KEY_DISABLE_CHMOD => false,
                self::$KEY_DISABLE_FILE_VIEW => false,
                self::$KEY_DISABLE_FILE_EDIT => false,
                self::$KEY_DISABLE_ADD_ONS_BUTTON => false,
                self::$KEY_DISABLE_HELP_BUTTON => false,
                self::$KEY_HELP_URL => null,
                self::$KEY_XHR_TIMEOUT_SECONDS => XHR_DEFAULT_TIMEOUT_SECONDS,
                self::$KEY_POST_LOGOUT_URL => null,
                self::$KEY_DISABLE_REMOTE_SERVER_ADDRESS_DISPLAY => false,
                self::$KEY_DISABLE_CHANGE_SERVER_BUTTON => false,
                self::$KEY_FOOTER_ITEM_DISPLAY => new ArrayObject(),
                self::$KEY_SIDEBAR_ITEM_DISPLAY => new ArrayObject(),
                self::$KEY_CONTEXT_MENU_ITEM_DISPLAY => new ArrayObject(),
                self::$KEY_FILE_BROWSER_COLUMN_DISPLAY => new ArrayObject(),
                self::$KEY_HEADER_ITEM_DISPLAY => new ArrayObject(),
                self::$KEY_HIDE_HISTORY_BAR => false,
                self::$KEY_ENABLE_RESET_PASSWORD => false,
                self::$KEY_ENABLE_FORGOT_PASSWORD => false,
                self::$KEY_DISABLE_LOGIN_LINK_BUTTON => false,
                self::$KEY_LOGIN_ITEM_DISPLAY => new ArrayObject(),
                self::$KEY_EDITOR_LINE_SEPARATOR => null,
                self::$KEY_ALLOWED_CLIENT_ADDRESSES => null,
                self::$KEY_DISALLOWED_CLIENT_MESSAGE => "",
                self::$KEY_LOGIN_FAILURE_REDIRECT => null,
                self::$KEY_DISABLE_LOGIN_FORM => false,
                self::$KEY_RESUME_SESSION_INFO_DISPLAY_SECONDS => 3,
                self::$KEY_DISABLE_UPLOAD_OVERWRITE_CONFIRMATION => false,
                self::$KEY_DISABLE_DELETE_CONFIRMATION => false,
                self::$KEY_EDITOR_OPTIONS => array('wordWrap' => 'on', 'minimap'=> array('enabled'=> false)),
                self::$KEY_SKIP_MAC_OS_SPECIAL_FILES => true
            );
        }

        private function getFrontendWritableKeys() {
            // since there's no auth only allow setting of safe keys
            return array(
                self::$KEY_SHOW_DOT_FILES,
                self::$KEY_LANGUAGE,
                self::$KEY_EDIT_NEW_FILES_IMMEDIATELY
            );
        }

        public function __construct($settingsPath) {
            $this->settingsPath = $settingsPath;
            $this->settingsReadFailed = false;

            if (!file_exists($settingsPath))
                $this->settings = array();
            else {
                $settings = array();

                $settingsContents = @file_get_contents($settingsPath);

                if ($settingsContents === false) {
                    $this->settingsReadFailed = true;
                    $this->settingsReadError = "Couldn't read data from settings file.";
                } else {
                    $settings = json_decode($settingsContents, true);

                    if ($settings == null || !is_array($settings)) {
                        $settings = array();
                        $this->settingsReadFailed = true;
                        $this->settingsReadError = "Couldn't decode JSON from settings file. JSON error was: " . json_last_error_msg();
                    }
                }

                $this->settings = $settings;
            }
        }

        /**
         * @return boolean
         */
        public function isSettingsReadFailed() {
            return $this->settingsReadFailed;
        }

        /**
         * @return string
         */
        public function getSettingsReadError() {
            return $this->settingsReadError;
        }

        public function save() {
            if (!$this->settingsWritable())
                throw new LocalizableException("Could not write settings JSON at " . $this->settingsPath,
                    LocalizableExceptionDefinition::$SETTINGS_WRITE_ERROR, array("path" => $this->settingsPath));

            file_put_contents($this->settingsPath, json_format($this->settings));
        }

        private function settingsWritable() {
            if (file_exists($this->settingsPath))
                return is_writable($this->settingsPath);

            return is_writable(dirname($this->settingsPath));
        }

        private function getDefaultValue($key) {
            $defaults = $this->getDefaults();

            if (!array_key_exists($key, $defaults))
                return null;

            return $defaults[$key];
        }

        private function getSetKey($key) {
            if (isset($this->settings[$key]))
                return $this->settings[$key];

            return $this->getDefaultValue($key);
        }

        private function setBool($key, $value) {
            if (!is_bool($value))
                throw new InvalidArgumentException("$key requires a boolean argument, got: \"$value\"");

            $this->settings[$key] = $value;
        }

        private function blankArray($inputArray, $skipKeys) {
            $blankedArray = array();

            foreach ($inputArray as $key => $value) {
                if ($key == "types" || $key == "host" && is_array($value))
                    $blankedArray[$key] = $value;
                else if (is_array($value))
                    $blankedArray[$key] = $this->blankArray($value, $skipKeys);
                else if (array_search($key, $skipKeys) !== false)
                    $blankedArray[$key] = $value;
                else
                    $blankedArray[$key] = true;
            }

            return $blankedArray;
        }

        public function getSettingsArray() {
            $settings = array();

            foreach ($this->getSettingKeyGetterMap() as $key => $getterName) {
                $settings[$key] = $this->$getterName();
            }

            return $settings;
        }

        public function setFromArray($settingsArray) {
            $safeKeys = $this->getFrontendWritableKeys();

            foreach ($this->getSettingKeySetterMap() as $key => $setterName) {
                if (!in_array($key, $safeKeys))
                    continue;

                if (isset($settingsArray[$key]))
                    $this->$setterName($settingsArray[$key]);
            }
        }

        private function getSetOrGet($isSet, $key) {
            $prefix = $isSet ? 'set' : 'get';
            return $prefix . ucfirst($key);
        }

        private function getAccessorLookupMap($isSet) {
            $validKeys = $this->getValidKeys();

            $settingKeyMap = array();

            foreach ($validKeys as $key) {
                $settingKeyMap[$key] = $this->getSetOrGet($isSet, $key);
            }

            return $settingKeyMap;
        }

        /* public setting setter/getters below */

        private function getSettingKeySetterMap() {
            return $this->getAccessorLookupMap(true);
        }

        private function getSettingKeyGetterMap() {
            return $this->getAccessorLookupMap(false);
        }

        public function getShowDotFiles() {
            return $this->getSetKey(self::$KEY_SHOW_DOT_FILES);
        }

        public function setShowDotFiles($showDotFiles) {
            $this->setBool(self::$KEY_SHOW_DOT_FILES, $showDotFiles);
        }

        public function getLanguage() {
            return $this->getSetKey(self::$KEY_LANGUAGE);
        }

        public function setLanguage($language) {
            $this->settings[self::$KEY_LANGUAGE] = $language;
        }

        public function getEditNewFilesImmediately() {
            return $this->getSetKey(self::$KEY_EDIT_NEW_FILES_IMMEDIATELY);
        }

        public function setEditNewFilesImmediately($editNewFilesImmediately) {
            $this->setBool(self::$KEY_EDIT_NEW_FILES_IMMEDIATELY, $editNewFilesImmediately);
        }

        public function getEditableFileExtensions() {
            return $this->getSetKey(self::$KEY_EDITABLE_FILE_EXTENSIONS);
        }

        public function setEditableFileExtensions($editableFileExtensions) {
            $this->settings[self::$KEY_EDITABLE_FILE_EXTENSIONS] = $editableFileExtensions;
        }

        public function getConnectionRestrictions() {
            $restrictions = $this->getSetKey(self::$KEY_CONNECTION_RESTRICTIONS);

            if (is_array($restrictions)) {
                $license = readDefaultMonstaLicense();
                if (is_null($license) || !$license->isLicensed()) {
                    if (array_key_exists("types", $restrictions))
                        return array("types" => $restrictions["types"]);

                    return $this->getDefaultValue(self::$KEY_CONNECTION_RESTRICTIONS);
                }

                $restrictions = $this->blankArray($restrictions, array("authenticationModeName", "initialDirectory"));
            }

            return $restrictions;
        }

        public function setConnectionRestrictions($connectionRestrictions) {
            // Not writable because they come in blank todo: make it writable? (for authorised users)
            // $this->settings[self::$KEY_CONNECTION_RESTRICTIONS] = $connectionRestrictions;
        }

        public function getUnblankedConnectionRestrictions() {
            return $this->getSetKey(self::$KEY_CONNECTION_RESTRICTIONS);
        }

        public function getHideProUpgradeMessages() {
            return $this->getSetKey(self::$KEY_HIDE_PRO_UPGRADE_MESSAGES);
        }

        public function setHideProUpgradeMessages($hideProUpgradeMessages) {
            $this->setBool(self::$KEY_HIDE_PRO_UPGRADE_MESSAGES, $hideProUpgradeMessages);
        }

        public function getDisableMasterLogin() {
            return $this->getSetKey(self::$KEY_DISABLE_MASTER_LOGIN);
        }

        public function setDisableMasterLogin($disableMasterLogin) {
            $this->setBool(self::$KEY_DISABLE_MASTER_LOGIN, $disableMasterLogin);
        }

        public function getEncodeEditorSaves() {
            return $this->getSetKey(self::$KEY_ENCODE_EDITOR_SAVES);
        }

        public function setEncodeEditorSaves($encodeEditorSaves) {
            $this->setBool(self::$KEY_ENCODE_EDITOR_SAVES, $encodeEditorSaves);
        }

        public function getDisableChmod() {
            return $this->getSetKey(self::$KEY_DISABLE_CHMOD);
        }

        public function setDisableChmod($disableChmod) {
            $this->setBool(self::$KEY_DISABLE_CHMOD, $disableChmod);
        }

        public function getDisableFileView() {
            return $this->getSetKey(self::$KEY_DISABLE_FILE_VIEW);
        }

        public function setDisableFileView($disableFileView) {
            $this->setBool(self::$KEY_DISABLE_FILE_VIEW, $disableFileView);
        }

        public function getDisableFileEdit() {
            return $this->getSetKey(self::$KEY_DISABLE_FILE_EDIT);
        }

        public function setDisableFileEdit($disableFileEdit) {
            $this->setBool(self::$KEY_DISABLE_FILE_VIEW, $disableFileEdit);
        }

        public function getEditorLineSeparator() {
            return $this->getSetKey(self::$KEY_EDITOR_LINE_SEPARATOR);
        }

        public function setEditorLineSeparator($lineSeparator) {
            $this->settings[self::$KEY_EDITOR_LINE_SEPARATOR] = $lineSeparator;
        }

        public function getDisableAddOnsButton() {
            return $this->getSetKey(self::$KEY_DISABLE_ADD_ONS_BUTTON);
        }

        public function setDisableAddOnsButton($disableAddOnsButton) {
            $this->setBool(self::$KEY_DISABLE_ADD_ONS_BUTTON, $disableAddOnsButton);
        }

        public function getDisableHelpButton() {
            return $this->getSetKey(self::$KEY_DISABLE_HELP_BUTTON);
        }

        public function setDisableHelpButton($disableHelpButton) {
            $this->setBool(self::$KEY_DISABLE_HELP_BUTTON, $disableHelpButton);
        }

        public function getHelpUrl() {
            return $this->getSetKey(self::$KEY_HELP_URL);
        }

        public function setHelpUrl($helpUrl) {
            $this->setBool(self::$KEY_HELP_URL, $helpUrl);
        }

        public function getXhrTimeoutSeconds() {
            return $this->getSetKey(self::$KEY_XHR_TIMEOUT_SECONDS);
        }

        public function setXhrTimeoutSeconds($xhrTimeoutSeconds) {
            $this->settings[self::$KEY_XHR_TIMEOUT_SECONDS] = intval($xhrTimeoutSeconds);
        }

        public function getPostLogoutUrl() {
            return $this->getSetKey(self::$KEY_POST_LOGOUT_URL);
        }

        public function setPostLogoutUrl($postLogoutUrl) {
            $this->settings[self::$KEY_POST_LOGOUT_URL] = $postLogoutUrl;
        }

        public function getDisableRemoteServerAddressDisplay() {
            return $this->getSetKey(self::$KEY_DISABLE_REMOTE_SERVER_ADDRESS_DISPLAY);
        }

        public function setDisableRemoteServerAddressDisplay($disableRemoteServerAddressDisplay) {
            $this->setBool(self::$KEY_DISABLE_REMOTE_SERVER_ADDRESS_DISPLAY, $disableRemoteServerAddressDisplay);
        }

        public function getDisableChangeServerButton() {
            return $this->getSetKey(self::$KEY_DISABLE_CHANGE_SERVER_BUTTON);
        }

        public function setDisableChangeServerButton($disableChangeServerButton) {
            $this->setBool(self::$KEY_DISABLE_CHANGE_SERVER_BUTTON, $disableChangeServerButton);
        }

        public function getFooterItemDisplay() {
            return $this->getSetKey(self::$KEY_FOOTER_ITEM_DISPLAY);
        }

        public function setFooterItemDisplay($footerItemDisplay) {
            $this->settings[self::$KEY_FOOTER_ITEM_DISPLAY] = $footerItemDisplay;
        }

        public function getSidebarItemDisplay() {
            return $this->getSetKey(self::$KEY_SIDEBAR_ITEM_DISPLAY);
        }

        public function setSidebarItemDisplay($sidebarItemDisplay) {
            $this->settings[self::$KEY_SIDEBAR_ITEM_DISPLAY] = $sidebarItemDisplay;
        }

        public function getContextMenuItemDisplay() {
            return $this->getSetKey(self::$KEY_CONTEXT_MENU_ITEM_DISPLAY);
        }

        public function setContextMenuItemDisplay($contextMenuItemDisplay) {
            $this->settings[self::$KEY_CONTEXT_MENU_ITEM_DISPLAY] = $contextMenuItemDisplay;
        }

        public function getFileBrowserColumnDisplay() {
            return $this->getSetKey(self::$KEY_FILE_BROWSER_COLUMN_DISPLAY);
        }

        public function setFileBrowserColumnDisplay($fileBrowserColumnDisplay) {
            $this->settings[self::$KEY_CONTEXT_MENU_ITEM_DISPLAY] = $fileBrowserColumnDisplay;
        }

        public function getHeaderItemDisplay() {
            return $this->getSetKey(self::$KEY_HEADER_ITEM_DISPLAY);
        }

        public function setHeaderItemDisplay($headerItemDisplay) {
            $this->settings[self::$KEY_HEADER_ITEM_DISPLAY] = $headerItemDisplay;
        }

        public function getHideHistoryBar() {
            return $this->getSetKey(self::$KEY_HIDE_HISTORY_BAR);
        }

        public function setHideHistoryBar($hideHistoryBar) {
            $this->setBool(self::$KEY_HIDE_HISTORY_BAR, $hideHistoryBar);
        }

        public function getEnableResetPassword() {
            return $this->getSetKey(self::$KEY_ENABLE_RESET_PASSWORD);
        }

        public function setEnableResetPassword($disableFileView) {
            $this->setBool(self::$KEY_ENABLE_RESET_PASSWORD, $disableFileView);
        }

        public function getEnableForgotPassword() {
            return $this->getSetKey(self::$KEY_ENABLE_FORGOT_PASSWORD);
        }

        public function setEnableForgotPassword($disableFileView) {
            $this->setBool(self::$KEY_ENABLE_FORGOT_PASSWORD, $disableFileView);
        }

        public function getDisableLoginLinkButton() {
            return $this->getSetKey(self::$KEY_DISABLE_LOGIN_LINK_BUTTON);
        }

        public function setDisableLoginLink($disableLoginLinkButton) {
            $this->setBool(self::$KEY_DISABLE_LOGIN_LINK_BUTTON, $disableLoginLinkButton);
        }

        public function getLoginItemDisplay() {
            return $this->getSetKey(self::$KEY_LOGIN_ITEM_DISPLAY);
        }

        public function setLoginItemDisplay($loginItemDisplay) {
            $this->settings[self::$KEY_LOGIN_ITEM_DISPLAY] = $loginItemDisplay;
        }
        
        public function getAllowedClientAddresses() {
            return $this->getSetKey(self::$KEY_ALLOWED_CLIENT_ADDRESSES);
        }
        
        public function setAllowedClientAddresses($allowedClientAddresses) {
            $this->settings[self::$KEY_ALLOWED_CLIENT_ADDRESSES] = $allowedClientAddresses;
        }

        public function getDisallowedClientMessage() {
            return $this->getSetKey(self::$KEY_DISALLOWED_CLIENT_MESSAGE);
        }

        public function setDisallowedClientMessage($disallowedClientMessage) {
            $this->settings[self::$KEY_DISALLOWED_CLIENT_MESSAGE] = $disallowedClientMessage;
        }
        
        public function getLoginFailureRedirect() {
            return $this->getSetKey(self::$KEY_LOGIN_FAILURE_REDIRECT);
        }

        public function setLoginFailureRedirect($loginFailureRedirect) {
            $this->settings[self::$KEY_LOGIN_FAILURE_REDIRECT]  = $loginFailureRedirect;
        }

        public function getDisableLoginForm() {
            return $this->getSetKey(self::$KEY_DISABLE_LOGIN_FORM);
        }

        public function setDisableLoginForm($disableLoginForm) {
            $this->setBool(self::$KEY_DISABLE_LOGIN_FORM, $disableLoginForm);
        }

        public function getResumeSessionInfoDisplaySeconds() {
            return $this->getSetKey(self::$KEY_RESUME_SESSION_INFO_DISPLAY_SECONDS);
        }

        public function setResumeSessionInfoDisplaySeconds($resumeSessionInfoDisplaySeconds) {
            $this->settings[self::$KEY_RESUME_SESSION_INFO_DISPLAY_SECONDS] = $resumeSessionInfoDisplaySeconds;
        }

        public function getDisableUploadOverwriteConfirmation() {
            return $this->getSetKey(self::$KEY_DISABLE_UPLOAD_OVERWRITE_CONFIRMATION);
        }

        public function setDisableUploadOverwriteConfirmation($disableUploadOverwriteConfirmation) {
            $this->setBool(self::$KEY_DISABLE_UPLOAD_OVERWRITE_CONFIRMATION, $disableUploadOverwriteConfirmation);
        }

        public function getDisableDeleteConfirmation() {
            return $this->getSetKey(self::$KEY_DISABLE_DELETE_CONFIRMATION);
        }

        public function setDisableDeleteConfirmation($disableDeleteConfirmation) {
            $this->setBool(self::$KEY_DISABLE_DELETE_CONFIRMATION, $disableDeleteConfirmation);
        }

        public function getEditorOptions() {
            return $this->getSetKey(self::$KEY_EDITOR_OPTIONS);
        }

        public function setEditorOptions($editorOptions) {
            $this->settings[self::$KEY_EDITOR_OPTIONS] = $editorOptions;
        }

        public function getSkipMacOsSpecialFiles() {
            return $this->getSetKey(self::$KEY_SKIP_MAC_OS_SPECIAL_FILES);
        }

        public function setSkipMacOsSpecialFiles($skipMacOsSpecialFiles) {
            $this->setBool(self::$KEY_SKIP_MAC_OS_SPECIAL_FILES, $skipMacOsSpecialFiles);
        }
    }