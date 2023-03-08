<?php

class File
{

    public static function copyFolder($from, $to, $exclude = [])
    {
        echo "copyFolder($from, $to);<br>";
        $files = scandir($from);
        print_r($files);
        foreach ($files as $file) {
            if (is_file($from . $file) && !in_array($file, $exclude)) {
                if (file_exists($to . $file)) unlink($to . $file);
                rename($from . $file, $to . $file);
                echo "rename($from$file, $to$file);<br>";
            } else if (is_dir($from . $file) && !in_array($file, ['.', '..'])) {
                if (!file_exists($to . $file)) {
                    echo "mkdir($to$file);;<br>";
                    mkdir($to . $file);
                }
                echo "File::copyFolder($from$file, $to$file);<br>";
                File::copyFolder($from . $file . DIRECTORY_SEPARATOR, $to . $file . DIRECTORY_SEPARATOR);
            }
        }
    }

    public static function deleteFolder($path)
    {
        $files = scandir($path);
        foreach ($files as $file) {
            if (is_file($path . $file)) {
                echo "unlink($path$file);<br>";
                unlink($path . $file);
            } else if (is_dir($path . $file) && !in_array($file, ['.', '..'])) {
                File::deleteFolder($path . $file . DIRECTORY_SEPARATOR);
                echo "rmdir($path$file);<br>";
                rmdir($path . $file);
            }
        }
        echo "rmdir($path);<br>";
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
