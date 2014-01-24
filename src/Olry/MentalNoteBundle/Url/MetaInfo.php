<?php
/*
 *
 * @author Tobias Olry <tobias.olry@web.de>
 */

namespace Olry\MentalNoteBundle\Url;

use Symfony\Component\DomCrawler\Crawler;
use Olry\MentalNoteBundle\Entity\Category;

class MetaInfo
{
    private $info;

    private $html;

    private $imageUrl = null;

    private static $videoDomains = array(
        'youtube',
        'vimeo'
    );

    private static $imageDomains = array(
        'imgur',
    );

    private static $musicDomains = array(
        'bandcamp',
    );

    private function translate($url)
    {
        $info = new Info($url);
        if ($info->host == 'i.imgur.com') {
            $this->imageUrl = $url;
            $newPath = str_replace('.' . $info->fileExtension, '', $info->path);
            return "http://imgur.com" . $newPath;
        }
        return $url;
    }

    public function __construct($url)
    {
        $url = $this->translate($url);

        $this->info = new Info($url);
        if ($this->info->isHtml()) {
            $this->html = file_get_contents($url);
        }
    }

    protected function getXpath($xpath)
    {
        if (!$this->info->isHtml()) {
            return null;
        }

        try {
            $crawler = new Crawler($this->html);
            return trim($crawler->filterXPath($xpath)->first()->text());
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    public function getImageUrl()
    {
        if ($this->imageUrl !== null) {
            return $this->imageUrl;
        }

        if ($this->info->isImage()) {
            return $this->info->url;
        }

        $xpaths = array(
            '//meta[@property="og:image"]/@content',
            '//link[@rel="image_src"]/@href',
            'id("comic")//img/@src',
        );
        foreach ($xpaths as $xpath){
            $result = $this->getXpath(array_shift($xpaths));
            if (!empty($result)) {
                return $result;
            }
        }

        return false;
    }

    public function getTitle()
    {
        return $this->getXpath('//head/title');
    }

    public function getDefaultCategory()
    {
        if (in_array($this->info->sld, self::$videoDomains)) {
            return Category::WATCH;
        }

        if (in_array($this->info->sld, self::$imageDomains)) {
            return Category::LOOK_AT;
        }

        if (in_array($this->info->sld, self::$musicDomains)) {
            return Category::LISTEN;
        }

        return Category::READ;
    }

    /**
     * Get info.
     *
     * @return Olry\MentalNoteBundle\Ur\Info
     */
    public function getInfo()
    {
        return $this->info;
    }
}


