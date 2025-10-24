<?php

namespace App\Services\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class CustomPathGenerator implements PathGenerator
{
    public const FOLDER_PREFIX_KEY = 'folder_prefix';

    public const SOURCE_URL = 'source_url';

    public const FILE_HASH_KEY = 'file_hash';

    public const FOLDER_NAME_UNSET = '_unset';

    /*
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media).'/';
    }

    /*
     * Get the path for conversions of the given media, relative to the root storage path.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media).'/conversions/';
    }

    /*
     * Get the path for responsive images of the given media, relative to the root storage path.
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media).'/responsive-images/';
    }

    /*
     * Get a unique base path for the given media.
     */
    protected function getBasePath(Media $media): string
    {
        $collectionName = $media->collection_name !== '' ? $media->collection_name.'/' : '';
        $parts = explode('\\', $media->model_type);
        $modelFolder = end($parts).'/';

        $prefix = config('media-library.prefix', '');

        if ($prefix !== '') {
            $prefix .= '/';
        }

        $modelId = $media->model_id;
        if ($media->hasCustomProperty(self::FOLDER_PREFIX_KEY)) {
            $modelId = $media->getCustomProperty(self::FOLDER_PREFIX_KEY);
        }

        return $prefix.$modelFolder.$collectionName.$modelId;
    }

    public static function convertFilename(?string $filename): string
    {
        $filename = trim($filename);

        if (empty($filename)) {
            return self::FOLDER_NAME_UNSET;
        }

        $filename = str_replace('%23', '-', $filename);

        // allow only alphanumeric characters, underscore, dash, and dot
        return preg_replace('/[^a-zA-Z0-9_\-.]/', '', $filename);
    }
}
