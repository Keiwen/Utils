<?php

namespace Keiwen\Utils\ID3;

class ID3v1Parser extends ID3AbstractParser
{

    /**
     * Genre definitions 0-79 follow the ID3 tag specification of 1999
     * and the first set of Winamp extensions (80-125)
     */
    const GENRES = [
        0   => 'Blues',
        1   => 'Classic Rock',
        2   => 'Country',
        3   => 'Dance',
        4   => 'Disco',
        5   => 'Funk',
        6   => 'Grunge',
        7   => 'Hip-Hop',
        8   => 'Jazz',
        9   => 'Metal',
        10  => 'New Age',
        11  => 'Oldies',
        12  => 'Other',
        13  => 'Pop',
        14  => 'R&B',
        15  => 'Rap',
        16  => 'Reggae',
        17  => 'Rock',
        18  => 'Techno',
        19  => 'Industrial',
        20  => 'Alternative',
        21  => 'Ska',
        22  => 'Death Metal',
        23  => 'Pranks',
        24  => 'Soundtrack',
        25  => 'Euro-Techno',
        26  => 'Ambient',
        27  => 'Trip-Hop',
        28  => 'Vocal',
        29  => 'Jazz+Funk',
        30  => 'Fusion',
        31  => 'Trance',
        32  => 'Classical',
        33  => 'Instrumental',
        34  => 'Acid',
        35  => 'House',
        36  => 'Game',
        37  => 'Sound Clip',
        38  => 'Gospel',
        39  => 'Noise',
        40  => 'Alternative Rock',
        41  => 'Bass',
        42  => 'Soul',
        43  => 'Punk',
        44  => 'Space',
        45  => 'Meditative',
        46  => 'Instrumental Pop',
        47  => 'Instrumental Rock',
        48  => 'Ethnic',
        49  => 'Gothic',
        50  => 'Darkwave',
        51  => 'Techno-Industrial',
        52  => 'Electronic',
        53  => 'Pop-Folk',
        54  => 'Eurodance',
        55  => 'Dream',
        56  => 'Southern Rock',
        57  => 'Comedy',
        58  => 'Cult',
        59  => 'Gangsta',
        60  => 'Top 40',
        61  => 'Christian Rap',
        62  => 'Pop/Funk',
        63  => 'Jungle',
        64  => 'Native US',
        65  => 'Cabaret',
        66  => 'New Wave',
        67  => 'Psychadelic',
        68  => 'Rave',
        69  => 'Showtunes',
        70  => 'Trailer',
        71  => 'Lo-Fi',
        72  => 'Tribal',
        73  => 'Acid Punk',
        74  => 'Acid Jazz',
        75  => 'Polka',
        76  => 'Retro',
        77  => 'Musical',
        78  => 'Rock & Roll',
        79  => 'Hard Rock',
        // Winamp extensions
        80  => 'Folk',
        81  => 'Folk-Rock',
        82  => 'National Folk',
        83  => 'Swing',
        84  => 'Fast Fusion',
        85  => 'Bebob',
        86  => 'Latin',
        87  => 'Revival',
        88  => 'Celtic',
        89  => 'Bluegrass',
        90  => 'Avantgarde',
        91  => 'Gothic Rock',
        92  => 'Progressive Rock',
        93  => 'Psychedelic Rock',
        94  => 'Symphonic Rock',
        95  => 'Slow Rock',
        96  => 'Big Band',
        97  => 'Chorus',
        98  => 'Easy Listening',
        99  => 'Acoustic',
        100 => 'Humour',
        101 => 'Speech',
        102 => 'Chanson',
        103 => 'Opera',
        104 => 'Chamber Music',
        105 => 'Sonata',
        106 => 'Symphony',
        107 => 'Booty Bass',
        108 => 'Primus',
        109 => 'Porn Groove',
        110 => 'Satire',
        111 => 'Slow Jam',
        112 => 'Club',
        113 => 'Tango',
        114 => 'Samba',
        115 => 'Folklore',
        116 => 'Ballad',
        117 => 'Power Ballad',
        118 => 'Rhythmic Soul',
        119 => 'Freestyle',
        120 => 'Duet',
        121 => 'Punk Rock',
        122 => 'Drum Solo',
        123 => 'Acapella',
        124 => 'Euro-House',
        125 => 'Dance Hall',
    ];

    const DEFAULT_GENRE = 12; // other

    const DATA_TITLE = 'title';
    const DATA_ARTIST = 'artist';
    const DATA_ALBUM = 'album';
    const DATA_YEAR = 'year';
    const DATA_TRACK_NUMBER = 'track';
    const DATA_GENRE = 'genre';
    const DATA_GENRE_ID = 'genreid';
    const DATA_COMMENT = 'comment';


    public static function getPossibleDataNames(): array
    {
        return array(
            static::DATA_TITLE, static::DATA_ARTIST, static::DATA_ALBUM,
            static::DATA_YEAR, static::DATA_TRACK_NUMBER, static::DATA_GENRE_ID,
            static::DATA_GENRE, static::DATA_COMMENT
        );
    }

    protected function parse(string $tagRawData): array
    {
        if (strlen($tagRawData) !== 128) {
			throw new ID3Exception('Invalid id3v1 tag size');
        }

        // version 1.0: comment is on 30 bytes
        // version 1.1: comment is on 28 bytes,
        // then next (125) is always null and used as separator
        // then next (126) is not null and used as track number
        if (($tagRawData[125] === "\x00") && ($tagRawData[126] !== "\x00")) {
            // v1.1
            $subFormatVersion = 'a28'. static::DATA_COMMENT . '/a1null/c1track';
        } else {
            // v1.0
            $subFormatVersion = 'a30'. static::DATA_COMMENT;
        }
        $format = 'a3identifier/a30'. static::DATA_TITLE
            . '/a30'. static::DATA_ARTIST
            . '/a30'. static::DATA_ALBUM
            . '/a4'. static::DATA_YEAR . '/'
            . $subFormatVersion
            . '/c1'. static::DATA_GENRE_ID;

        $data = unpack($format, $tagRawData);

        if($data['identifier'] !== 'TAG'){
			throw new ID3Exception('Invalid id3v1 identifier');
        }
        unset($data['identifier'], $data['null']);

        $parsedData = array();
        foreach ($data as $dataKey => $dataValue) {
            // even if it should be overwritten, reset parsedValue
            $parsedValue = null;
            switch ($dataKey) {
                case static::DATA_YEAR:
                    $parsedValue = $this->parseIntValue($dataValue);
                    // unset if negative year or in the future
                    if ($parsedValue <= 0 || $parsedValue > date('Y')) $parsedValue = null;
                    break;
                case static::DATA_GENRE_ID:
                    $parsedValue = $this->parseIntValue($dataValue);
                    if (isset(static::GENRES[$parsedValue])) {
                        $parsedData[static::DATA_GENRE] = static::GENRES[$parsedValue];
                    }
                    break;
                case static::DATA_TRACK_NUMBER:
                    $parsedValue = $this->parseIntValue($dataValue);
                    break;
                default:
                    // title, artist, album
                    $parsedValue = $this->parseStringValue($dataValue);
            }
            $parsedData[$dataKey] = $parsedValue;
        }

        return $parsedData;
    }


    /**
     * @param string $genreName
     * @return int|null null if not found
     */
    public static function findGenreId(string $genreName)
    {
        $reversedGenres = array_flip(static::GENRES);
        return $reversedGenres[$genreName] ?? null;
    }

}
