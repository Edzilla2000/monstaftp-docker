<?php

    // To run diagnostics, please add your FTP credentials into the variables below.
    // To prevent unauthorized access, when you are finished with this diagnostic tool,
    // either remove your login details, or rename/delete this file.

    $ftpHost = "";                   // FTP host address
    $ftpPort = 21;                   // Host port (normally 21)
    $ftpUser = "";                   // FTP username
    $ftpPass = "";                   // FTP password
    $ftpPasv = 1;                    // Set to 1 for passive mode, or 0 for active mode
    $ftpDir = "";                    // Set a start folder (optional) i.e. /path/to/folder
    $ftpTmp = "";                    // Set a temp folder (optional), i.e. /path/to/folder
    $testFile = "mftp-test.html";    // File name used for creating test files

    // Path to MFTP library - update this if ftp-diagnostics.php is not in your mftp directory
    require_once(dirname(__FILE__) . "/application/api/file_sources/connection/mftp_functions.php");

    // Path to MFTP constants- update this if ftp-diagnostics.php is not in your mftp directory
    require_once(dirname(__FILE__) . "/application/api/constants.php");

    // Path to MFTP configuration file - update this if ftp-diagnostics.php is not in your mftp directory
    require_once(dirname(__FILE__) . "/settings/config.php");
?>

<html
<head>
    <title>PBL</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="shortcut icon" type="image/x-icon" href="application/frontend/images/monsta-logo-favicon.png">
    <link rel="apple-touch-icon" href="application/frontend/images/monsta-logo-webclip.png">
    <link href="//fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">

    <style type="text/css">
        body {
            font-family: 'Open Sans', sans-serif;
            font-size: 15px;
            text-rendering: optimizeLegibility;
        }

        td {
            padding: 10px 0;
        }

        .red {
            color: red;
        }

        .green {
            color: green;
        }
    </style>

</head>
<body>

<h2>Monsta FTP Diagnostics Tool</h2>

<?php
    $systype = "";
    $rawlist = "";
    $exceptionMessage = null;

    if ($ftpHost == "" || $ftpUser == "" || $ftpPass == "") {
        echo "Please open this file in a text editor and set your FTP credentials.";
    } else {
        $testArray = array();

        $testName = "Can create file on client server";
        $filename = createTempFileName($ftpTmp, $testFile);
        $testArray[$testName] = @file_put_contents($filename, "Hello World!");

        $testName = "Can delete file on client server";
        $testArray[$testName] = @unlink($filename);

        try {
            $testName = "Can connect to FTP server";
            $conn = mftp_connect($ftpHost, $ftpPort);

            $testArray[$testName] = $conn !== false;

            if ($conn === false)
                throw new Exception("Unable to connect to FTP server.");

            $testName = "Can log in to FTP server";
            mftp_login($conn, $ftpUser, $ftpPass);

            $testArray[$testName] = 1;

            $systype = mftp_get_systype($conn);

            if ($ftpPasv) {
                $testName = "Can set FTP mode to passive";
                mftp_pasv($conn, true);
                $testArray[$testName] = 1;
            }

            if ($ftpDir != "") {
                $testName = "Can change folder on FTP server";
                mftp_chdir($conn, $ftpDir);
                $testArray[$testName] = 1;
            }

            $testName = "Can upload a file to FTP server";
            $filename = createTempFileName($ftpTmp, $testFile);
            file_put_contents($filename, "Hello World!");
            mftp_put($conn, "/" . $testFile, $filename, FTP_BINARY);
            $testArray[$testName] = 1;

            $testName = "Can delete a file from FTP server";
            $filename = createTempFileName($ftpTmp, $testFile);
            file_put_contents($filename, "Hello World!");
            mftp_delete($conn, "/" . $testFile);
            $testArray[$testName] = 1;

            $rawlist = join("\n", mftp_rawlist($conn, "-a"));

        } catch (Exception $e) {
            $exceptionOccurred = true;
            $exceptionMessage = $e->getMessage();
            $testArray[$testName] = 0;
        }
        ?>

        Need help? Please <a href="http://redirect.monstaftp.com/contact"> contact
            us</a> and include the URL of this page.<br>

        <br>
        <table>
            <tr>
                <td><strong>Setting</strong></td>
                <td width="50"></td>
                <td><strong>Value</strong></td>
            </tr>
            <tr>
                <td>PHP Version:</td>
                <td></td>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <td>Magic Quotes:</td>
                <td></td>
                <td>
                    <?php
                        if (get_magic_quotes_gpc())
                            echo "On (<a href='http://redirect.monstaftp.com/magic-quotes'>please disable</a>)";
                        else
                            echo "Off";
                    ?>
                </td>
            </tr>
            <tr>
                <td>Client Platform:</td>
                <td></td>
                <td>
                    <?php
                        if (strtoupper(substr(PHP_OS, 0, 5)) == 'LINUX')
                            echo "Linux";
                        elseif (strtoupper(substr(PHP_OS, 0, 7)) == 'FREEBSD')
                            echo "FreeBSD";
                        elseif (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
                            echo "Windows";
                        else
                            echo PHP_OS;
                    ?>
                </td>
            </tr>
            <tr>
                <td>FTP Server Platform:</td>
                <td></td>
                <td>
                    <?php
                        if ($systype != "")
                            echo ucwords(strtolower($systype));
                        else
                            echo "Couldn't determine";
                    ?>
                </td>
            </tr>
            <tr>
                <td>OpenSSL Installed:</td>
                <td></td>
                <td>
                    <?php if (function_exists("openssl_get_publickey")) { ?>
                        <span class="green">Yes</span>
                    <?php } else {?>
                        <span class="no">No</span>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <td>Zip Extension Loaded:</td>
                <td></td>
                <td>
                    <?php if (extension_loaded('zip')) { ?>
                        <span class="green">Yes</span>
                    <?php } else {?>
                        <span class="no">No</span>
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <td>PHP Safe Mode:</td>
                <td></td>
                <td>
                    <?php if (ini_get('safe_mode')) { ?>
                        On
                    <?php } else {?>
                        Off
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <td>Suhosin Installed:</td>
                <td></td>
                <td>
                    <?php if (extension_loaded('suhosin')) { ?>
                        Yes
                    <?php } else {?>
                        No
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <td>Memory Limit:</td>
                <td></td>
                <td>
                    <?php echo ini_get('memory_limit')?>
                </td>
            </tr>
            <tr>
                <td>PHP Max Execution Time:</td>
                <td></td>
                <td>
                    <?php echo ini_get('max_execution_time'); ?> seconds
                </td>
            </tr>
        </table>

        <br>
        <table>
            <tr>
                <td><strong>Test</strong></td>
                <td width="50"></td>
                <td><strong>Result</strong></td>
            </tr>
            <?php
                foreach ($testArray as $key => $value) {
                    $class = $value ? "green" : "red";
                    $value = $value ? "Passed" : "Failed";
                    ?>
                    <tr>
                        <td><?php echo $key; ?></td>
                        <td></td>
                        <td class="<?php echo $class; ?>"><?php echo $value; ?></td>
                    </tr>
                    <?php
                }

                if (!is_null($exceptionMessage)) { ?>
                    <tr>
                        <td>
                            Last Exception:
                        </td>
                        <td></td>
                        <td>
                            <?php echo $exceptionMessage ?>
                        </td>
                    </tr>
                <?php } ?>
        </table>
    <?php
        echo "<br>FTP Raw Output:";
        echo "<br><textarea cols=100 rows=10>" . trim($rawlist) . "</textarea>";
    }

    function createTempFileName($ftpTmp, $filename) {
        if ($ftpTmp == "")
            $ftpTmp = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();

        return tempnam($ftpTmp, $filename);
    }
?>
</body>
</html>
