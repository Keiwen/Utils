<?php
namespace Keiwen\Utils\Grid;

class TetragonGrid extends AbstractGrid
{


    /**
     * create a new box
     * @param int[] $coord
     * @return TetragonBox
     */
    protected function createBox(array $coord): AbstractBox
    {
        $box = new TetragonBox($coord);
        $this->addBox($box);
        return $box;
    }


    public function getBoxNeighborDirections()
    {
        return array(
            static::DIRECTION_UP,
            static::DIRECTION_RIGHT,
            static::DIRECTION_DOWN,
            static::DIRECTION_LEFT,
        );
    }



}
