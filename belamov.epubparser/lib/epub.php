<?php

namespace belamov\EpubParser;

class Epub implements \Iterator
{
    private $meta;
    private $workingDir;
    private $content;

    private $currentItemIndex = 0;
    private $contentPath;
    private $dirForImages;

    public function __construct($filePath, $workingDir, $dirForImages)
    {
        $hash = md5($filePath . filesize($filePath));

        $this->workingDir = $workingDir . $hash . "/";
        $this->dirForImages = $dirForImages;
        $this->extractEpubFile($filePath);

        $container = new \SimpleXMLElement(file_get_contents($this->workingDir . "META-INF/container.xml"));
        $metaPath = (string)$container->rootfiles->rootfile['full-path'];
        $meta = new \SimpleXMLElement((file_get_contents($this->workingDir . $metaPath)));
        $this->contentPath = $this->workingDir . dirname($metaPath) . "/";

        $this->meta = new Meta($meta, $this->workingDir . dirname($metaPath) . "/");

        //html content
        $this->content = $this->meta->getContent();

        //save image files for further use
        $this->meta->saveImages($dirForImages);
    }

    private function extractEpubFile($filePath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            $zip->extractTo($this->workingDir);
            $zip->close();
        } else {
            throw new \Exception("Couldn't extract file: {$filePath}");
        }
    }

    public function cleanWorkingDir()
    {
        $this->rmrf($this->workingDir);
    }

    private function rmrf($target)
    {
        if (is_dir($target)) {
            $files = glob($target . '*', GLOB_MARK); //GLOB_MARK adds a slash to directories returned

            foreach ($files as $file) {
                $this->rmrf($file);
            }

            rmdir($target);
        } elseif (is_file($target)) {
            unlink($target);
        }
    }

    public function current()
    {
        $html = new \SimpleXMLElement(file_get_contents($this->content[$this->currentItemIndex]));
        $text = "";

        foreach ($html->body->children() as $child) {
            $text .= $child->asXml();
        }

        //replace images' sources
        //im not good with regexps :(
        $text = preg_replace(
            "/src=\"((\S)*)\"/",
            "src=\"" . str_replace($_SERVER['DOCUMENT_ROOT'], "", $this->dirForImages) . "$1\"",
            $text
        );
        $text = preg_replace(
            "/xlink:href=\"((\S)*)\"/",
            "xlink:href=\"" . str_replace($_SERVER['DOCUMENT_ROOT'], "", $this->dirForImages) . "$1\"",
            $text
        );
        return [
            'text' => $text,
            'name' => "Часть " . ($this->currentItemIndex + 1)
        ];
    }


    public function next()
    {
        $this->currentItemIndex++;
    }

    public function key()
    {
        return $this->currentItemIndex;
    }

    public function valid()
    {
        return !empty($this->content[$this->currentItemIndex]);
    }

    public function rewind()
    {
        $this->currentItemIndex = 0;
    }

    public function getTitle()
    {
        return $this->meta->getTitle();
    }

    public function getDescription()
    {
        return $this->meta->getDescription();
    }

    public function getAuthor()
    {
        return $this->meta->getAuthor();
    }

    public function getCoverPath()
    {
        return $this->dirForImages . $this->meta->getCoverPath();
    }
}