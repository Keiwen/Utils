<?php
namespace Keiwen\Utils\Grid;

class TetragonDiagonalGrid extends TetragonGrid
{


    /**
     * create a new box
     * @param int[] $coord
     * @return TetragonDiagonalBox
     */
    protected function createBox(array $coord): AbstractBox
    {
        $box = new TetragonDiagonalBox($coord);
        $this->addBox($box);
        return $box;
    }



    public function getBoxNeighborDirections()
    {
        return array(
            static::DIRECTION_UP,
            static::DIRECTION_UPRIGHT,
            static::DIRECTION_RIGHT,
            static::DIRECTION_DOWNRIGHT,
            static::DIRECTION_DOWN,
            static::DIRECTION_DOWNLEFT,
            static::DIRECTION_LEFT,
            static::DIRECTION_UPLEFT,
        );
    }



}
