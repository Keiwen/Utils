<?php
namespace Keiwen\Utils\Grid;

use Keiwen\Utils\Math\Divisibility;

class HexagonHorizontalBox extends AbstractBox
{


    /**
     * @param int $direction
     * @return HexagonHorizontalBox|null
     */
    public function getNeighbor(int $direction): ?AbstractBox
    {
        $neighborsCoord = $this->coord;
        switch($direction) {
            case AbstractGrid::DIRECTION_UPRIGHT:
                $neighborsCoord[0]--;
                // on odd row, we have a column offset
                if (Divisibility::isNumberOdd($this->coord[0])) {
                    $neighborsCoord[1]++;
                }
                break;
            case AbstractGrid::DIRECTION_RIGHT:
                $neighborsCoord[1]++;
                break;
            case AbstractGrid::DIRECTION_DOWNRIGHT:
                $neighborsCoord[0]++;
                // on odd row, we have a column offset
                if (Divisibility::isNumberOdd($this->coord[0])) {
                    $neighborsCoord[1]++;
                }
                break;
            case AbstractGrid::DIRECTION_DOWNLEFT:
                $neighborsCoord[0]++;
                // on even row, we have a column offset
                if (Divisibility::isNumberEven($this->coord[0])) {
                    $neighborsCoord[1]--;
                }
                break;
            case AbstractGrid::DIRECTION_LEFT:
                $neighborsCoord[1]--;
                break;
            case AbstractGrid::DIRECTION_UPLEFT:
                $neighborsCoord[0]--;
                // on even row, we have a column offset
                if (Divisibility::isNumberEven($this->coord[0])) {
                    $neighborsCoord[1]--;
                }
                break;
            default: return null;
        }
        $neighborsCoord = $this->grid->adjustCoord($neighborsCoord);
        if(!HexagonHorizontalGrid::isValidCoord($neighborsCoord)) return null;
        return $this->grid->getBox($neighborsCoord);
    }


}
