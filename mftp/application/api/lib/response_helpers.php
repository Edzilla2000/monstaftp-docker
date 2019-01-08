<?php

    require_once(dirname(__FILE__) . "/../constants.php");
    require_once(dirname(__FILE__) . "/LocalizableException.php");

    function handleExceptionInRequest($exception) {
        if (MONSTA_DEBUG)
            error_log($exception->getTraceAsString());

        if(!headers_sent())
            header('HTTP/1.1 577 Monsta Exception', true, 577);
            // Custom code so we can determine if it's an exception we've handled. 77 is M in ASCII

        $errResponse = array(
            'errors' => array($exception->getMessage())
        );

        if (is_a($exception, "LocalizableException")) {
            $errResponse['localizedErrors'] = array(
                array(
                    "errorName" => LocalizableExceptionCodeLookup::codeToName($exception->getCode()),
                    "context" => $exception->getContext()
                )
            );
        }

        print json_encode($errResponse);
        exit();
    }

    function exitWith404($error = null) {
        header('HTTP/1.1 404 Not Found', true, 404);

        if(!is_null($error)){
            header("Content-type: text/plain");
            print($error);
        }

        exit();
    }

    function dieIfNotPOST() {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            header('HTTP/1.1 405 Method Not Allowed', true, 405);
            header("Allow: POST");
            exit();
        }
    }