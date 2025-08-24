<?php

/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/
class File
{

    public static function copyFolder($from, $to, $exclude = [])
    {
        $files = scandir($from);
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

    public static function resizeCropImage($source_file, $dst_dir, $max_width, $max_height, $quality = 80)
    {
        $imgsize = getimagesize($source_file);
        $width = $imgsize[0];
        $height = $imgsize[1];
        $mime = $imgsize['mime'];

        switch ($mime) {
            case 'image/gif':
                $image_create = "imagecreatefromgif";
                $image = "imagegif";
                break;

            case 'image/png':
                $image_create = "imagecreatefrompng";
                $image = "imagepng";
                $quality = 7;
                break;

            case 'image/jpeg':
                $image_create = "imagecreatefromjpeg";
                $image = "imagejpeg";
                $quality = 80;
                break;

            default:
                return false;
                break;
        }

        if ($max_width == 0) {
            $max_width  = $width;
        }

        if ($max_height == 0) {
            $max_height = $height;
        }

        $widthRatio = $max_width / $width;
        $heightRatio = $max_height / $height;
        $ratio = min($widthRatio, $heightRatio);
        $nwidth  = (int)$width  * $ratio;
        $nheight = (int)$height * $ratio;

        $dst_img = imagecreatetruecolor($nwidth, $nheight);
        $white = imagecolorallocate($dst_img, 255, 255, 255);
        imagefill($dst_img, 0, 0, $white);
        $src_img = $image_create($source_file);
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $nwidth, $nheight, $width, $height);

        imagepng($dst_img, $dst_dir);

        if ($dst_img) imagedestroy($dst_img);
        if ($src_img) imagedestroy($src_img);
        return file_exists($dst_dir);
    }

    public static function makeThumb($srcFile, $thumbFile, $thumbSize = 200)
    {
        /* Determine the File Type */
        $type = substr($srcFile, strrpos($srcFile, '.') + 1);
        $imgsize = getimagesize($srcFile);
        $oldW = $imgsize[0];
        $oldH = $imgsize[1];
        $mime = $imgsize['mime'];
        switch ($mime) {
            case 'image/gif':
                $src = imagecreatefromgif($srcFile);
                break;

            case 'image/png':
                $src = imagecreatefrompng($srcFile);
                break;

            case 'image/jpeg':
                $src = imagecreatefromjpeg($srcFile);
                break;

            default:
                return false;
                break;
        }
        /* Calculate the New Image Dimensions */
        $limiting_dim = 0;
        if ($oldH > $oldW) {
            /* Portrait */
            $limiting_dim = $oldW;
        } else {
            /* Landscape */
            $limiting_dim = $oldH;
        }
        /* Create the New Image */
        $new = imagecreatetruecolor($thumbSize, $thumbSize);
        /* Transcribe the Source Image into the New (Square) Image */
        imagecopyresampled($new, $src, 0, 0, ($oldW - $limiting_dim) / 2, ($oldH - $limiting_dim) / 2, $thumbSize, $thumbSize, $limiting_dim, $limiting_dim);
        imagejpeg($new, $thumbFile, 100);
        imagedestroy($new);
        return file_exists($thumbFile);
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
