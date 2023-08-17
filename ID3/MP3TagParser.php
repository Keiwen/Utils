<?php

namespace Keiwen\Utils\ID3;

class MP3TagParser
{

    protected $filepath;
    protected $metadataV1;
    protected $metadataV2;


    public function __construct(string $filepath)
    {
        $realpath = $filepath ? realpath($filepath) : null;
        if (!$realpath || !file_exists($realpath) || !is_file($realpath) || !is_readable($realpath)) {
            throw new ID3Exception('File not found or invalid: ' . $filepath);
        }
        $filesize = filesize($realpath);
        if ($filesize > PHP_INT_MAX) {
            throw new ID3Exception(sprintf('File is too big: PHP_INT_MAX is %d while filesize is %d', PHP_INT_MAX, $filesize));
        }
        $this->filepath = $realpath;
        $fh = fopen($realpath, 'rb');
        if (!$fh) {
            throw new ID3Exception('Error on file reading');
        }

        // For id3v1, tag are placed on the last 128 bytes and start by "TAG"
        fseek($fh, -128, SEEK_END);
        if (fread($fh, 3) === 'TAG') {
            fseek($fh, -128, SEEK_END);
            $rawData = fread($fh, 128);
            $this->metadataV1 = new ID3v1Parser($rawData);
        }

        // For id3v2, tag are placed at the start and have variable length but start by "ID3"
        fseek($fh, 0, SEEK_SET);
        if (fread($fh, 3) === 'ID3') {
            //byte 4 is major version, byte 5 is revision
            //byte 6 is the ID3 flags
            //byte 7 to 10 is the ID3 size
            fseek($fh, 6, SEEK_SET);
            $sizeByte = unpack('N', fread($fh, 4));
            $tagSize = ID3Utils::syncSafeInteger($sizeByte[1]);
            fseek($fh, 0, SEEK_SET);
            $rawData = fread($fh, $tagSize);
            $this->metadataV2 = new ID3v2Parser($rawData);
        }
        fclose($fh);
    }

    public function hasV1Metadata(): bool
    {
        return !empty($this->metadataV1);
    }

    public function hasV2Metadata(): bool
    {
        return !empty($this->metadataV2);
    }

    /**
     * try to read data where it can
     * @param array $orderedDataNames
     * @return mixed|null
     */
    protected function searchMetadata(array $orderedDataNames)
    {
        foreach ($orderedDataNames as $dataName) {
            $dataValue = $this->getMetadata($dataName);
            if ($dataValue !== null) return $dataValue;
        }
        return null;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getMetadata(string $name)
    {
        $dataValue = null;
        if ($this->hasV2Metadata()) {
            $dataValue = $this->metadataV2->getData($name);
        }
        if ($dataValue === null && $this->hasV1Metadata()) {
            $dataValue = $this->metadataV1->getData($name);
        }
        if ($dataValue !== null) return $dataValue;
        return null;
    }

    /**
     * @return array
     */
    public function getAllMetadataV1()
    {
        if ($this->hasV1Metadata()) {
            return $this->metadataV1->getAllDatas();
        }
        return array();
    }

    /**
     * @return array
     */
    public function getAllMetadataV2()
    {
        if ($this->hasV2Metadata()) {
            return $this->metadataV2->getAllDatas();
        }
        return array();
    }

    public function getTitle()
    {
        return $this->searchMetadata(['TIT2', 'TT2', ID3v1Parser::DATA_TITLE]);
    }

    public function getArtist()
    {
        return $this->searchMetadata(['TOPE', 'TOA', ID3v1Parser::DATA_ARTIST]);
    }

    public function getAlbum()
    {
        return $this->searchMetadata(['TALB', 'TAL', ID3v1Parser::DATA_ALBUM]);
    }

    public function getYear()
    {
        return $this->searchMetadata(['TYER', 'TYE', ID3v1Parser::DATA_YEAR]);
    }

    public function getTrackNumber()
    {
        return $this->searchMetadata(['TRCK', 'TRK', ID3v1Parser::DATA_TRACK_NUMBER]);
    }

    public function getGenre()
    {
        return $this->searchMetadata([ID3v1Parser::DATA_GENRE]);
    }

    public function getGenreId()
    {
        return $this->searchMetadata([ID3v1Parser::DATA_GENRE_ID]);
    }


}
