<?php
namespace Keiwen\Utils\Grid;

class TetragonBox extends AbstractBox
{

    /**
     * @param int $direction
     * @return TetragonBox|null
     */
    public function getNeighbor(int $direction): ?AbstractBox
    {
        $neighborsCoord = $this->coord;
        switch($direction) {
            case AbstractGrid::DIRECTION_UP:
                $neighborsCoord[0]--;
                break;
            case AbstractGrid::DIRECTION_RIGHT:
                $neighborsCoord[1]++;
                break;
            case AbstractGrid::DIRECTION_DOWN:
                $neighborsCoord[0]++;
                break;
            case AbstractGrid::DIRECTION_LEFT:
                $neighborsCoord[1]--;
                break;
            default: return null;
        }
        $neighborsCoord = $this->grid->adjustCoord($neighborsCoord);
        if(!TetragonGrid::isValidCoord($neighborsCoord)) return null;
        return $this->grid->getBox($neighborsCoord);
    }


}
