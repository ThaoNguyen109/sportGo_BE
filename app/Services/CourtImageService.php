<?php

namespace App\Services;

use App\Models\Court;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CourtImageService
{
    private FileUploadService $fileUploadService;

    public function __construct(
        FileUploadService $fileUploadService
    ) {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Create many images
     */
    public function createImages(
        Court $court,
        array $images
    ): void {

        if (empty($images)) {
            return;
        }

        $data = array_map(function ($img) use ($court) {

            return [
                'court_id'   => $court->id,
                'image_url'  => $img,
                'created_at' => now(),
                'updated_at' => now(),
            ];

        }, $images);

        $court->images()->insert($data);
    }

    /**
     * Create single image
     */
    public function createImage(
        Court $court,
        string $path
    ) {

        return $court->images()->create([
            'image_url' => $path
        ]);
    }

    /**
     * Delete single image
     */
    public function deleteImage(
        Court $court,
        int $imageId
    ): void {

        $image = $court->images()
            ->find($imageId);

        if (!$image) {

            throw new HttpException(
                404,
                'Ảnh không tồn tại'
            );
        }

        // delete file storage
        $this->fileUploadService
            ->delete($image->image_url);

        // delete DB
        $image->delete();
    }
}