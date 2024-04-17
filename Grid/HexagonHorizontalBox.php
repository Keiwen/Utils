<?php
namespace Keiwen\Utils\Grid;

class HexagonHorizontalBox extends AbstractBox
{


    /**
     * @param int $direction
     * @return HexagonHorizontalBox|null
     */
    public function getNeighbor(int $direction): ?AbstractBox
    {
        $neighborsCoord = $this->coord;
        $maxRowOffset = ceil($this->grid->getMaxHeight() / 2);
        switch($direction) {
            case AbstractGrid::DIRECTION_UPRIGHT:
                $neighborsCoord[0]--;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    if($neighborsCoord[0] === -1) {
                        $neighborsCoord[0] = $this->grid->getMaxHeight() - 1;
                        $neighborsCoord[1] += $maxRowOffset;
                    } else {
                        $neighborsCoord[1] -= $this->grid->getMaxWidth();
                    }
                }
                break;
            case AbstractGrid::DIRECTION_RIGHT:
                $neighborsCoord[1]++;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    $neighborsCoord[1] -= $this->grid->getMaxWidth();
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
                    if($neighborsCoord[0] === $this->grid->getMaxHeight()) {
                        $neighborsCoord[0] = 0;
                        if($neighborsCoord[1] === $this->grid->getMaxWidth() + $maxRowOffset) {
                            $neighborsCoord[1] = 0;
                        } else {
                            $neighborsCoord[1] -= $maxRowOffset;
                        }
                    } else {
                        $neighborsCoord[1] -= $this->grid->getMaxWidth();
                    }
                }
                break;
            case AbstractGrid::DIRECTION_DOWNLEFT:
                $neighborsCoord[0]++;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    if($neighborsCoord[0] === $this->grid->getMaxHeight()) {
                        $neighborsCoord[0] = 0;
                        $neighborsCoord[1] -= $maxRowOffset;
                    } else {
                        $neighborsCoord[1] += $this->grid->getMaxWidth();
                    }
                }
                break;
            case AbstractGrid::DIRECTION_LEFT:
                $neighborsCoord[1]--;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    $neighborsCoord[1] += $this->grid->getMaxWidth();
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
                    if($neighborsCoord[0] === -1) {
                        $neighborsCoord[0] = $this->grid->getMaxHeight() - 1;
                        if($neighborsCoord[1] === -1) {
                            $neighborsCoord[1] = $this->grid->getMaxWidth() + $maxRowOffset - 1;
                        } else {
                            $neighborsCoord[1] += $maxRowOffset;
                        }
                    } else {
                        $neighborsCoord[1] += $this->grid->getMaxWidth();
                    }
                }
                break;
            default: return null;
        }
        if(!HexagonHorizontalGrid::isValidCoord($neighborsCoord)) return null;
        return $this->grid->getBox($neighborsCoord);
    }


}
