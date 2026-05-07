<?php

declare(strict_types=1);

namespace Framework\Filesystem;

class Image
{
    private $image;
    private string $type;
    private int $width;
    private int $height;

    public function __construct(string $source)
    {
        $info = getimagesize($source);
        if ($info === false) {
            throw new \RuntimeException("Unable to read image: {$source}");
        }

        $this->width = $info[0];
        $this->height = $info[1];
        $this->type = $info['mime'];

        $this->image = match ($this->type) {
            'image/jpeg' => imagecreatefromjpeg($source),
            'image/png' => imagecreatefrompng($source),
            'image/gif' => imagecreatefromgif($source),
            'image/webp' => imagecreatefromwebp($source),
            default => throw new \RuntimeException("Unsupported image type: {$this->type}"),
        };
    }

    public static function make(string $source): self
    {
        return new self($source);
    }

    public function resize(int $width, int $height): self
    {
        $newImage = imagecreatetruecolor($width, $height);

        if ($this->type === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        imagecopyresampled(
            $newImage,
            $this->image,
            0, 0, 0, 0,
            $width,
            $height,
            $this->width,
            $this->height
        );

        imagedestroy($this->image);
        $this->image = $newImage;
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    public function crop(int $width, int $height, int $x = 0, int $y = 0): self
    {
        $newImage = imagecreatetruecolor($width, $height);

        if ($this->type === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        imagecopyresampled(
            $newImage,
            $this->image,
            0, 0, $x, $y,
            $width,
            $height,
            $width,
            $height
        );

        imagedestroy($this->image);
        $this->image = $newImage;
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    public function fit(int $width, int $height): self
    {
        $origRatio = $this->width / $this->height;
        $targetRatio = $width / $height;

        if ($origRatio > $targetRatio) {
            $cropWidth = (int)($this->height * $targetRatio);
            $cropHeight = $this->height;
        } else {
            $cropWidth = $this->width;
            $cropHeight = (int)($this->width / $targetRatio);
        }

        $x = (int)(($this->width - $cropWidth) / 2);
        $y = (int)(($this->height - $cropHeight) / 2);

        $this->crop($cropWidth, $cropHeight, $x, $y);

        return $this->resize($width, $height);
    }

    public function width(int $width): self
    {
        $height = (int)($this->height * ($width / $this->width));
        return $this->resize($width, $height);
    }

    public function height(int $height): self
    {
        $width = (int)($this->width * ($height / $this->height));
        return $this->resize($width, $height);
    }

    public function quality(int $quality): self
    {
        // Quality is applied on save
        return $this;
    }

    public function encode(string $type = 'jpeg'): self
    {
        // Convert image type
        if ($type !== $this->type) {
            $newImage = imagecreatetruecolor($this->width, $this->height);

            if ($type === 'image/png') {
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
            }

            imagecopy($newImage, $this->image, 0, 0, 0, 0, $this->width, $this->height);

            imagedestroy($this->image);
            $this->image = $newImage;
            $this->type = match ($type) {
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                default => 'image/jpeg',
            };
        }

        return $this;
    }

    public function save(string $path, int $quality = 90): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $result = match ($this->type) {
            'image/jpeg' => imagejpeg($this->image, $path, $quality),
            'image/png' => imagepng($this->image, $path, 9 - (int)($quality / 10)),
            'image/gif' => imagegif($this->image, $path),
            'image/webp' => imagewebp($this->image, $path, $quality),
            default => false,
        };

        $this->destroy();

        return $result;
    }

    public function stream(?string $type = null, int $quality = 90): string
    {
        ob_start();

        $type = $type ?? $this->type;

        match ($type) {
            'image/jpeg' => imagejpeg($this->image, null, $quality),
            'image/png' => imagepng($this->image),
            'image/gif' => imagegif($this->image),
            'image/webp' => imagewebp($this->image, null, $quality),
            default => throw new \RuntimeException("Unsupported image type: {$type}"),
        };

        $content = ob_get_clean();

        return $content;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function destroy(): void
    {
        if ($this->image) {
            imagedestroy($this->image);
        }
    }

    public function __destruct()
    {
        $this->destroy();
    }
}

class ImageServer
{
    private string $cachePath;
    private int $maxWidth;
    private int $maxHeight;

    public function __construct(array $config = [])
    {
        $this->cachePath = $config['cache_path'] ?? storage_path('app/cache/images');
        $this->maxWidth = $config['max_width'] ?? 2000;
        $this->maxHeight = $config['max_height'] ?? 2000;

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0755, true);
        }
    }

    public function handle(string $source, array $params): string
    {
        $cacheKey = $this->getCacheKey($source, $params);
        $cacheFile = $this->cachePath . '/' . $cacheKey;

        if (is_file($cacheFile)) {
            return $cacheFile;
        }

        $image = Image::make($source);

        if (isset($params['w'])) {
            $width = min((int)$params['w'], $this->maxWidth);
            if (isset($params['h'])) {
                $height = min((int)$params['h'], $this->maxHeight);
                if (($params['fit'] ?? false) === true) {
                    $image->fit($width, $height);
                } else {
                    $image->resize($width, $height);
                }
            } else {
                $image->width($width);
            }
        } elseif (isset($params['h'])) {
            $height = min((int)$params['h'], $this->maxHeight);
            $image->height($height);
        }

        if (isset($params['q'])) {
            $quality = (int)$params['q'];
        } else {
            $quality = 90;
        }

        $image->save($cacheFile, $quality);

        return $cacheFile;
    }

    private function getCacheKey(string $source, array $params): string
    {
        $params['source'] = $source;
        $params['mtime'] = filemtime($source);
        return md5(json_encode($params)) . '.' . pathinfo($source, PATHINFO_EXTENSION);
    }

    public function clearCache(): bool
    {
        $files = glob($this->cachePath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }
}
