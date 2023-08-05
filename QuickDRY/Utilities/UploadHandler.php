<?php

namespace QuickDRY\Utilities;

/*
 * jQuery File Upload Plugin PHP Class 5.11.1
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

use JetBrains\PhpStorm\ArrayShape;
use QuickDRYInstance\Common\FileClass;

/**
 * Class UploadHandler
 */
class UploadHandler
{
    protected array $options;

    /**
     * @param null $options
     */
    public function __construct($options = null)
    {
        $this->options = [
            'script_url' => '/',
            'upload_dir' => dirname($_SERVER['SCRIPT_FILENAME']) . '/files/',
            'upload_url' => '/',
            'param_name' => 'files',
            // Set the following option to 'POST', if your server does not support
            // DELETE requests. This is a parameter sent to the client:
            'delete_type' => 'DELETE',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            'min_file_size' => 1,
            'accept_file_types' => '/.+$/i',
            // The maximum number of files for the upload directory:
            'max_number_of_files' => null,
            // Image resolution restrictions:
            'max_width' => null,
            'max_height' => null,
            'min_width' => 1,
            'min_height' => 1,
            // Set the following option to false to enable resumable uploads:
            'discard_aborted_uploads' => true,
            // Set to true to rotate images based on EXIF meta data, if available:
            'orient_image' => false,
        ];
        if ($options) {
            $this->options = array_replace_recursive($this->options, $options);
        }
    }

    /**
     * @param $matches
     *
     * @return string
     */
    protected function upcount_name_callback($matches): string
    {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext = $matches[2] ?? '';
        return ' (' . $index . ')' . $ext;
    }

    /**
     * @param $name
     *
     * @return array|string|string[]|null
     */
    protected function upcount_name($name): array|string|null
    {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            [$this, 'upcount_name_callback'],
            $name,
            1
        );
    }

    /**
     * @param $name
     * @param $type
     * @return string
     */
    protected function trim_file_name($name, $type): string
    {
        // Remove path information and dots around the filename, to prevent uploading
        // into different directories or replacing hidden system files.
        // Also remove control characters and spaces (\x00..\x20) around the filename:
        $file_name = trim(basename(stripslashes($name)), ".\x00..\x20");
        // Add missing file extension for known image types:
        if (!str_contains($file_name, '.') &&
            preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
            $file_name .= '.' . $matches[1];
        }
        if ($this->options['discard_aborted_uploads']) {
            while (is_file($this->options['upload_dir'] . $file_name)) {
                $file_name = $this->upcount_name($file_name);
            }
        }
        return $file_name;
    }

    /**
     * @param $uploaded_file
     * @param $name
     * @param $size
     * @param $type
     * @param $entity_id
     * @param $entity_type_id
     *
     * @return array
     */
    #[ArrayShape(['url' => 'string', 'thumbnail_url' => 'string', 'name' => 'mixed', 'size' => 'mixed', 'id' => 'mixed', 'entity_id' => 'mixed', 'entity_type_id' => 'mixed'])] protected function handle_file_upload($uploaded_file, $name, $size, $type, $entity_id, $entity_type_id): array
    {
        $fileArray = [
            'name' => $name,
            'type' => $type,
            'tmp_name' => $uploaded_file,
            'size' => $size,
            'entity_id' => $entity_id,
            'entity_type_id' => $entity_type_id,
        ];
        return self::UploadFiles($fileArray);
    }


    /**
     * @param null $entity_id
     * @param null $entity_type_id
     */
    public function post($entity_id = null, $entity_type_id = null): void
    {
        if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE') {
            return;
        }
        $upload = $_FILES[$this->options['param_name']] ?? null;
        $info = [];
        if ($upload && is_array($upload['tmp_name'])) {
            // param_name is an array identifier like "files[]",
            // $_FILES is a multi-dimensional array:
            foreach ($upload['tmp_name'] as $index => $value) {
                $info[] = $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    $_SERVER['HTTP_X_FILE_NAME'] ?? $upload['name'][$index],
                    $_SERVER['HTTP_X_FILE_SIZE'] ?? $upload['size'][$index],
                    $_SERVER['HTTP_X_FILE_TYPE'] ?? $upload['type'][$index],
                    $entity_id,
                    $entity_type_id
                );
            }
        } elseif ($upload || isset($_SERVER['HTTP_X_FILE_NAME'])) {
            // param_name is a single object identifier like "file",
            // $_FILES is a one-dimensional array:
            $info[] = $this->handle_file_upload(
                $upload['tmp_name'] ?? null,
                $_SERVER['HTTP_X_FILE_NAME'] ?? ($upload['name'] ?? null),
                $_SERVER['HTTP_X_FILE_SIZE'] ?? ($upload['size'] ?? null),
                $_SERVER['HTTP_X_FILE_TYPE'] ?? ($upload['type'] ?? null),
                $entity_id,
                $entity_type_id
            );
        }
        header('Vary: Accept');
        $json = json_encode($info);
        $redirect = isset($_REQUEST['redirect']) ?
            stripslashes($_REQUEST['redirect']) : null;
        if ($redirect) {
            header('Location: ' . sprintf($redirect, rawurlencode($json)));
            return;
        }
        if (isset($_SERVER['HTTP_ACCEPT']) &&
            (str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }
        echo $json;
    }

    /**
     * @param array $fileArray
     * @param int|null $user_id
     * @return array
     */
    #[ArrayShape(['url' => 'string', 'thumbnail_url' => 'string', 'name' => 'mixed', 'size' => 'mixed', 'id' => 'mixed', 'entity_id' => 'mixed', 'entity_type_id' => 'mixed'])] public static function UploadFiles(array $fileArray, int $user_id = null): array
    {
        $file = new FileClass();
        $file->user_id = $user_id;
        $file->file_name = $fileArray['name'];
        $file->file_type = $fileArray['type'];
        $file->file_hash = md5_file($fileArray['tmp_name']);
        $file->file_size = $fileArray['size'];
        $file->file_ext = explode('.', $file->file_name);
        $file->file_ext = $file->file_ext[sizeof($file->file_ext) - 1];
        $file->created_at = Dates::Timestamp();
        if ($fileArray['entity_id'] && $fileArray['entity_type_id']) {
            $file->entity_id = $fileArray['entity_id'];
            $file->entity_type = $fileArray['entity_type_id'];
        }
        $id = $file->Save();

        if (!file_exists($file->server_location)) {
            move_uploaded_file($fileArray['tmp_name'], $file->server_location);
        }

        return [
            'url' => '',
            'thumbnail_url' => '',
            'name' => $file->file_name,
            'size' => $file->file_size,
            'id' => $id,
            'entity_id' => $fileArray['entity_id'],
            'entity_type_id' => $fileArray['entity_type_id'],
        ];
    }
}

