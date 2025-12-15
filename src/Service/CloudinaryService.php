<?php

namespace App\Service;

use Cloudinary\Cloudinary;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CloudinaryService
{
    private Cloudinary $cloudinary;

    public function __construct(
        string $cloudName,
        string $apiKey,
        string $apiSecret
    ) {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key'    => $apiKey,
                'api_secret' => $apiSecret,
            ],
            'url' => [
                'secure' => true,
            ],
        ]);
    }

    /**
     * Upload une image vers Cloudinary (signature gérée automatiquement)
     */
    public function uploadImage(
        UploadedFile $file,
        string $folder = 'artworks/projects',
        array $options = []
    ): array {
        $uploadOptions = array_merge([
            'folder' => $folder,
            'resource_type' => 'image',
            'overwrite' => false,
            'unique_filename' => true,
   'transformation' => [
    [
        'fetch_format' => 'auto',
        'quality' => 'auto:good',
    ]
],

        ], $options);

        try {
            $result = $this->cloudinary->uploadApi()->upload(
                $file->getPathname(),
                $uploadOptions
            );

            return [
                'success' => true,
                'url' => $result['secure_url'],
                'public_id' => $result['public_id'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Supprimer un fichier Cloudinary
     */
    public function deleteFile(string $publicId, string $resourceType = 'image'): bool
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId, [
                'resource_type' => $resourceType,
            ]);

            return ($result['result'] ?? null) === 'ok';
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * URL optimisée d'image
     */
    public function getOptimizedUrl(string $publicId, int $width = 800, int $height = 600): string
    {
        return $this->cloudinary->image($publicId)
            ->resize(\Cloudinary\Transformation\Resize::fill($width, $height))
            ->delivery(\Cloudinary\Transformation\Quality::auto())
            ->delivery(\Cloudinary\Transformation\Format::auto())
            ->toUrl();
    }
}
