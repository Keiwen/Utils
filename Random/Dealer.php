<?php

namespace Keiwen\Utils\Random;


class Dealer
{

    protected $library;
    protected $deck;
    protected $discard;


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
     * empty discard and fill deck
     */
    public function initialize()
    {
        $this->discard = array();
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
    public function drawFromDeck($fromBottom = false)
    {
        if($fromBottom) return array_shift($this->deck);
        return array_pop($this->deck);
    }


    /**
     * @param mixed $record
     * @param bool  $inDeck put record back in deck instead of discard
     * @param bool  $topOfDeck put record back on top of deck
     */
    public function discard($record, bool $inDeck = false, bool $topOfDeck = false)
    {
        switch(true) {
            case !$inDeck:
                //put in discard
                array_push($this->discard, $record);
                break;
            case $topOfDeck:
                //top of deck
                array_push($this->deck, $record);
                break;
            default:
                //bottom of deck
                array_unshift($this->deck, $record);
        }
    }


    /**
     * @param bool $shuffleDeck shuffle resulting deck
     * @param bool $shuffleDiscardFirst shuffle discard prior to mixed with deck
     */
    public function mergeDiscardInDeck(bool $shuffleDeck = false, bool $shuffleDiscardFirst = false)
    {
        if($shuffleDiscardFirst) {
            shuffle($this->discard);
        } elseif(!$shuffleDeck) {
            array_reverse($this->discard);
        }
        $this->deck = array_merge($this->discard, $this->deck);
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
