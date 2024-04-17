<?php
namespace Keiwen\Utils\Grid;

use Keiwen\Utils\Math\Divisibility;

class HexagonVerticalBox extends AbstractBox
{


    /**
     * @param int $direction
     * @return HexagonVerticalBox|null
     */
    public function getNeighbor(int $direction): ?AbstractBox
    {
        $neighborsCoord = $this->coord;
        $maxColOffset = ceil($this->grid->getMaxWidth() / 2);
        switch($direction) {
            case AbstractGrid::DIRECTION_UP:
                $neighborsCoord[0]--;
                break;
            case AbstractGrid::DIRECTION_UPRIGHT:
                $neighborsCoord[1]++;
                // on even column, we have a row offset
                if (Divisibility::isNumberEven($this->coord[1])) {
                    $neighborsCoord[0]--;
                }
                break;
            case AbstractGrid::DIRECTION_DOWNRIGHT:
                $neighborsCoord[1]++;
                // on odd column, we have a row offset
                if (Divisibility::isNumberOdd($this->coord[1])) {
                    $neighborsCoord[0]++;
                }
                break;
            case AbstractGrid::DIRECTION_DOWN:
                $neighborsCoord[0]++;
                break;
            case AbstractGrid::DIRECTION_DOWNLEFT:
                $neighborsCoord[1]--;
                // on odd column, we have a row offset
                if (Divisibility::isNumberOdd($this->coord[1])) {
                    $neighborsCoord[0]++;
                }
                break;
            case AbstractGrid::DIRECTION_UPLEFT:
                $neighborsCoord[1]--;
                // on even column, we have a row offset
                if (Divisibility::isNumberEven($this->coord[1])) {
                    $neighborsCoord[0]--;
                }
                break;
            default: return null;
        }
        $neighborsCoord = $this->grid->adjustCoord($neighborsCoord);
        if(!HexagonVerticalGrid::isValidCoord($neighborsCoord)) return null;
        return $this->grid->getBox($neighborsCoord);
    }


}
