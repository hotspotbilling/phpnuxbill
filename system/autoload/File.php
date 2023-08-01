<?php

class File
{

    public static function copyFolder($from, $to, $exclude = [])
    {
        $files = scandir($from);
        print_r($files);
        foreach ($files as $file) {
            if (is_file($from . $file) && !in_array($file, $exclude)) {
                if (file_exists($to . $file)) unlink($to . $file);
                rename($from . $file, $to . $file);
            } else if (is_dir($from . $file) && !in_array($file, ['.', '..'])) {
                if (!file_exists($to . $file)) {
                    mkdir($to . $file);
                }
                File::copyFolder($from . $file . DIRECTORY_SEPARATOR, $to . $file . DIRECTORY_SEPARATOR, $exclude);
            }
        }
    }

    public static function deleteFolder($path)
    {
        $files = scandir($path);
        foreach ($files as $file) {
            if (is_file($path . $file)) {
                unlink($path . $file);
            } else if (is_dir($path . $file) && !in_array($file, ['.', '..'])) {
                File::deleteFolder($path . $file . DIRECTORY_SEPARATOR);
                rmdir($path . $file);
            }
        }
        rmdir($path);
    }


    /**
     * file path fixer
     *
     * @access public
     * @param string $path
     * @return string
     */
    public static function pathFixer($path)
    {
        return str_replace("/", DIRECTORY_SEPARATOR, $path);
    }
}
