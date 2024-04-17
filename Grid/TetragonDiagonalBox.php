<?php
namespace Keiwen\Utils\Grid;

class TetragonDiagonalBox extends TetragonBox
{


    /**
     * @param int $direction
     * @return TetragonDiagonalBox|null
     */
    public function getNeighbor(int $direction): ?AbstractBox
    {
        $neighbor = parent::getNeighbor($direction);
        if ($neighbor !== null) return $neighbor;

        $neighborsCoord = $this->coord;
        switch($direction) {
            case AbstractGrid::DIRECTION_UPRIGHT:
                $neighborsCoord[0]--;
                $neighborsCoord[1]++;
                break;
            case AbstractGrid::DIRECTION_DOWNRIGHT:
                $neighborsCoord[0]++;
                $neighborsCoord[1]++;
                break;
            case AbstractGrid::DIRECTION_DOWNLEFT:
                $neighborsCoord[0]++;
                $neighborsCoord[1]--;
                break;
            case AbstractGrid::DIRECTION_UPLEFT:
                $neighborsCoord[0]--;
                $neighborsCoord[1]--;
                break;
            default: return null;
        }
        $neighborsCoord = $this->grid->adjustCoord($neighborsCoord);
        if(!TetragonDiagonalGrid::isValidCoord($neighborsCoord)) return null;
        return $this->grid->getBox($neighborsCoord);
    }


}
