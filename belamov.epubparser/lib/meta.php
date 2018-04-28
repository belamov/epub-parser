<?php

namespace belamov\EpubParser;


class Meta
{
    private $contentPath;
    private $meta;

    /**
     * Meta constructor.
     * @param \SimpleXMLElement $meta
     */
    public function __construct($meta, $contentPath)
    {
        $this->meta = $meta;
        $this->contentPath = $contentPath;
    }

    public function getContent()
    {
        $content = [];
        foreach ($this->meta->spine->itemref as $itemref) {
            $item = $this->meta->xpath('//*[@id = "' . (string)$itemref['idref'] . '"]');
            $content[] = $this->contentPath . (string)$item[0]['href'];
        }
        return $content;
    }

    public function saveImages($dirForImages)
    {
        foreach ($this->meta->manifest->item as $item) {
            if (
                $item['media-type'] == 'image/png'
                or $item['media-type'] == 'image/jpeg'
                or $item['media-type'] == 'image/gif'
            ) {
                $imgPath = (string)$item['href'];

                if (!file_exists(dirname($dirForImages . $imgPath))) {
                    mkdir(dirname($dirForImages . $imgPath), 0777, true);
                }
                copy($this->contentPath . $imgPath, $dirForImages . $imgPath);
            }
        }
    }

    public function getTitle()
    {
        return (string)$this->meta->xpath("//dc:title")[0];
    }

    public function getDescription()
    {
        return (string)$this->meta->xpath("//dc:description")[0];
    }

    public function getAuthor()
    {
        return (string)$this->meta->xpath("//dc:creator")[0];
    }

    public function getCoverPath()
    {
        $coverId = (string)$this->meta->xpath('//*[@name="cover"]')[0]['content'];
        $coverPath = (string)$this->meta->xpath('//*[@id ="' . $coverId . '"]')[0]['href'];
        return $coverPath;
    }
}