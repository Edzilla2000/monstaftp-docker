<?php

    abstract class LocalizableExceptionDefinition {
        public static $FETCH_IN_PROGRESS_ERROR = "FETCH_IN_PROGRESS_ERROR";
        public static $FETCH_FAILED_ERROR = "FETCH_FAILED_ERROR";
        public static $PRIVATE_KEY_LOAD_ERROR = "PRIVATE_KEY_LOAD_ERROR";
        public static $PUBLIC_KEY_LOAD_ERROR = "PUBLIC_KEY_LOAD_ERROR";
        public static $DECRYPT_ERROR = "DECRYPT_ERROR";
        public static $CONNECTION_FAILURE_ERROR = "CONNECTION_FAILURE_ERROR";
        public static $UNCONNECTED_DISCONNECT_ERROR = "UNCONNECTED_DISCONNECT_ERROR";
        public static $FILE_DOES_NOT_EXIST_ERROR = "FILE_DOES_NOT_EXIST_ERROR";
        public static $FILE_EXISTS_ERROR = "FILE_EXISTS_ERROR";
        public static $FILE_PERMISSION_ERROR = "FILE_PERMISSION_ERROR";
        public static $GENERAL_FILE_SOURCE_ERROR = "GENERAL_FILE_SOURCE_ERROR";
        public static $OPERATION_BEFORE_CONNECTION_ERROR = "OPERATION_BEFORE_CONNECTION_ERROR";
        public static $OPERATION_BEFORE_AUTHENTICATION_ERROR = "OPERATION_BEFORE_CONNECTION_ERROR";
        public static $AUTHENTICATION_BEFORE_CONNECTION_ERROR = "AUTHENTICATION_BEFORE_CONNECTION_ERROR";
        public static $PASSIVE_MODE_BEFORE_AUTHENTICATION_ERROR = "PASSIVE_MODE_BEFORE_AUTHENTICATION_ERROR";
        public static $FAILED_TO_SET_PASSIVE_MODE_ERROR = "FAILED_TO_SET_PASSIVE_MODE_ERROR";
        public static $AUTHENTICATION_FAILED_ERROR = "AUTHENTICATION_FAILED_ERROR";
        public static $LICENSE_READ_FAILED_ERROR = "LICENSE_READ_FAILED_ERROR";
        public static $LIST_DIRECTORY_FAILED_ERROR = "LIST_DIRECTORY_FAILED_ERROR";
        public static $GET_SYSTEM_TYPE_BEFORE_CONNECTION_ERROR = "GET_SYSTEM_TYPE_FAILED_ERROR";
        public static $GET_SYSTEM_TYPE_FAILED_ERROR = "GET_SYSTEM_TYPE_FAILED_ERROR";
        public static $FAILED_TO_CLOSE_CONNECTION_ERROR = "FAILED_TO_CLOSE_CONNECTION_ERROR";
        public static $GET_WORKING_DIRECTORY_BEFORE_CONNECTION_ERROR = "GET_WORKING_DIRECTORY_BEFORE_CONNECTION_ERROR";
        public static $DEBIAN_PRIVATE_KEY_BUG_ERROR = "DEBIAN_PRIVATE_KEY_BUG_ERROR";
        public static $COULD_NOT_LOAD_PROFILE_DATA_ERROR = "COULD_NOT_LOAD_PROFILE_DATA_ERROR";
        public static $PROFILE_NOT_READABLE_ERROR = "PROFILE_NOT_READABLE_ERROR";
        public static $PROFILE_SIZE_READ_ERROR = "PROFILE_SIZE_READ_ERROR";
        public static $UNSUPPORTED_CIPHER_ERROR = "UNSUPPORTED_CIPHER_ERROR";
        public static $PROBABLE_INCORRECT_PASSWORD_ERROR = "PROBABLE_INCORRECT_PASSWORD_ERROR";
        public static $IV_GENERATE_ERROR = "IV_GENERATE_ERROR";
        public static $SETTINGS_READ_ERROR = "SETTINGS_READ_ERROR";
        public static $SETTINGS_WRITE_ERROR = "SETTINGS_WRITE_ERROR";
        public static $ARCHIVE_READ_ERROR = "ARCHIVE_READ_ERROR";
        public static $LICENSE_WRITE_ERROR = "LICENSE_WRITE_ERROR";
        public static $PRO_CONFIG_WRITE_ERROR = "PRO_CONFIG_WRITE_ERROR";
        public static $REPLACEMENT_LICENSE_OLDER_ERROR = "REPLACEMENT_LICENSE_OLDER_ERROR";
        public static $INVALID_POSTED_LICENSE_ERROR = "INVALID_POSTED_LICENSE_ERROR";
        public static $SFTP_AUTHENTICATION_NOT_ENABLED = "SFTP_AUTHENTICATION_NOT_ENABLED";
        public static $LICENSE_DIRECTORY_NOT_WRITABLE_ERROR = "LICENSE_DIRECTORY_NOT_WRITABLE_ERROR";
        public static $INSTALL_DIRECTORY_EXISTS_ERROR = "INSTALL_DIRECTORY_EXISTS_ERROR";
        public static $INSTALL_PATH_NOT_WRITABLE_ERROR = "INSTALL_PATH_NOT_WRITABLE_ERROR";
        public static $INSTALL_DIRECTORY_DOES_NOT_EXIST_ERROR = "INSTALL_DIRECTORY_DOES_NOT_EXIST_ERROR";
        public static $INSTALL_DIRECTORY_INVALID_ERROR = "INSTALL_DIRECTORY_INVALID_ERROR";
        public static $INSTALL_ARCHIVE_INVALID_ERROR = "INSTALL_ARCHIVE_INVALID_ERROR";
        public static $INSTALL_ARCHIVE_EXTRACT_ERROR = "INSTALL_ARCHIVE_EXTRACT_ERROR";
        public static $INSTALL_SETUP_REMOVE_ERROR = "INSTALL_SETUP_REMOVE_ERROR";
        public static $INSTALL_SETUP_RENAME_ERROR = "INSTALL_SETUP_RENAME_ERROR";
        public static $INSTALL_SETUP_BACKUP_RESTORE_ERROR = "INSTALL_SETUP_BACKUP_RESTORE_ERROR";
        public static $LOGIN_FAILURE_EXCEEDED_ERROR = "LOGIN_FAILURE_EXCEEDED_ERROR";
        public static $TLS_REQUIRED_ERROR = "TLS_REQUIRED_ERROR";
        public static $FILE_NOT_WRITABLE_ERROR = "FILE_NOT_WRITABLE_ERROR";
        public static $CURL_NOT_INSTALLED = "CURL_NOT_INSTALLED";
        public static $QUOTA_EXCEEDED_MESSAGE = "QUOTA_EXCEEDED_MESSAGE";
        public static $FILE_CHANGED_ON_SERVER = "FILE_CHANGED_ON_SERVER";
        public static $REMOTE_FILE_NOT_FOUND = "REMOTE_FILE_NOT_FOUND";
    }

    // i wanted this to be a static member on LocalizableExceptionCodeLookup but then i would have to instantiate it
    // every time due to PHP not allowing expressions in class definitions. this global is the lesser of two evils
    // or make it a singleton soon
    // todo make LocalizableExceptionCodeLookup a singleton
    $EXCEPTION_CODE_MAP = array(
        LocalizableExceptionDefinition::$FETCH_IN_PROGRESS_ERROR,
        LocalizableExceptionDefinition::$FETCH_FAILED_ERROR,
        LocalizableExceptionDefinition::$PRIVATE_KEY_LOAD_ERROR,
        LocalizableExceptionDefinition::$PUBLIC_KEY_LOAD_ERROR,
        LocalizableExceptionDefinition::$DECRYPT_ERROR,
        LocalizableExceptionDefinition::$CONNECTION_FAILURE_ERROR,
        LocalizableExceptionDefinition::$UNCONNECTED_DISCONNECT_ERROR,
        LocalizableExceptionDefinition::$FILE_DOES_NOT_EXIST_ERROR,
        LocalizableExceptionDefinition::$FILE_EXISTS_ERROR,
        LocalizableExceptionDefinition::$FILE_PERMISSION_ERROR,
        LocalizableExceptionDefinition::$GENERAL_FILE_SOURCE_ERROR,
        LocalizableExceptionDefinition::$OPERATION_BEFORE_CONNECTION_ERROR,
        LocalizableExceptionDefinition::$OPERATION_BEFORE_AUTHENTICATION_ERROR,
        LocalizableExceptionDefinition::$AUTHENTICATION_BEFORE_CONNECTION_ERROR,
        LocalizableExceptionDefinition::$PASSIVE_MODE_BEFORE_AUTHENTICATION_ERROR,
        LocalizableExceptionDefinition::$FAILED_TO_SET_PASSIVE_MODE_ERROR,
        LocalizableExceptionDefinition::$AUTHENTICATION_FAILED_ERROR,
        LocalizableExceptionDefinition::$LICENSE_READ_FAILED_ERROR,
        LocalizableExceptionDefinition::$LIST_DIRECTORY_FAILED_ERROR,
        LocalizableExceptionDefinition::$GET_SYSTEM_TYPE_BEFORE_CONNECTION_ERROR,
        LocalizableExceptionDefinition::$GET_SYSTEM_TYPE_FAILED_ERROR,
        LocalizableExceptionDefinition::$FAILED_TO_CLOSE_CONNECTION_ERROR,
        LocalizableExceptionDefinition::$GET_WORKING_DIRECTORY_BEFORE_CONNECTION_ERROR,
        LocalizableExceptionDefinition::$DEBIAN_PRIVATE_KEY_BUG_ERROR,
        LocalizableExceptionDefinition::$COULD_NOT_LOAD_PROFILE_DATA_ERROR,
        LocalizableExceptionDefinition::$PROFILE_NOT_READABLE_ERROR,
        LocalizableExceptionDefinition::$PROFILE_SIZE_READ_ERROR,
        LocalizableExceptionDefinition::$UNSUPPORTED_CIPHER_ERROR,
        LocalizableExceptionDefinition::$PROBABLE_INCORRECT_PASSWORD_ERROR,
        LocalizableExceptionDefinition::$IV_GENERATE_ERROR,
        LocalizableExceptionDefinition::$SETTINGS_READ_ERROR,
        LocalizableExceptionDefinition::$SETTINGS_WRITE_ERROR,
        LocalizableExceptionDefinition::$ARCHIVE_READ_ERROR,
        LocalizableExceptionDefinition::$LICENSE_WRITE_ERROR,
        LocalizableExceptionDefinition::$PRO_CONFIG_WRITE_ERROR,
        LocalizableExceptionDefinition::$REPLACEMENT_LICENSE_OLDER_ERROR,
        LocalizableExceptionDefinition::$INVALID_POSTED_LICENSE_ERROR,
        LocalizableExceptionDefinition::$SFTP_AUTHENTICATION_NOT_ENABLED,
        LocalizableExceptionDefinition::$LICENSE_DIRECTORY_NOT_WRITABLE_ERROR,
        LocalizableExceptionDefinition::$INSTALL_DIRECTORY_EXISTS_ERROR,
        LocalizableExceptionDefinition::$INSTALL_PATH_NOT_WRITABLE_ERROR,
        LocalizableExceptionDefinition::$INSTALL_DIRECTORY_DOES_NOT_EXIST_ERROR,
        LocalizableExceptionDefinition::$INSTALL_DIRECTORY_INVALID_ERROR,
        LocalizableExceptionDefinition::$INSTALL_ARCHIVE_INVALID_ERROR,
        LocalizableExceptionDefinition::$INSTALL_ARCHIVE_EXTRACT_ERROR,
        LocalizableExceptionDefinition::$INSTALL_SETUP_REMOVE_ERROR,
        LocalizableExceptionDefinition::$INSTALL_SETUP_RENAME_ERROR,
        LocalizableExceptionDefinition::$INSTALL_SETUP_BACKUP_RESTORE_ERROR,
        LocalizableExceptionDefinition::$LOGIN_FAILURE_EXCEEDED_ERROR,
        LocalizableExceptionDefinition::$TLS_REQUIRED_ERROR,
        LocalizableExceptionDefinition::$FILE_NOT_WRITABLE_ERROR,
        LocalizableExceptionDefinition::$CURL_NOT_INSTALLED,
        LocalizableExceptionDefinition::$QUOTA_EXCEEDED_MESSAGE,
        LocalizableExceptionDefinition::$FILE_CHANGED_ON_SERVER,
        LocalizableExceptionDefinition::$REMOTE_FILE_NOT_FOUND
    );

    abstract class LocalizableExceptionCodeLookup {
        static function codeToName($errorCode) {
            global $EXCEPTION_CODE_MAP;
            return $EXCEPTION_CODE_MAP[$errorCode];
        }

        static function nameToCode($errorName) {
            global $EXCEPTION_CODE_MAP;
            return array_search($errorName, $EXCEPTION_CODE_MAP);
        }
    }

    class LocalizableException extends Exception {
        /**
         * @var array
         */
        private $context;

        public function __construct($message, $errorName, $context = null, $previous = null) {
            $code = LocalizableExceptionCodeLookup::nameToCode($errorName);

            parent::__construct($message, $code, $previous);

            $this->context = $context;
        }

        /**
         * @return array
         */
        public function getContext() {
            return $this->context;
        }
    }