<?php

namespace Keiwen\Utils\Random;


class Dealer
{

    protected $library;
    protected $deck;
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
     */
    public function initialize()
    {
        $this->discardPile = array();
        $this->deck = $this->library;
        $this->shuffleDeck();
    }


    /**
     * @param int $part number of part, must be greater than one
     * @param int $maxRound max records retrieved for each part
     * @return array
     * @throws \RuntimeException when less than one part
     */
    public function deal(int $part, int $maxRound = 0)
    {
        if($part < 1) throw new \RuntimeException("Cannot deal for less than one part");
        $deal = array();
        $currentRound = 1;
        $currentPart = 1;
        while(!empty($this->deck)) {
            $deal[$currentPart][] = $this->drawFromDeck();
            $currentPart++;
            //if all part filled, start a new round
            if($currentPart > $part) {
                $currentPart = 1;
                $currentRound++;
                //stop if max round reach
                if($currentRound > $maxRound) break;
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
        if($fromBottom) return array_shift($this->deck);
        return array_pop($this->deck);
    }


    /**
     * @param mixed $record
     * @param bool  $inDeck put record back in deck instead of discard pile
     * @param bool  $topOfDeck put record back on top of deck
     */
    public function discard($record, bool $inDeck = false, bool $topOfDeck = false)
    {
        switch(true) {
            case !$inDeck:
                //put in discard pile
                $this->discardPile[] = $record;
                break;
            case $topOfDeck:
                //top of deck
                $this->deck[] = $record;
                break;
            default:
                //bottom of deck
                array_unshift($this->deck, $record);
        }
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
        $this->deck = array_merge($this->discardPile, $this->deck);
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
