<?php
// src/Service/ImageUploader.php
namespace App\Service\hebergements;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ImageUploader
{
    public function __construct(
        private string $uploadDirectory,
        private SluggerInterface $slugger
    ) {}

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = 'chambre_'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->uploadDirectory, $fileName);
        } catch (FileException $e) {
            throw new \RuntimeException('Failed to upload file: '.$e->getMessage());
        }

        return '/chambreImages/'.$fileName;
    }
}