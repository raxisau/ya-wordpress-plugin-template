<?php

/** Upload.php - Utility functions
 *
 * ***********************************************************************
 * � Sloppycode.net All rights reserved.
 *
 * This is a standard copyright header for all source code appearing
 * at sloppycode.net. This application/class/script may be redistributed,
 * as long as the above copyright remains intact.
 * Comments to sloppycode@sloppycode.net
 * ***********************************************************************
 * Upload class - wrapper for uploading files. See accompanying docs
 *
 * @author C.Small <sloppycode@sloppycode.net>
 *
 * More features and better error checking will come in the next version
 *
 *
 * ** Revision History
 *  2-Jun-2009 B.Dutton    Removed radweb_include for auto load
 * 30-Jun-2003 Dutton      Checking into RADWEB 6.0
 * 04-Jan-2000 Dutton      Initial Coding
 *
 */
class Upload {

    /**
     *
     * @type var
     */
    private $maxupload_size;

    /**
     *
     * @type var
     */
    private $post_files;

    /**
     *
     * @type var
     */
    private $errors;

    /**
     * function to ...
     * @param $i Desc
     * @returns var
     * @public
     */
    function __construct() {
        global $_FILES;
        $this->post_files = $_FILES;
        $this->isPosted = false;
    }

    /**
     * function to ...
     * @param $i Desc
     * @param $i Desc
     * @param $i Desc
     * @param $i Desc
     * @returns var
     * @public
     */
    function save( $directory, $field, $overwrite, $mode = 0777 ) {
        $this->isPosted = true;
        if ( $this->post_files[$field]['size'] < $this->maxupload_size &&
                $this->post_files[$field]['size'] > 0 ) {
            $noerrors = true;
            $this->isPosted = true;
            // Get names
            $tempName = $this->post_files[$field]['tmp_name'];
            $file = $this->post_files[$field]['name'];
            $all = $directory . "/" . $file;

            // Copy to directory
            if ( file_exists( $all ) ) {
                if ( $overwrite ) {
                    @unlink( $all ) || $noerrors = false;
                    $this->errors = "Upload class save error: unable to overwrite " . $all . "<BR>";
                    @copy( $tempName, $all ) || $noerrors = false;
                    $this->errors .= "Upload class save error: unable to copy to " . $all . "<BR>";
                    @chmod( $all, $mode ) || $ernoerrorsrors = false;
                    $this->errors .= "Upload class save error: unable to change permissions for: " . $all . "<BR>";
                }
            }
            else {
                @copy( $tempName, $all ) || $noerrors = false;
                $this->errors = "Upload class save error: unable to copy to " . $all . "<BR>";
                @chmod( $all, $mode ) || $noerrors = false;
                $this->errors .= "Upload class save error: unable to change permissions for: " . $all . "<BR>";
            }
            return $noerrors;
        }
        else if ( $this->post_files[$field]['size'] > $this->maxupload_size ) {
            $this->errors = "File size exceeds maximum file size of " . $this->maxuploadsize . " bytes";
            return false;
        }
        else if ( $this->post_files[$field]['size'] == 0 ) {
            $this->errors = "File size is 0 bytes";
            return false;
        }
    }

    /**
     * function to ...
     * @param $i Desc
     * @param $i Desc
     * @param $i Desc
     * @param $i Desc
     * @param $i Desc
     * @returns var
     * @public
     */
    function saveAs( $filename, $directory, $field, $overwrite, $mode = 0777 ) {
        $this->isPosted = true;
        if ( $this->post_files[$field]['size'] < $this->maxupload_size &&
                $this->post_files[$field]['size'] > 0 ) {
            $noerrors = true;

            // Get names
            $tempName = $this->post_files[$field]['tmp_name'];
            $all = $directory . "/" . $filename;

            // Copy to directory
            if ( file_exists( $all ) ) {
                if ( $overwrite ) {
                    @unlink( $all ) || $noerrors = false;
                    $this->errors = "Upload class saveas error: unable to overwrite " . $all . "<BR>";
                    @copy( $tempName, $all ) || $noerrors = false;
                    $this->errors .= "Upload class saveas error: unable to copy to " . $all . "<BR>";
                    @chmod( $all, $mode ) || $noerrors = false;
                    $this->errors .= "Upload class saveas error: unable to copy to" . $all . "<BR>";
                }
            }
            else {
                @copy( $tempName, $all ) || $noerrors = false;
                $this->errors = "Upload class saveas error: unable to copy to " . $all . "<BR>";
                @chmod( $all, $mode ) || $noerrors = false;
                $this->errors .= "Upload class saveas error: unable to change permissions for: " . $all . "<BR>";
            }
            return $noerrors;
        }
        else if ( $this->post_files[$field]['size'] > $this->maxupload_size ) {
            $this->errors = "File size exceeds maximum file size of " . $this->maxuploadsize . " bytes";
            return false;
        }
        else if ( $this->post_files[$field]['size'] == 0 ) {
            $this->errors = "File size is 0 bytes";
            return false;
        }
    }

    /**
     * function to ...
     * @param $i Desc
     * @returns var
     * @public
     */
    function getFilename( $field ) {
        return $this->post_files[$field]['name'];
    }

    /**
     * function to ...
     * @param $i Desc
     * @returns var
     * @public
     */
    function getFileMimeType( $field ) {
        return $this->post_files[$field]['type'];
    }

    /**
     * function to ...
     * @param $i Desc
     * @returns var
     * @public
     */
    function getFileSize( $field ) {
        return $this->post_files[$field]['size'];
    }

    /**
     * function to ...
     * @param $i Desc
     * @returns var
     * @public
     */
    function deleteFile( $field ) {
        $all = $this->post_files[$field]['name'];
        if ( file_exists( $all ) )
            @unlink( $all );
    }

}
