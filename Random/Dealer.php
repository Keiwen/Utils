<?php

namespace Keiwen\Utils\Random;


class Dealer
{

    /** @var array $library */
    protected $library;
    /** @var array $deck */
    protected $deck;
    /** @var array $discardPile */
    protected $discardPile;

    /**
     * Dealer constructor.
     *
     * @param array $library initial records list
     */
    public function __construct(array $library)
    {
        $this->library = $library;
        $this->initialize();
    }


    /**
     * empty discard pile and fill deck
     * @param bool $shuffle
     */
    public function initialize(bool $shuffle = true)
    {
        $this->discardPile = array();
        $this->deck = $this->library;
        if($shuffle) $this->shuffleDeck();
    }


    /**
     * @return array
     */
    public function getDeck()
    {
        return $this->deck;
    }

    /**
     * @return array
     */
    public function getDiscardPile()
    {
        return $this->discardPile;
    }


    /**
     * @param int $part number of part, must be 1 or greater
     * @param int $maxRound max records retrieved for each part
     * @return array
     * @throws \RuntimeException when less than one part
     */
    public function deal(int $part, int $maxRound = 0)
    {
        if($part < 1) throw new \RuntimeException("Cannot deal for less than one part");
        $deal = array();
        $currentRound = 1;
        $currentPart = 0;
        while(!empty($this->deck)) {
            $card = $this->drawFromDeck();
            $deal[$currentPart][] = $card;
            $currentPart++;
            //if all part filled, start a new round
            if($currentPart >= $part) {
                $currentPart = 0;
                //stop if max round reach
                if(!empty($maxRound) && $currentRound >= $maxRound) break;
                $currentRound++;
            }
        }
        return $deal;
    }


    /**
     * @param bool $fromBottom
     * @return mixed
     */
    public function drawFromDeck(bool $fromBottom = false)
    {
        if($fromBottom) return array_pop($this->deck);
        return array_shift($this->deck);
    }


    /**
     * @param $record
     * @param bool $shuffleDeck
     * @param bool $toTop
     */
    public function addInDeck($record, bool $shuffleDeck = true, $toTop = false)
    {
        if($toTop) {
            array_unshift($this->deck, $record);
        } else {
            // array_push($this->deck, $record);
            // use command below, twice faster
            $this->deck[] = $record;
        }
        if($shuffleDeck) $this->shuffleDeck();
    }


    /**
     * @param array $record
     * @param bool $shuffleDeck
     * @param bool $toTop
     */
    public function addMultipleInDeck(array $records, bool $shuffleDeck = true, $toTop = false)
    {
        foreach($records as $record) {
            $this->addInDeck($record, false, $toTop);
        }
        if($shuffleDeck) $this->shuffleDeck();
    }


    /**
     * @param mixed $record
     */
    public function discard($record)
    {
        $this->discardPile[] = $record;
    }


    /**
     * @param bool $shuffleDeck shuffle resulting deck
     * @param bool $shuffleDiscardPileFirst shuffle discard pile prior to mixed with deck
     */
    public function mergeDiscardPileInDeck(bool $shuffleDeck = false, bool $shuffleDiscardPileFirst = false)
    {
        if($shuffleDiscardPileFirst) {
            shuffle($this->discardPile);
        } elseif(!$shuffleDeck) {
            array_reverse($this->discardPile);
        }
        $this->deck = array_merge($this->deck, $this->discardPile);
        if($shuffleDeck) $this->shuffleDeck();
    }


    /**
     *
     */
    public function shuffleDeck()
    {
        shuffle($this->deck);
    }


}
