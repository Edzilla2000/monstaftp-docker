<?php

    class PathOperations {
        public static function join() {
            $pathComponents = array();

            foreach (func_get_args() as $pathComponent) {
                if ($pathComponent !== '') {
                    if (substr($pathComponent, 0, 1) == '/')
                        $pathComponents = array();  // if we're back at the root then reset the array
                    $pathComponents[] = $pathComponent;
                }

            }

            return preg_replace('#/+#', '/', join('/', $pathComponents));
        }

        public static function normalize($path) {
            $pathComponents = array();
            $realPathComponentFound = false;  // ..s should be resolved only if they aren't leading the path
            $pathPrefix = substr($path, 0, 1) == '/' ? '/' : '';

            foreach (explode("/", $path) as $pathComponent) {
                if (strlen($pathComponent) == 0 || $pathComponent == '.')
                    continue;

                if ($pathComponent == '..' && $realPathComponentFound) {
                    unset($pathComponents[count($pathComponents) - 1]);
                    continue;
                }

                $pathComponents[] = $pathComponent;
                $realPathComponentFound = true;
            }

            return $pathPrefix . join("/", $pathComponents);
        }

        public static function directoriesMatch($dir1, $dir2) {
            return PathOperations::normalize($dir1) == PathOperations::normalize($dir2);
        }

        public static function remoteDirname($path) {
            // on windows machines $dirName will be \ for root files, we want it to be / for remote paths
            $dirName = dirname($path);
            return ($dirName == "\\") ? "/" : $dirName;
        }

        public static function directoriesInPath($directoryPath) {
            $directories = array();
            while ($directoryPath != "/" && $directoryPath != null && $directoryPath != "") {
                $directories[] = $directoryPath;
                $directoryPath = self::remoteDirname($directoryPath);
            }

            return $directories;
        }

        public static function ensureTrailingSlash($path) {
            if (strlen($path) == 0)
                return "/";

            if (substr($path, strlen($path) - 1, 1) != "/")
                $path .= "/";

            return $path;
        }

        public static function isParentPath($parent, $child) {
            $normalizedChild = self::ensureTrailingSlash(self::normalize($child));
            $normalizedParent = self::ensureTrailingSlash(self::normalize($parent));

            return substr($normalizedChild, 0, strlen($normalizedParent)) == $normalizedParent;
        }

        public static function getFirstPathComponent($path) {
            $pathWithoutLeadingSlashes = preg_replace("|^(/+)|", "", $path);

            $pathComponents = explode("/", $pathWithoutLeadingSlashes);
            return (count($pathComponents) == 0) ? "" : $pathComponents[0];
        }

        public static function stripTrailingSlash($path) {
            return substr($path, -1) === "/" ? substr($path, 0, -1) : $path;
        }

        public static function recursiveDelete($path) {
            if (is_dir($path) === true) {
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::CHILD_FIRST);

                foreach ($files as $file) {
                    if (in_array($file->getBasename(), array('.', '..')) !== TRUE) {
                        $removeSuccess = true;

                        if ($file->isDir() === TRUE) {
                            $removeSuccess = @rmdir($file->getPathName());
                        } else if (($file->isFile() === TRUE) || ($file->isLink() === TRUE)) {
                            $removeSuccess = @unlink($file->getPathname());
                        }

                        if(!$removeSuccess)
                            return false;
                    }
                }

                return @rmdir($path);
            } else if ((is_file($path) === TRUE) || (is_link($path) === TRUE)) {
                return @unlink($path);
            }

            return false;
        }

        public static function pathDepthCompare($path1, $path2) {
            // naive function for ordering paths based on their depth; more slashes == deeper
            $slashCount1 = substr_count($path1, "/");
            $slashCount2 = substr_count($path2, "/");

            if ($slashCount1 == $slashCount2)
                return strcasecmp($path1, $path2); // if same depth, just alphabetical sort. doesn't really matter

            return ($slashCount1 > $slashCount2) ? -1 : 1;
        }
    }

    function pathDepthCompare($path1, $path2) {
        // Wrap the static method above, as PHP doesn't understand passing a static method to usort
        return PathOperations::pathDepthCompare($path1, $path2);
    }