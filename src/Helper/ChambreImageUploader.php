<?php 

namespace App\Helper;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ChambreImageUploader
{
    private $uploadDir;
    private $slugger;

    public function __construct(string $projectDir, SluggerInterface $slugger)
    {
        $this->uploadDir = $projectDir.'/public/chambreImages/';
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $file->move($this->uploadDir, $fileName);

        return '/chambreImages/'.$fileName;
    }

    public function delete(string $filePath): void
    {
        $fullPath = $this->uploadDir.'/'.basename($filePath);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}