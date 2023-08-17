<?php

namespace Keiwen\Utils\ID3;

class ID3v2Parser extends ID3AbstractParser
{

    const ENCODING_NAMES = [
        // $00  ISO-8859-1.
        // Terminated with $00.
        0x00   => 'ISO-8859-1',
        // $01  UTF-16 encoded Unicode with BOM. All strings in the same frame SHALL have the same byteorder.
        // Terminated with $00 00.
        0x01   => 'UTF-16',
        // $02  UTF-16 big endian, encoded Unicode without BOM.
        // Terminated with $00 00.
        0x02   => 'UTF-16BE',
        // $03  UTF-8 encoded Unicode.
        // Terminated with $00.
        0x03   => 'UTF-8',
    ];

    protected $encoding = 'ISO-8859-1';
    protected $terminator = "\x00";
    protected $terminatorPos;

    protected $id3Version;
    protected $flags = array(
        'unsync' => false,
        'compression' => false,
        'extHead' => false,
        'experimental' => false,
        'hasFooter' => false,
    );
    protected $extHeadSize = 0;
    protected $extHeader = '';


    /**
     * @param string $tagRawData
     * @return array
     * @throws ID3Exception
     */
    protected function parse(string $tagRawData): array
    {
        /*
         * First 10 bytes defines ID3v2 header as follows
         * ID3v2/file identifier      "ID3"
         * ID3v2 version              $03 00
         * ID3v2 flags                %abc00000
         * ID3v2 size             4 * %0xxxxxxx
         */
        $this->id3Version = ord(substr($tagRawData, 3, 1));
        if (!in_array($this->id3Version, [2, 3, 4])) {
			throw new ID3Exception('Invalid id3v2 version: ' . $this->id3Version);
        }
        $this->parseFlags($tagRawData);
        $this->parseExtHead($tagRawData);

        $tagData = substr($tagRawData, 10 + $this->extHeadSize);

        if ($this->flags['unsync'] && $this->id3Version <= 3) {
            $tagData = ID3Utils::unsyncString($tagData);
        }

        $index = 0;
        $rawLength = strlen($tagData);
        $parsedData = array();

        while ($index < $rawLength) {
            // even if it should be overwritten, reset parsedValue
            $parsedValue = null;
            // frame name
            $frameName = substr($tagData, $index, 3);
            $index += 3;
            if ($this->id3Version === 2) {
                if ($frameName === "\x00\x00\x00" || strlen($frameName) !== 3) break; // empty name or incorrect
                $frameCharsCount = 3;
            } else {
                if ($frameName === "\x00\x00\x00\x00" || strlen($frameName) !== 4) break; // empty name or incorrect
                $frameCharsCount = 4;
            }
            // frame length
            $sizeByte = substr($tagData, $index, $frameCharsCount);
            $index += $frameCharsCount;
            if (strlen($sizeByte) !== $frameCharsCount) break; // incorrect length
            $sizeByte = unpack('N', "\x00" . $sizeByte);
            $frameLength = $sizeByte[1] ?? 0;
            if ($frameLength > $rawLength || $index >= $rawLength) break; // exceeds tag size
            if ($frameLength < 1) continue; // empty frame

            if ($frameCharsCount === 4) {
                // status & format bytes
                $frameStatus = ord(substr($tagData, $index, 1));
                $frameFormat = ord(substr($tagData, $index + 1, 1));
                $index += 2;
            }

            // frame data
            $frameData = substr($tagData, $index, $frameLength);
            $index += $frameLength;
            if (strlen($frameData) < 1) continue; // empty frame

            if ($frameCharsCount === 4) {
                $frameFormat = $this->parseFrameFormat($frameFormat);
                $frameStatus = $this->parseFrameStatus($frameStatus);
                if($frameFormat['unsync']){
                    $frameData = ID3Utils::unsyncString($frameData);
                }
            }





            $this->updateTerminatorPos($frameData);
            $parsedValue = trim($this->parseFrame($frameName, $frameLength, $frameData));

            $parsedData[$frameName] = $parsedValue;

        }

        return $parsedData;
    }

    protected function parseFlags(string $tagRawData): void
    {
        $flags = ord(substr($tagRawData, 5, 1));
        // bit 7 in flags indicates whether or not unsynchronisation is used
        $this->flag['unsync'] = (bool) ($flags & 0b10000000);
        // bit 6 in flags indicates whether or not compression is used
        if ($this->id3Version === 2) {
            // bit 6 in flags indicates whether or not compression is used
            $this->flag['compression'] = (bool) ($flags & 0b01000000);
        }
        if ($this->id3Version === 3 || $this->id3Version === 4) {
            // bit 6 in flags indicates whether or not the header is followed by and extended header
            $this->flag['extHead'] = (bool) ($flags & 0b01000000);
            // bit 5 is a experimental indicator
            $this->flag['experimental'] = (bool) ($flags & 0b00100000);
        }
        if ($this->id3Version === 4) {
            // bit 4 indicates a footer at the end of the tag
            $this->flag['hasFooter'] = (bool) ($flags & 0b00010000);
        }
    }

    protected function parseExtHead(string $tagRawData): void
    {
        if ($this->flags['extHead']) {
            $sizeByte = unpack('N', substr($tagRawData, 10, 4));
            $extHeaderSize = ID3Utils::syncSafeInteger($sizeByte[1]);
            $this->extHeader = substr($tagRawData, 14, $extHeaderSize);
            // add 4 to include bytes for size
            $this->extHeadSize = 4 + $extHeaderSize;
        }
    }

    public function getFlags()
    {
        return $this->flags;
    }

    public function getID3Version()
    {
        return $this->id3Version;
    }


    protected function updateTerminatorPos(string $data): void
    {
        $encodingByte   = ord(substr($data, 0, 1));
        $this->encoding = $this::ENCODING_NAMES[$encodingByte] ?? 'ISO-8859-1';
        $this->terminatorPos  = strpos($data, $this->terminator, 1);

        // UTF-16
        if ($encodingByte === 1 || $encodingByte === 2) {
            $this->terminator = "\x00\x00";
            // match terminator + BOM
            preg_match('/[\x00]{2}[\xfe\xff]{2}/', $data, $match);
            // no BOM / content following the terminator is not encoded
            if (empty($match) || $encodingByte === 2) {
                preg_match('/[\x00]{2}[^\x00]+/', $data, $match);
            }
            // add 2 bytes for the terminator
            $this->terminatorPos = strpos($data, $match[0] ?? "\x00") + 2;
        }
    }

    protected function parseFrameFormat(int $flags): array
    {
        if ($this->id3Version === 3) {
            return array(
                'flags' => $flags,
                'length' => false,
                'unsync' => false,
                'encryption' => (bool) ($flags & 0b01000000),
                'compression' => (bool) ($flags & 0b10000000),
                'grouping' => (bool) ($flags & 0b00100000),
            );
        } else {
            return array(
                'flags' => $flags,
                'length' => (bool) ($flags & 0b00000001),
                'unsync' => (bool) ($flags & 0b00000010),
                'encryption' => (bool) ($flags & 0b00000100),
                'compression' => (bool) ($flags & 0b00001000),
                'grouping' => (bool) ($flags & 0b01000000),
            );
        }
    }

    protected function parseFrameStatus(int $flags): array
    {
        if ($this->id3Version === 3) {
            return array(
                'flags' => $flags,
                'read-only' => (bool) ($flags & 0b00100000),
                'file' => (bool) ($flags & 0b01000000),
                'tag' => (bool) ($flags & 0b10000000),
            );
        } else {
            return array(
                'flags' => $flags,
                'read-only' => (bool) ($flags & 0b00010000),
                'file' => (bool) ($flags & 0b00100000),
                'tag' => (bool) ($flags & 0b01000000),
            );
        }
    }

    protected function parseFrame(string $name, int $length, $data)
    {

        switch ($name) {
            case 'CNT':
            case 'PCNT':
                return 0; //not implemented
            case 'COM':
            case 'COMM':
                return $this->parseStringValue(substr($data, $this->terminatorPos));
            case 'IPL':
            case 'IPLS':
                return $this->parseStringValue(substr($data, 1));
            case 'GEOB':
                return null;
            case 'LINK':
                return null;
            case 'MCI':
            case 'MCDI':
                return $data;
            case 'PIC':
            case 'APIC':
                return ''; //not implemented
            case 'POP':
            case 'POPM':
                $t = strpos($data, "\x00", 1);
                return ord(substr($data, $t + 1, 1));
            case 'PRIV':
                return $this->parseStringValue(substr($data, $this->terminatorPos));
            case 'TXX':
            case 'TXXX':
                return $this->parseStringValue(substr($data, $this->terminatorPos));
            case 'UFID':
                return null;
            case 'ULT':
            case 'USLT':
                return $this->parseStringValue(substr($data, $this->terminatorPos));
            case 'WXX':
            case 'WXXX':
                return $this->parseStringValue(substr($data, $this->terminatorPos));
        }

        $shortname = substr($name, 0, 1);
        switch ($shortname) {
            case 'T':
                // Text information identifier: "T00" - "TZZ", exclunding TXX
                return $this->parseStringValue(substr($data, 1));
            case 'W':
                // URL link frame: "T00" - "TZZ", excluding WXX
                return $data;
        }

        return bin2hex($data);
    }

    public static function getPossibleDataNames(): array
    {
        return array(
            'BUF' => 'Recommended buffer size',
            'CNT' => 'Play counter',
            'COM' => 'Comments',
            'CRA' => 'Audio encryption',
            'CRM' => 'Encrypted meta frame',
            'ETC' => 'Event timing codes',
            'EQU' => 'Equalization',
            'GEO' => 'General encapsulated object',
            'IPL' => 'Involved people list',
            'LNK' => 'Linked information',
            'MCI' => 'Music CD Identifier',
            'MLL' => 'MPEG location lookup table',
            'PIC' => 'Attached picture',
            'POP' => 'Popularimeter',
            'REV' => 'Reverb',
            'RVA' => 'Relative volume adjustment',
            'SLT' => 'Synchronized lyric/text',
            'STC' => 'Synced tempo codes',
            'TAL' => 'Album/Movie/Show title',
            'TBP' => 'BPM (Beats Per Minute)',
            'TCM' => 'Composer',
            'TCO' => 'Content type',
            'TCR' => 'Copyright message',
            'TDA' => 'Date',
            'TDY' => 'Playlist delay',
            'TEN' => 'Encoded by',
            'TFT' => 'File type',
            'TIM' => 'Time',
            'TKE' => 'Initial key',
            'TLA' => 'Language(s)',
            'TLE' => 'Length',
            'TMT' => 'Media type',
            'TOA' => 'Original artist(s)/performer(s)',
            'TOF' => 'Original filename',
            'TOL' => 'Original Lyricist(s)/text writer(s)',
            'TOR' => 'Original release year',
            'TOT' => 'Original album/Movie/Show title',
            'TP1' => 'Lead artist(s)/Lead performer(s)/Soloist(s)/Performing group',
            'TP2' => 'Band/Orchestra/Accompaniment',
            'TP3' => 'Conductor/Performer refinement',
            'TP4' => 'Interpreted, remixed, or otherwise modified by',
            'TPA' => 'Part of a set',
            'TPB' => 'Publisher',
            'TRC' => 'ISRC (International Standard Recording Code)',
            'TRD' => 'Recording dates',
            'TRK' => 'Track number/Position in set',
            'TSI' => 'Size',
            'TSS' => 'Software/hardware and settings used for encoding',
            'TT1' => 'Content group description',
            'TT2' => 'Title/Songname/Content description',
            'TT3' => 'Subtitle/Description refinement',
            'TXT' => 'Lyricist/text writer',
            'TXX' => 'User defined text information frame',
            'TYE' => 'Year',
            'UFI' => 'Unique file identifier',
            'ULT' => 'Unsychronized lyric/text transcription',
            'WAF' => 'Official audio file webpage',
            'WAR' => 'Official artist/performer webpage',
            'WAS' => 'Official audio source webpage',
            'WCM' => 'Commercial information',
            'WCP' => 'Copyright/Legal information',
            'WPB' => 'Publishers official webpage',
            'WXX' => 'User defined URL link frame',

            'ITU' => 'iTunes?',
            'PCS' => 'Podcast?',
            'TDR' => 'Release date',

            'AENC' => 'Audio encryption',
            'APIC' => 'Attached picture',
            'COMM' => 'Comments',
            'COMR' => 'Commercial frame',
            'ENCR' => 'Encryption method registration',
            'EQUA' => 'Equalization',
            'ETCO' => 'Event timing codes',
            'GEOB' => 'General encapsulated object',
            'GRID' => 'Group identification registration',
            'IPLS' => 'Involved people list',
            'LINK' => 'Linked information',
            'MCDI' => 'Music CD identifier',
            'MLLT' => 'MPEG location lookup table',
            'OWNE' => 'Ownership frame',
            'PRIV' => 'Private frame',
            'PCNT' => 'Play counter',
            'POPM' => 'Popularimeter',
            'POSS' => 'Position synchronisation frame',
            'RBUF' => 'Recommended buffer size',
            'RVAD' => 'Relative volume adjustment',
            'RVRB' => 'Reverb',
            'SYLT' => 'Synchronized lyric/text',
            'SYTC' => 'Synchronized tempo codes',
            'TALB' => 'Album/Movie/Show title',
            'TBPM' => 'BPM (beats per minute)',
            'TCOM' => 'Composer',
            'TCON' => 'Content type',
            'TCOP' => 'Copyright message',
            'TDAT' => 'Date',
            'TDLY' => 'Playlist delay',
            'TENC' => 'Encoded by',
            'TEXT' => 'Lyricist/Text writer',
            'TFLT' => 'File type',
            'TIME' => 'Time',
            'TIT1' => 'Content group description',
            'TIT2' => 'Title/songname/content description',
            'TIT3' => 'Subtitle/Description refinement',
            'TKEY' => 'Initial key',
            'TLAN' => 'Language(s)',
            'TLEN' => 'Length',
            'TMED' => 'Media type',
            'TOAL' => 'Original album/movie/show title',
            'TOFN' => 'Original filename',
            'TOLY' => 'Original lyricist(s)/text writer(s)',
            'TOPE' => 'Original artist(s)/performer(s)',
            'TORY' => 'Original release year',
            'TOWN' => 'File owner/licensee',
            'TPE1' => 'Lead performer(s)/Soloist(s)',
            'TPE2' => 'Band/orchestra/accompaniment',
            'TPE3' => 'Conductor/performer refinement',
            'TPE4' => 'Interpreted, remixed, or otherwise modified by',
            'TPOS' => 'Part of a set',
            'TPUB' => 'Publisher',
            'TRCK' => 'Track number/Position in set',
            'TRDA' => 'Recording dates',
            'TRSN' => 'Internet radio station name',
            'TRSO' => 'Internet radio station owner',
            'TSIZ' => 'Size',
            'TSRC' => 'ISRC (international standard recording code)',
            'TSSE' => 'Software/Hardware and settings used for encoding',
            'TYER' => 'Year',
            'TXXX' => 'User defined text information frame',
            'UFID' => 'Unique file identifier',
            'USER' => 'Terms of use',
            'USLT' => 'Unsychronized lyric/text transcription',
            'WCOM' => 'Commercial information',
            'WCOP' => 'Copyright/Legal information',
            'WOAF' => 'Official audio file webpage',
            'WOAR' => 'Official artist/performer webpage',
            'WOAS' => 'Official audio source webpage',
            'WORS' => 'Official internet radio station homepage',
            'WPAY' => 'Payment',
            'WPUB' => 'Publishers official webpage',
            'WXXX' => 'User defined URL link frame',

            'GRP1' => 'ITunes Grouping',
            'TCMP' => 'ITunes compilation field',
            'TDEN' => 'Encoding time',
            'TSST' => 'Set subtitle',
            'TIPL' => 'Involved people list',
            'TMOO' => 'Mood',
            'TDOR' => 'Original release time',
            'TDRL' => 'Release time',
            'TDTG' => 'Tagging time',
            'TDRC' => 'Recording time',
            'TSOA' => 'Album sort order',
            'TSOP' => 'Performer sort order',
            'TSOT' => 'Title sort order',
            'TSO2' => 'Album-Artist sort order',
            'TSOC' => 'Composer sort order',
            'EQU2' => 'Equalisation',
            'RVA2' => 'Relative volume adjustment',
            'SIGN' => 'Signature',
            'ASPI' => 'Audio seek point index',
            'RGAD' => 'Replay Gain Adjustment',
            'CHAP' => 'Chapters',
            'CTOC' => 'Chapters Table Of Contents',
        );
    }


}
