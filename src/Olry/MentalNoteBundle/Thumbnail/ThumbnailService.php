<?php

namespace Olry\MentalNoteBundle\Thumbnail;

use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Olry\MentalNoteBundle\Url\MetaInfo;

class ThumbnailService
{
    private $documentRoot;
    private $filepattern;
    private $cacheDir;
    private $fs;

    public function __construct($documentRoot, $cacheDir, $filepattern)
    {
        $this->documentRoot = $documentRoot;
        $this->cacheDir     = $cacheDir;
        $this->filepattern  = $filepattern;
        $this->fs           = new Filesystem();
    }

    private function compilePattern($width, $height, $name)
    {
        $search  = array('{width}', '{height}', '{name}');
        $replace = array($width, $height, $name);

        return str_replace($search, $replace, $this->filepattern);
    }

    /**
     * @param string $file
     */
    public function getImageForUrl($url, $file)
    {
        $metainfo = new MetaInfo($url);

        $imageUrl = $metainfo->getImageUrl();
        if ($imageUrl) {
            // todo use something more sophisticated, e.g. guzzle
            file_put_contents($file, file_get_contents($imageUrl));

            return;
        }

        $cmd = "xvfb-run --auto-servernum --server-args='-screen 0, 1024x768x24' cutycapt --url=%s --out=%s";
        $process = new Process(sprintf($cmd,
            \escapeshellarg($url),
            \escapeshellarg($file . '.png'))
        );

        $process
            ->setTimeout(60)
            ->run()
        ;

        if ( ! $process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }

        rename($file . '.png', $file);

        return;
    }

    public function generate($url, $width, $height, $name)
    {
        $hash = md5($url);

        $thumbnail               = new Thumbnail();
        $thumbnail->url          = $url;
        $thumbnail->width        = $width;
        $thumbnail->height       = $height;
        $thumbnail->relativePath = $this->compilePattern($width, $height, $name);
        $thumbnail->absolutePath = $this->documentRoot . '/' . $thumbnail->relativePath;

        if ($this->fs->exists($thumbnail->absolutePath)) {
            return $thumbnail;
        }

        $tmpFile = $this->cacheDir . '/' . $hash;

        if ( ! $this->fs->exists($this->cacheDir)) {
            $this->fs->mkdir($this->cacheDir);
        }

        if (!$this->fs->exists($tmpFile)) {
            $this->getImageForUrl($url, $tmpFile);
        }

        if ( ! $this->fs->exists(dirname($thumbnail->absolutePath))) {
            $this->fs->mkdir(dirname($thumbnail->absolutePath));
        }

        $cmd = "convert %s[0] -resize '%dx%d^' -gravity center -crop %dx%d+0+0 +repage %s";

        $process = new Process(
            sprintf($cmd,
                $tmpFile,
                $width, $height,
                $width, $height,
                $thumbnail->absolutePath)
        );
        $process->run();

        if ( ! $process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }

        return $thumbnail;
    }
}

