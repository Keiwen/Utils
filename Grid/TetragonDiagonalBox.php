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
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    if($neighborsCoord[0] < 0) $neighborsCoord[0] = $this->grid->getMaxHeight() - 1;
                    if($neighborsCoord[1] >= $this->grid->getMaxWidth()) $neighborsCoord[1] = 0;
                }
                break;
            case AbstractGrid::DIRECTION_DOWNRIGHT:
                $neighborsCoord[0]++;
                $neighborsCoord[1]++;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    if($neighborsCoord[0] >= $this->grid->getMaxHeight()) $neighborsCoord[0] = 0;
                    if($neighborsCoord[1] >= $this->grid->getMaxWidth()) $neighborsCoord[1] = 0;
                }
                break;
            case AbstractGrid::DIRECTION_DOWNLEFT:
                $neighborsCoord[0]++;
                $neighborsCoord[1]--;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    if($neighborsCoord[0] >= $this->grid->getMaxHeight()) $neighborsCoord[0] = 0;
                    if($neighborsCoord[1] < 0) $neighborsCoord[1] = $this->grid->getMaxWidth() - 1;
                }
                break;
            case AbstractGrid::DIRECTION_UPLEFT:
                $neighborsCoord[0]--;
                $neighborsCoord[1]--;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    if($neighborsCoord[0] < 0) $neighborsCoord[0] = $this->grid->getMaxHeight() - 1;
                    if($neighborsCoord[1] < 0) $neighborsCoord[1] = $this->grid->getMaxWidth() - 1;
                }
                break;
            default: return null;
        }
        if(!TetragonDiagonalGrid::isValidCoord($neighborsCoord)) return null;
        return $this->grid->getBox($neighborsCoord);
    }


}
