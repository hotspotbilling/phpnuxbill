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

        $image($dst_img, $dst_dir, $quality);

        if ($dst_img) imagedestroy($dst_img);
        if ($src_img) imagedestroy($src_img);
        return file_exists($dst_dir);
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
