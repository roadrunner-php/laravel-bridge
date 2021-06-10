<?php

namespace Symfony\Component\HttpFoundation\File {

    /**
     * ***************************************************************************
     * ******* WARNING **** WARNING **** WARNING **** WARNING **** WARNING *******.
     * ***************************************************************************
     * *******                                                             *******
     * *******        THIS FUNCTION OVERLOADING IS NECESSARY MEASURE       *******
     * *******    https://github.com/spiral/roadrunner-laravel/issues/43   *******
     * *******                                                             *******
     * ***************************************************************************.
     *
     * Moves an uploaded file to a new location.
     *
     * @link  https://php.net/manual/en/function.move-uploaded-file.php
     *
     * @param string $from The filename of the uploaded file
     * @param string $to   The destination of the moved file
     *
     * @return bool If filename is a valid file, but cannot be moved for some
     *              reason, no action will occur, and will return false.
     *
     * @see   \Symfony\Component\HttpFoundation\File\UploadedFile::move
     *
     * @since 4.0.3
     * @since 5.0
     * @since 7.0
     * @since 8.0
     */
    function move_uploaded_file(string $from, string $to): bool
    {
        return \is_file($from) && \rename($from, $to);
    }
}
