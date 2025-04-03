<?php
namespace App\Service\voyages;

class ImagePathResolver
{
    private string $projectDir;
    private string $baseUrl;
    private bool $isWebEnvironment;

    public function __construct(
        string $projectDir,
        string $baseUrl,
        bool $isWebEnvironment = true
    ) {
        $this->projectDir = $projectDir;
        $this->baseUrl = $baseUrl;
        $this->isWebEnvironment = $isWebEnvironment;
    }

    public function getFullPath(string $imageName): string
    {
        if ($this->isWebEnvironment) {
            return '/uploads/images/'.$imageName;
        } else {
            return $this->projectDir.'/public/uploads/images/'.$imageName;
        }
    }

    public function resolvePaths(string $pathImages): array
    {
        $imageNames = explode('***', $pathImages);
        return array_map([$this, 'getFullPath'], $imageNames);
    }
}