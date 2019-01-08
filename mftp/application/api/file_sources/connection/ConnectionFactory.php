<?php
    require_once(dirname(__FILE__) . '/../../constants.php');
    require_once(dirname(__FILE__) . '/FTPConnection.php');
    require_once(dirname(__FILE__) . '/MFTPConnection.php');
    require_once(dirname(__FILE__) . '/MockConnection.php');
    require_once(dirname(__FILE__) . '/SFTPConnection.php');
    require_once(dirname(__FILE__) . '/PHPSeclibConnection.php');

    class ConnectionFactory {
        /**
         * @param $connectionType string
         * @param $configuration FTPConfiguration|MockConnectionConfiguration|SFTPConfiguration
         * @return ConnectionBase
         */
        public function getConnection($connectionType, $configuration) {
            switch ($connectionType) {
                case 'ftp':
                    $useMFTPLibrary = function_exists("fsockopen") && defined("USE_MFTP_LIBRARY") && USE_MFTP_LIBRARY;

                    if ($useMFTPLibrary)
                        return new MFTPConnection($configuration);

                    return new FTPConnection($configuration);
                case 'mock':
                    return new MockConnection($configuration);
                case 'sftp':
                    if (USE_SECLIB_LIBRARY)
                        return new PHPSeclibConnection($configuration);

                    return new SFTPConnection($configuration);
                default:
                    throw new InvalidArgumentException("Unknown connection type '$connectionType' in getConnection");
            }
        }
    }