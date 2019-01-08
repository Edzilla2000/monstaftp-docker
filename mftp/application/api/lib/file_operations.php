<?php

    function mftpFileGetContents($path){
        $realPath = @realpath($path);

        if($realPath !== FALSE) {
            $path = $realPath;
        }

        if($realPath === FALSE || !@file_exists($path)) {
            throw new Exception("Unable to read file at $path, the file does not appear to exist.");
        }

        if(@is_dir($path)) {
            throw new Exception("Unable to read contents of $path as it is a directory.");
        }

        if(!@is_readable($path)) {
            throw new Exception("$path is not readable.");
        }

        return @file_get_contents($path);
    }