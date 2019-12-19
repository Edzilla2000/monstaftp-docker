<?php
    /**
     *  Chunk size of data to read at once
     */
    define("MFTP_BUFF_SIZE", 8192);
    /**
     *  Time to sleep between stream_socket_enable_crypto retries
     */
    define("MFTP_SSL_SLEEP_DELAY_USEC", 1000000);
    /**
     *  Number of times to retry enabling SSL on a socket if stream_socket_enable_crypto returns false
     */
    define("MFTP_SSL_RETRY_COUNT", 100); // total time to wait = MFTP_SSL_RETRY_COUNT * MFTP_SSL_SLEEP_DELAY_USEC


    if (!function_exists("monstaBasename")) {
        function monstaBasename($path) {
            // manual basename splitting because built in function may not work with special characters
            $splitPath = explode("/", $path);
            return $splitPath[count($splitPath) - 1];
        }
    }

    /**
     * Class MFTPException
     */
    class MFTPException extends Exception {
        /**
         * @var null|string
         */
        private $path;
        /**
         * @var null|string
         */
        private $rawMessage;

        /**
         * MFTPException constructor.
         * @param string $message
         * @param null $code
         * @param null $rawMessage
         * @param null $path
         */
        public function __construct($message, $code = null, $rawMessage = null, $path = null) {
            parent::__construct($message, $code);
            $this->path = $path;
            $this->rawMessage = $rawMessage;
        }

        /**
         * @return null|string
         */
        public function getPath() {
            return $this->path;
        }

        /**
         * @return null|string
         */
        public function getRawMessage() {
            return $this->rawMessage;
        }
    }

    /**
     * Class MFTPAuthenticationException
     */
    class MFTPLineReadException extends MFTPException {

    }

    /**
     * Class MFTPAuthenticationException
     */
    class MFTPAuthenticationException extends MFTPException {

    }

    /**
     * Class MFTPAuthenticationUsernameException
     */
    class MFTPAuthenticationUsernameException extends MFTPAuthenticationException {

    }

    /**
     * Class MFTPAuthenticationPasswordException
     */
    class MFTPAuthenticationPasswordException extends MFTPAuthenticationException {

    }

    /**
     * Class MFTPAuthenticationRequiresTlsException
     */
    class MFTPAuthenticationRequiresTlsException extends MFTPAuthenticationException {

    }

    /**
     * Class MFTPFileException
     */
    class MFTPFileException extends MFTPException {

    }

    /**
     * Class MFTPRemoteFileException
     */
    class MFTPRemoteFileException extends MFTPFileException {

    }

    /**
     * Class MFTPRemoteFileExistsException
     */
    class MFTPRemoteFileExistsException extends MFTPRemoteFileException {

    }

    /**
     * Class MFTPNoSuchRemoteFileException
     */
    class MFTPNoSuchRemoteFileException extends MFTPRemoteFileException {

    }

    /**
     * Class MFTPRemoteFilePermissionDenied
     */
    class MFTPRemoteFilePermissionDenied extends MFTPRemoteFileException {

    }

    /**
     * Class MFTPLocalFileException
     */
    class MFTPLocalFileException extends MFTPFileException {

    }

    /**
     * Class MFTPNoSuchLocalFileException
     */
    class MFTPNoSuchLocalFileException extends MFTPLocalFileException {

    }

    /**
     * Class MFTPQuotaExceededException
     */
    class MFTPQuotaExceededException extends MFTPException {

    }

    /**
     * Class mftp_conn
     * It's as close as we can get to a struct in PHP
     */
    class mftp_conn {
        /**
         * @var null|resource
         */
        public $commandSocket = null;

        /**
         * @var bool
         */
        public $isPassive = false;

        /**
         * @var bool
         */
        public $isSecure = false;

        /**
         * @var null|resource
         */
        public $dataSocket = null;

        /**
         * @var null|resource
         */
        public $listeningSocket = null;

        /**
         * @var int
         */
        public $timeout = 90;

        /**
         * @var null|int
         */
        public $transferType = null;

        /**
         * @var null|array
         */
        public $features = null;

        /**
         * @var string
         */
        public $host;

        /**
         * @var null|MFTPLogger
         * If not null, the instance of MFTPLogger will be called to handle log messages
         */
        public $logger = null;

        /**
         * @var bool
         */
        public $isIpV6;

        /**
         * @var bool
         */
        public $disableExtendedPassive = false;

        /**
         * mftp_conn constructor.
         * @param string $host
         * @param int $timeout
         * @param null|MFTPLogger logger
         */
        public function __construct($host, $timeout = null, $logger = null) {
            $this->host = $host;

            if (!is_null($timeout))
                $this->timeout = $timeout;

            $this->logger = $logger;
        }
    }

    /**
     * Class mftp_response
     * Holds the response code and text from a line returned by FTP server
     * Acts like a struct
     */
    class mftp_response {
        /**
         * @var null|string
         */
        public $code = null;

        /**
         * @var null|string
         */
        public $text = null;

        /**
         * mftp_response constructor.
         * @param $responseCode int
         * @param $responseText string
         */
        public function __construct($responseCode, $responseText) {
            $this->code = $responseCode;

            if (strlen($responseText) != 0 && substr($responseText, strlen($responseText) - 1) == "\r") {
                $responseText = substr($responseText, 0, strlen($responseText) - 1);
            }

            $this->text = $responseText;
        }
    }

    /**
     * Class MFTPLogger
     * Basic logging class that can be passed in when creating mftp_conn to handle its logging
     */
    abstract class MFTPLogger {

        /**
         * @param $message
         */
        abstract public function log($message);
    }

    /**
     * Class MFTPErrorLogLogger
     * Calls to log() will send the message to the error_log function
     */
    class MFTPErrorLogLogger extends MFTPLogger {
        /**
         * @param $message
         */
        public function log($message) {
            error_log($message);
        }
    }

    /**
     * Class MFTPEchoLogger
     * Calls to log() will echo the $message and append \n
     */
    class MFTPEchoLogger extends MFTPLogger {
        /**
         * @param $message
         */
        public function log($message) {
            echo $message . "\n";
        }
    }

    /**
     * @param string $host
     * @param int $port
     * @param null int $timeout
     * @param null|MFTPLogger $logger
     * @return bool|mftp_conn
     * @throws Exception
     * Connect to an FTP server
     */
    function mftp_connect($host, $port = 21, $timeout = null, $logger = null) {
        if (!function_exists("fsockopen"))
            throw new Exception("Please enable or install the PHP socket library.");

        $conn = new mftp_conn($host, $timeout, $logger);

        $sock = @fsockopen(_mftp_escape_ip_address($host), $port, $errNumber, $errStr, $conn->timeout);

        if (!$sock)
            return false;

        $socketName = stream_socket_get_name($sock, false);

        $conn->isIpV6 = !_mftp_address_is_ipv4($socketName);

        $conn->commandSocket = $sock;

        do {
            $line = _mftp_read_line($conn, $conn->commandSocket);
        } while (substr($line, 0, 4) == "220-");

        return $conn;
    }

    /**
     * @param mftp_conn $connection
     * @throws MFTPException
     * Enables SSL on a connection, call after mftp_connect and before mftp_login
     */
    function mftp_enable_ssl($connection) {
        $resp = _mftp_perform_command($connection, "AUTH TLS");

        if ($resp->code != 234)
            throw new MFTPException("Auth TLS request failed", $resp->code, $resp->text);

        _mftp_enable_crypto_on_socket($connection->commandSocket, $connection->commandSocket);

        _mftp_enable_protection($connection);

        $connection->isSecure = true;
    }


    /**
     * @param mftp_conn $connection
     * @param string $username
     * @param string $password
     * @throws MFTPAuthenticationRequiresTlsException
     * @throws MFTPAuthenticationPasswordException
     * @throws MFTPAuthenticationUsernameException
     * Login to FTP server after connecting (and optionally after enabling SSL)
     *
     */
    function mftp_login($connection, $username, $password) {
        $resp = _mftp_perform_command($connection, "USER", $username);

        if ($resp->code == 230)
            return; // auth without password

        if ($resp->code == 421)
            throw new MFTPAuthenticationRequiresTlsException("FTP server requires TLS", $resp->code, $resp->text);

        if ($resp->code != 331)
            throw new MFTPAuthenticationUsernameException("FTP username rejected", $resp->code, $resp->text);

        $resp = _mftp_perform_command($connection, "PASS", $password);

        if (substr($resp->text, 0, 1) == "-")
            _mftp_read_remaining_lines($connection, $connection->commandSocket);

        if ($resp->code != 230)
            throw new MFTPAuthenticationPasswordException("FTP password rejected", $resp->code, $resp->text);
    }

    /**
     * @param mftp_conn $connection
     * @return string
     * @throws MFTPException
     * Returns the systype of the FTP server
     */
    function mftp_get_systype($connection) {
        $resp = _mftp_perform_command($connection, "SYST");
        if ($resp->code != 215 && $resp->code != 200)
            throw new MFTPException("Unable to get sys type", $resp->code, $resp->text);

        $splitType = explode(' ', $resp->text);

        return $splitType[0];
    }

    /**
     * @param mftp_conn $connection
     * @param string $directory
     * Changes into a directory on the FTP server
     */
    function mftp_chdir($connection, $directory) {
        $resp = _mftp_perform_command($connection, "CWD", $directory);

        if ($resp->code != 250 && $resp->code != 200)
            _mftp_convert_response_to_exception("Unable to change directory", $resp, $directory);
    }

    /**
     * @param mftp_conn $connection
     * @return string
     * Returns the current directory on the FTP server
     */
    function mftp_pwd($connection) {
        $resp = _mftp_perform_command($connection, "PWD");

        if ($resp->code != 257 && $resp->code != 200)
            _mftp_convert_response_to_exception("PWD failed", $resp);

        $firstDQuote = strpos($resp->text, '"');
        $lastDQuote = strrpos($resp->text, '"');

        return substr($resp->text, $firstDQuote + 1, $lastDQuote - $firstDQuote - 1);
    }

    /**
     * @param mftp_conn $connection
     * @param string $listArgs
     * @return array
     * Returns an array of lines output from the FTP LIST command
     */
    function mftp_rawlist($connection, $listArgs) {
        $resp = _mftp_execute_command_for_data_socket($connection, "LIST", $listArgs);

        if ($resp->code == 226) // some servers don't open a ftp-data connection if the directory is empty
            return array();

        if ($resp->code != 150 && $resp->code != 125)
            _mftp_convert_response_to_exception("LIST command failed", $resp, $listArgs);

        if (substr($resp->text, 0, 1) == "-")
            _mftp_read_remaining_lines($connection, $connection->commandSocket);

        $rawLines = _mftp_read_data_connection($connection);

        $lines = array();

        foreach (explode("\r\n", $rawLines) as $rawLine) {
            if (trim($rawLine) == "")
                continue;

            $lines[] = $rawLine;
        }

        _mftp_close_data_connection($connection);

        _mftp_read_remaining_lines($connection, $connection->commandSocket);

        return $lines;
    }

    /**
     * @param mftp_conn $connection
     * @param string $localPath
     * @param string $remotePath
     * @param int $transferMode
     * Retrieve a remote file and save to local storage
     */
    function mftp_get($connection, $localPath, $remotePath, $transferMode) {
        $handle = _mftp_open_local_file($localPath, "w");

        _mftp_set_transfer_type($connection, $transferMode);

        $resp = _mftp_execute_command_for_data_socket($connection, "RETR", $remotePath);

        if ($resp->code != 150 && $resp->code != 125)
            _mftp_convert_response_to_exception("RETR command failed", $resp, $remotePath);

        if (substr($resp->text, 0, 1) == "-")
            _mftp_read_remaining_lines($connection, $connection->commandSocket);

        _mftp_accept_data_connection($connection);

        while ($buf = fread($connection->dataSocket, MFTP_BUFF_SIZE))
            fwrite($handle, $buf);

        _mftp_close_data_connection($connection);

        _mftp_read_remaining_lines($connection, $connection->commandSocket);
    }

    /**
     * @param mftp_conn $connection
     * @param string $remotePath
     * @param string $localPath
     * @param int $transferMode
     * @param int $progressCallbackTimeout
     * @param callable $progressCallback
     * Upload a file from local to remote FTP server
     */
    function mftp_put($connection, $remotePath, $localPath, $transferMode, $progressCallbackTimeout = null, $progressCallback = null) {
        $handle = _mftp_open_local_file($localPath, "r");

        _mftp_set_transfer_type($connection, $transferMode);

        $resp = _mftp_execute_command_for_data_socket($connection, "STOR", $remotePath);

        if ($resp->code != 150 && $resp->code != 125)
            _mftp_convert_response_to_exception("STOR command failed", $resp, $remotePath);

        if (substr($resp->text, 0, 1) == "-")
            _mftp_read_remaining_lines($connection, $connection->commandSocket);

        _mftp_accept_data_connection($connection);

        $lastCallbackTime = time();

        $totalBytesWritten = 0;

        while ($buf = fread($handle, MFTP_BUFF_SIZE)) {
            $totalBytesWritten += fwrite($connection->dataSocket, $buf);

            if ($progressCallbackTimeout !== null && $progressCallback !== null) {
                if (time() - $lastCallbackTime >= $progressCallbackTimeout) {
                    $progressCallback($totalBytesWritten);
                    $lastCallbackTime = time();
                }
            }
        }

        _mftp_close_data_connection($connection);

        $storResultLines = _mftp_read_remaining_lines($connection, $connection->commandSocket);

        $resultLinesCount = count($storResultLines);

        if ($resultLinesCount != 0) {
            $resp = _mftp_parse_response($storResultLines[$resultLinesCount - 1]);

            if ($resp->code == 552)
                throw new MFTPQuotaExceededException("STOR command failed due to quota exceeded", $resp->code,
                    $resp->text);

            if ($resp->code < 200 || $resp->code >= 300)
                throw new MFTPException("STOR command failed", $resp->code, $resp->text);
        }
    }

    /**
     * @param mftp_conn $connection
     * @param string $remotePath
     * Make a directory on the FTP server
     */
    function mftp_mkdir($connection, $remotePath) {
        $resp = _mftp_perform_command($connection, "MKD", $remotePath);

        if ($resp->code != 257 && $resp->code != 200)
            _mftp_convert_response_to_exception("Error making directory at $remotePath", $resp, $remotePath);
    }

    /**
     * @param $connection
     * @param $remotePath
     * Recursively make all directories up to and including $remotePath
     */
    function mftp_mkdir_recursive($connection, $remotePath) {
        $directoriesInTree = array();

        $pathComponents = explode("/", preg_replace("#/+#", "/", $remotePath));

        for ($componentIndex = 0; $componentIndex < count($pathComponents); ++$componentIndex) {
            if ($pathComponents[$componentIndex] == "")
                continue;

            $subPath = implode("/", array_slice($pathComponents, 0, $componentIndex + 1));

            if ($subPath == "")
                continue;

            $directoriesInTree[] = $subPath;
        }

        if (count($directoriesInTree) == 0)
            return;

        // start at the top and go down until the first existing one is not found

        for ($dirIndex = count($directoriesInTree); $dirIndex-- > 0;) {
            try {
                mftp_chdir($connection, $directoriesInTree[$dirIndex]);
                break;
            } catch (MFTPNoSuchRemoteFileException $e) {
                // good, try next one down
            }
        }

        // now go back up creating them
        for (++$dirIndex; $dirIndex < count($directoriesInTree); ++$dirIndex) {
            mftp_mkdir($connection, $directoriesInTree[$dirIndex]);
        }
    }


    /**
     * @param mftp_conn $connection
     * @param string $remotePath
     * Delete a file on the FTP server
     */
    function mftp_delete($connection, $remotePath) {
        $resp = _mftp_perform_command($connection, "DELE", $remotePath);
        if ($resp->code != 250 && $resp->code != 200)
            _mftp_convert_response_to_exception("Delete of $remotePath failed", $resp, $remotePath);
    }

    /**
     * @param mftp_conn $connection
     * @param string $remotePath
     * Remove a directory on the FTP server - it must be empty
     */
    function mftp_rmdir($connection, $remotePath) {
        $remoteDir = dirname($remotePath);

        mftp_chdir($connection, ($remoteDir == "\\") ? "/" : $remoteDir);

        $resp = _mftp_perform_command($connection, "RMD", monstaBasename($remotePath));

        if ($resp->code != 250 && $resp->code != 200)
            _mftp_convert_response_to_exception("Unable to remove directory at $remotePath", $resp, $remotePath);
    }


    /**
     * @param mftp_conn $connection
     * @param string $sourcePath
     * @param string $destinationPath
     * Rename/move a file on the FTP server
     */
    function mftp_rename($connection, $sourcePath, $destinationPath) {
        $resp = _mftp_perform_command($connection, "RNFR", $sourcePath);

        if ($resp->code != 350)
            _mftp_convert_response_to_exception("Could not rename from $sourcePath", $resp, $sourcePath);

        $resp = _mftp_perform_command($connection, "RNTO", $destinationPath);

        if ($resp->code != 250 && $resp->code != 200)
            _mftp_convert_response_to_exception("Could not rename to $destinationPath", $resp, $destinationPath);
    }

    /**
     * @param mftp_conn $connection
     * @param int $mode
     * @param string $remotePath
     * CHMOD a file on the FTP server
     */
    function mftp_chmod($connection, $mode, $remotePath) {
        $modeString = sprintf("%o $remotePath", $mode, $remotePath);

        $resp = _mftp_perform_command($connection, "SITE", "CHMOD $modeString");

        if ($resp->code != 250 && $resp->code != 200)
            _mftp_convert_response_to_exception("CHMOD operation failed", $resp);
    }

    /**
     * @param mftp_conn $connection
     * @return bool
     * Close an MFTP connection
     */
    function mftp_disconnect($connection) {
        _mftp_put_cmd($connection, "QUIT");
        _mftp_close_data_connection($connection);

        if (!is_null($connection->commandSocket)) {
            fclose($connection->commandSocket);
            $connection->commandSocket = null;
        }

        return true;
    }

    /**
     * @param mftp_conn $connection
     * @return array
     * Perform the FEAT command on a server and return all features as an array
     */
    function mftp_features($connection) {
        if (!is_null($connection->features)) {
            return $connection->features;
        }

        _mftp_put_cmd($connection, "FEAT");

        $features = array();

        while (true) {
            $line = _mftp_read_line($connection, $connection->commandSocket);

            if (substr($line, 0, 4) == "211-") {
                continue;
            }

            if (preg_match("/^\\d{3} /", $line)) {
                break; // break if any final response code is found
            }

            $feature = trim($line);

            $features[] = $feature;
        }

        $connection->features = $features;

        return $features;
    }

    /**
     * @param mftp_conn $connection
     * @param string $argument
     * @return array
     * Perform the SITE command with given argument (string) and return an array of response lines.
     * Assumes lines start with NNN-<text>, and final line is NNN <text>
     * Does not do error checking or raise exceptions etc on "bad" responses, just returns the lines
     */
    function mftp_site($connection, $argument) {
        _mftp_put_cmd($connection, "SITE", $argument);

        $responseLines = array();

        while (true) {
            $responseLine = trim(_mftp_read_line($connection, $connection->commandSocket));

            $responseLines[] = trim($responseLine);

            if (strlen($responseLine) < 4 || substr($responseLine, 3, 1) != "-")
                break;
        }

        return $responseLines;
    }

    /**
     * @param mftp_conn $connection
     * (Try to) enable UTF8 for transfers - this will fail silently
     */
    function mftp_utf8_on($connection) {
        _mftp_perform_command($connection, "OPTS", "UTF8 ON");
    }

    /**
     * @param mftp_conn $connection
     * @param bool $passive
     * Enable or disable passive mode for an MFTP connection - this does not execute PASV immediately, rather it tells
     * the connection it should use PASV when a data connection is required
     */
    function mftp_pasv($connection, $passive) {
        $connection->isPassive = $passive;
    }

    /**
     * "Private" functions below
     */

    /**
     * @param resource $dataSocket
     * @param resource $sessionSocket
     * @param bool $block
     * @return bool
     * @throws MFTPException
     * Enables crypto on a socket stream
     */
    function _mftp_enable_crypto_on_socket($dataSocket, $sessionSocket, $block = true) {
        stream_context_set_option($dataSocket, 'ssl', 'verify_peer', false);
        stream_context_set_option($dataSocket, "ssl", "allow_self_signed", true);
        stream_context_set_option($dataSocket, 'ssl', 'verify_peer_name', false);


        if (!$block)
            stream_set_blocking($dataSocket, false);

        if (defined("STREAM_CRYPTO_METHOD_ANY_CLIENT"))
            $cryptoType = STREAM_CRYPTO_METHOD_ANY_CLIENT;
        else
            $cryptoType = STREAM_CRYPTO_METHOD_SSLv23_CLIENT;

        $sslEnabled = stream_socket_enable_crypto($dataSocket, true, $cryptoType, $sessionSocket);

        if (!$block && $sslEnabled === 0) {
            stream_set_blocking($dataSocket, true);
            return false;
        }

        if (!$block)
            stream_set_blocking($dataSocket, true);

        if ($sslEnabled != true) {
            if ($dataSocket != $sessionSocket)
                fclose($sessionSocket);

            fclose($dataSocket);
            throw new MFTPException("Unable to enable SSL.");
        }

        return true;
    }

    /**
     * @param $connection
     * @throws MFTPException
     * Sets PBSZ and sends a PROT P command
     */
    function _mftp_enable_protection($connection) {
        $resp = _mftp_perform_command($connection, "PBSZ", "0");

        if ($resp->code != 200)
            throw new MFTPException("PBSZ command failed", $resp->code, $resp->text);

        $resp = _mftp_perform_command($connection, "PROT", "P");

        if ($resp->code != 200)
            throw new MFTPException("PROT command failed setting mode to P", $resp->code, $resp->text);
    }

    /**
     * @param $connection
     * @param $command
     * @param null $args
     * @return mftp_response
     * Prepares the data socket, executes a command, then opens the data socket before reading the command response
     */
    function _mftp_execute_command_for_data_socket($connection, $command, $args = null) {
        $dataSockResult = _mftp_prepare_data_socket($connection);

        _mftp_put_cmd($connection, $command, $args);

        $commandSocketArr = array($connection->commandSocket);
        $writeSockets = NULL;
        $exceptSockets = NULL;
        $retryCount = 0;
        $lastResponse = null;

        if ($connection->isPassive)
            while (++$retryCount < MFTP_SSL_RETRY_COUNT) {
                if (_mftp_pasv_connect($connection, $dataSockResult[0], $dataSockResult[1])) {
                    break;
                }

                if (0 !== stream_select($commandSocketArr, $writeSockets, $exceptSockets, 0, MFTP_SSL_SLEEP_DELAY_USEC)) {
                    $lastResponse = _mftp_parse_response(_mftp_read_last_line($connection, $connection->commandSocket));
                    if ($lastResponse->code != 150)
                        break;
                }
            }

        if ($lastResponse == null)
            $lastResponse = _mftp_parse_response(_mftp_read_last_line($connection, $connection->commandSocket));

        return $lastResponse;
    }

    /**
     * @param int $transferMode
     * @return string
     * Converts a transferMode integer into A (for FTP_ASCII/1) or I (for FTP_BINARY/2)
     */
    function _mftp_get_transfer_mode_code($transferMode) {
        if ($transferMode != FTP_ASCII && $transferMode != FTP_BINARY)
            throw new InvalidArgumentException("Unknown transfer mode, please use only FTP_ASCII or FTP_BINARY");

        return $transferMode == FTP_ASCII ? "A" : "I";
    }

    /**
     * @param string $localPath
     * @param int $mode
     * @return resource
     * @throws MFTPLocalFileException
     * Wrapper around fopen to convert failures to exceptions
     */
    function _mftp_open_local_file($localPath, $mode) {
        $handle = @fopen($localPath, $mode);

        if ($handle === false) {
            $err = error_get_last();
            throw new MFTPLocalFileException("Unable to open local file $localPath: " . $err['message'], null, null,
                $localPath);
        }

        return $handle;
    }

    /**
     * @param mftp_conn $connection
     * @param int $transferMode
     * @throws MFTPException
     * Sets the transfer type on connection to A or I
     */
    function _mftp_set_transfer_type($connection, $transferMode) {
        if ($connection->transferType == $transferMode)
            return;

        $transferModeCode = _mftp_get_transfer_mode_code($transferMode);

        $resp = _mftp_perform_command($connection, "TYPE", $transferModeCode);

        if ($resp->code != 200)
            throw new MFTPException("Unable to set transfer TYPE: {$resp->code} / $resp->text", $resp->code,
                $resp->text);

        $connection->transferType = $transferMode;
    }

    /**
     * @param mftp_conn $connection
     * @return array|null
     * @throws Exception
     * @throws MFTPException
     * Execute a EPSV (if supported) or PASV command on the server and return an array containing [ip, port]. Returns
     * null if a data connection is already active
     */
    function _mftp_pasv_setup($connection) {
        if (!is_null($connection->dataSocket))
            return null;

        if (_mftp_connection_supports_epsv($connection)) {
            return _mftp_epsv_setup($connection);
        }

        $resp = _mftp_perform_command($connection, "PASV");
        if ($resp->code != 227 && $resp->code != 200)
            throw new Exception("Unable to enable passive mode: {$resp->code} / {$resp->text}");

        if (!preg_match("/\\((\\d+),(\\d+),(\\d+),(\\d+),(\\d+),(\\d+)\\)/", $resp->text, $matches))
            throw new Exception("Unable to parse passive mode response: {$resp->text}");

        $ip = join(".", array_slice($matches, 1, 4));
        $port = $matches[5] * 256 + $matches[6];

        $connection->isPassive = true;

        return array($ip, $port);
    }


    /**
     * @param $connection
     * @return array
     * @throws Exception
     * Execute an EPSV command on the server and return an array containing [host, port]. The host is always the same
     * as that used to set up the data socket.
     */
    function _mftp_epsv_setup($connection) {
        $resp = _mftp_perform_command($connection, "EPSV");

        if ($resp->code == 500) {
            // some FTP servers claim to support EPSV but don't really. fall back to PASV in this case.
            $connection->disableExtendedPassive = true;
            return _mftp_pasv_setup($connection);
        }

        if ($resp->code != 229 && $resp->code != 200)
            throw new Exception("Unable to enable extended passive mode: {$resp->code} / {$resp->text}");

        if (!preg_match("/\\(([^)]+)\\)/", $resp->text, $matches))
            throw new Exception("Unable to parse extended passive mode response: {$resp->text}");

        $portField = $matches[1];

        $delimiter = substr($portField, 0, 1);

        $splitPortField = explode($delimiter, $portField);

        return array($connection->host, $splitPortField[3]);
    }

    /**
     * @param $connection
     * @param $ip
     * @param $port
     * @return bool
     * @throws MFTPException
     * Connect the passive socket and enable crypto if required
     */
    function _mftp_pasv_connect($connection, $ip, $port) {
        if (is_null($connection->dataSocket)) {
            // this might be called multiple times to try to get crypto going

            $connectionResult = @fsockopen(_mftp_escape_ip_address($ip), $port, $errNumber, $errStr,
                $connection->timeout);

            if ($connectionResult === false)
                throw new MFTPException("Unable to switch to passive mode, error opening connection.");

            $connection->dataSocket = $connectionResult;
        }

        $connection->isPassive = true;

        if ($connection->isSecure && !_mftp_enable_crypto_on_socket($connection->dataSocket, $connection->commandSocket, false))
            return false;

        return true;
    }

    /**
     * @param mftp_conn $connection
     * @param string $command
     * @param string null $args
     * @throws Exception
     * Writes the command and args (if supplied) to the command socket of the FTP connection
     */
    function _mftp_put_cmd($connection, $command, $args = null) {
        if (strpos($command, "\r\n") !== FALSE)
            throw new InvalidArgumentException("New line found in command $command");

        if (!is_null($args)) {
            if (strpos($args, "\r\n") !== FALSE)
                throw new InvalidArgumentException("New lines found in args $command");

            $command .= " " . $args;
        }

        $command .= "\r\n";

        if (!is_null($connection->logger))
            $connection->logger->log($command);

        $bytesWritten = fwrite($connection->commandSocket, $command);

        if ($bytesWritten === FALSE || $bytesWritten != strlen($command))
            throw new Exception("Expected to write " . strlen($command) . " bytes but only wrote $bytesWritten");
    }

    /**
     * @param string $responseLine
     * @return bool
     * Return true if the reponse line is "NNN Message". Multi-line messages will be in the format "NNN-Message" before
     * the last line (note the "-" after the number)
     */
    function _mftp_is_last_response_line($responseLine) {
        return substr($responseLine, 3, 1) === " ";
    }

    /**
     * @param mftp_conn $connection
     * @param resource $socket
     * @return array
     * Read all lines up to and including the last line (as per the _mftp_is_last_response_line check above)
     */
    function _mftp_read_remaining_lines($connection, $socket) {
        $responseLines = array();

        do {
            $responseLine = _mftp_read_line($connection, $socket);
            $responseLines[] = $responseLine;
        } while (!_mftp_is_last_response_line($responseLine));

        return $responseLines;
    }

    /**
     * @param mftp_conn $connection
     * @param resource $socket
     * @return string|null
     * Read all remaining lines on a socket but return just the last one
     */
    function _mftp_read_last_line($connection, $socket) {
        $lines = _mftp_read_remaining_lines($connection, $socket);
        $lineCount = count($lines);
        return $lineCount == 0 ? null : $lines[$lineCount - 1];
    }

    /**
     * @param $connection
     * @param resource $socket
     * @return string
     * @throws MFTPLineReadException
     * Reads and returns one line from the given socket, throws exception if false is read (no data/socket closed)
     */
    function _mftp_read_line($connection, $socket) {
        $line = fgets($socket, MFTP_BUFF_SIZE);

        if (!is_null($connection->logger))
            $connection->logger->log($line);

        if ($line === false)
            throw new MFTPLineReadException("Could not read line from socket");

        return $line;
    }

    /**
     * @param string $responseLine
     * @return mftp_response
     * Convert a response line to an mftp_response "struct"
     */
    function _mftp_parse_response($responseLine) {
        list($responseCode, $responseText) = sscanf($responseLine, "%d %[^\t\n]");
        return new mftp_response($responseCode, $responseText);
    }

    /**
     * @param mftp_conn $connection
     * @param string $command
     * @param string null $args
     * @return mftp_response
     * Puts a command, then parses the response and returns it
     */
    function _mftp_perform_command($connection, $command, $args = null) {
        _mftp_put_cmd($connection, $command, $args);

        return _mftp_parse_response(_mftp_read_last_line($connection, $connection->commandSocket));
    }

    /**
     * @param string $message
     * @param mftp_response $response
     * @param string null $path
     * @throws MFTPException
     * @throws MFTPNoSuchRemoteFileException
     * @throws MFTPRemoteFileException
     * @throws MFTPRemoteFileExistsException
     * @throws MFTPRemoteFilePermissionDenied
     * Looks at the code and text of a response and converts it into a better exception
     */
    function _mftp_convert_response_to_exception($message, $response, $path = null) {
        $normalisedResponseText = strtolower($response->text);

        if ($response->code == 450)
            throw new MFTPRemoteFileException("$message. Could not access path: $path",
                $response->code, $path);

        if ($response->code == 552)
            throw new MFTPRemoteFileException("Quota exceeded.", $response->code, $path);

        if ($response->code == 550 || $response->code == 553 || $response->code == 451) {
            if ($response->code == 451 || strpos($normalisedResponseText, "permission denied") !== FALSE)
                throw new MFTPRemoteFilePermissionDenied("$message: Permission denied at $path", $response->code,
                    $response->text, $path);

            if (strpos($normalisedResponseText, "file exists") !== FALSE)
                throw new MFTPRemoteFileExistsException("$message: File exists $path", $response->code,
                    $response->text, $path);

            if ($response->code == 550 || strpos($normalisedResponseText, "no such file") !== FALSE
                || strpos($normalisedResponseText, "doesn't exist") !== FALSE
                || strpos($normalisedResponseText, "failed to change directory") !== FALSE // this is a guess
                || strpos($normalisedResponseText, "folder not found") !== FALSE
                || strpos($normalisedResponseText, "not find") !== FALSE
                || strpos($normalisedResponseText, "not found") !== FALSE
            )
                throw new MFTPNoSuchRemoteFileException("$message: No such file or directory $path", $response->code,
                    $response->text, $path);

            // default -> error with remote file
            throw new MFTPRemoteFileException("$message (path: $path)", $response->code, $path);
        }

        throw new MFTPException("MFTP error", $response->code, $response->text, $path);
    }

    /**
     * @param mftp_conn $connection
     * @throws Exception
     * @throws MFTPException
     * @return array|bool
     * Gets a data socket ready for use, whether passive or active, return true if data connection already exists
     */
    function _mftp_prepare_data_socket($connection) {
        if (!is_null($connection->dataSocket))
            return true;

        if ($connection->isPassive)
            return _mftp_pasv_setup($connection);

        _mftp_active_setup($connection);
        return true;
    }

    /**
     * @param $connection
     * @throws MFTPException
     * Sets up listening socket and executes EPRT (if supported) or PORT command, does not accept the incoming
     * connection yet
     */
    function _mftp_active_setup($connection) {
        $commandSocketName = stream_socket_get_name($connection->commandSocket, false);

        if ($connection->isIpV6) {
            $splitCommandSocketName = explode(":", $commandSocketName);
            $splitLocalIp = array_slice($splitCommandSocketName, 0, count($splitCommandSocketName) - 1);
            $localIp = join(":", $splitLocalIp);
        } else {
            $splitCommandSocketName = explode(":", $commandSocketName);
            $localIp = $splitCommandSocketName[0];
        }

        $sock = stream_socket_server("tcp://" . _mftp_escape_ip_address($localIp) . ":0");

        $sockName = stream_socket_get_name($sock, false);

        $splitSockName = explode(":", $sockName);

        $port = $splitSockName[count($splitSockName) - 1];

        if (_mftp_connection_supports_eprt($connection)) {
            _mftp_perform_eprt_command($connection, $localIp, $port);
        } else {
            _mftp_perform_port_command($connection, $localIp, $port);
        }

        $connection->listeningSocket = $sock;
    }

    /**
     * @param $connection
     * @param $localIp
     * @param $port
     * @throws MFTPException
     * Send the command to set up a data socket using PORT
     */
    function _mftp_perform_port_command($connection, $localIp, $port) {
        $ipAddress = join(",", explode(".", $localIp));

        $p1 = floor($port / 256);
        $p2 = $port % 256;

        $portDescription = $ipAddress . ",$p1,$p2";

        _mftp_put_cmd($connection, "PORT", $portDescription);

        $resp = _mftp_parse_response(_mftp_read_line($connection, $connection->commandSocket));

        if ($resp->code != 200)
            throw new MFTPException("PORT command failed", $resp->code, $resp->text);
    }

    /**
     * @param $connection
     * @param $localIp
     * @param $port
     * @throws MFTPException
     * Send the command to set up a data socket using EPRT
     */
    function _mftp_perform_eprt_command($connection, $localIp, $port) {
        $ipVersionLookup = strpos($localIp, ":") === false ? "1" : "2";  // 1 = v4, 2 = v6

        $portDescription = "|$ipVersionLookup|$localIp|$port|";

        _mftp_put_cmd($connection, "EPRT", $portDescription);

        $resp = _mftp_parse_response(_mftp_read_line($connection, $connection->commandSocket));

        if ($resp->code != 200)
            throw new MFTPException("EPRT command failed", $resp->code, $resp->text);
    }

    /**
     * @param mftp_conn $connection
     * Closes the data connection and/or listening socket for an mftp_connection, then sets them to null. Will not fail
     * if they are already set to null
     */
    function _mftp_close_data_connection($connection) {
        if (!is_null($connection->dataSocket))
            fclose($connection->dataSocket);

        if (!is_null($connection->listeningSocket))
            fclose($connection->listeningSocket);

        $connection->dataSocket = null;
        $connection->listeningSocket = null;
    }

    /**
     * @param mftp_conn $connection
     * @throws MFTPException
     * Wait for incoming active connection from the FTP server; if it is a passive connection then just return
     * immediately
     */
    function _mftp_accept_data_connection($connection) {
        if ($connection->isPassive || !is_null($connection->dataSocket))
            return;

        $dataSocket = stream_socket_accept($connection->listeningSocket, $connection->timeout);

        if ($dataSocket === FALSE)
            throw new MFTPException("Listening for connection failed");

        $connection->dataSocket = $dataSocket;

        if ($connection->isSecure)
            _mftp_enable_crypto_on_socket($connection->dataSocket, $connection->commandSocket);
    }

    /**
     * @param mftp_conn $connection
     * @return string
     * @throws MFTPException
     * Read all data from data connection, in MFTP_BUFF_SIZE sized chunks. Not intended for use with reading files as
     * it all goes into memory, more useful for commands like LIST
     */
    function _mftp_read_data_connection($connection) {
        _mftp_accept_data_connection($connection);

        $fullBuffer = "";

        while ($buf = fread($connection->dataSocket, MFTP_BUFF_SIZE))
            $fullBuffer .= $buf;

        _mftp_close_data_connection($connection);

        return $fullBuffer;
    }

    /**
     * @param mftp_conn $connection
     * @return bool
     * Return true if the connection lists EPSV in its features output. Will do a FEAT command if the results of a
     * previous FEAT command have not been cached.
     */
    function _mftp_connection_supports_epsv($connection) {
        return !$connection->disableExtendedPassive && in_array("EPSV", mftp_features($connection));
    }

    /**
     * @param mftp_conn $connection
     * @return bool
     * Return true if the connection lists EPSV in its features output. Will do a FEAT command if the results of a
     * previous FEAT command have not been cached.
     */
    function _mftp_connection_supports_eprt($connection) {
        return in_array("EPRT", mftp_features($connection));
    }

    /**
     * @param $address
     * @return bool
     * Return true if the address is IPv4 (or a hostname, either way it does not need escaping)
     */
    function _mftp_address_is_ipv4($address) {
        return strpos($address, ".") !== false;
    }

    /**
     * @param $address
     * @return bool
     * Return true if the address is IPv6 in which case it should be surrounded by [] for use in URIs etc
     */
    function _mftp_address_is_ipv6($address) {
        return strpos($address, ":") !== false;
    }

    /**
     * @param $address
     * @return string
     * Wrap an IPv6 address in [], if it is and IPv4 address or hostname, do not change it
     */
    function _mftp_escape_ip_address($address) {
        return _mftp_address_is_ipv6($address) ? "[$address]" : $address;
    }