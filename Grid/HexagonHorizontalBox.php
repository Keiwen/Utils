<?php
namespace Keiwen\Utils\Grid;

class HexagonHorizontalBox extends AbstractBox
{


    /**
     * @param int $direction
     * @return HexagonHorizontalBox|null
     */
    public function getNeighbor(int $direction): AbstractBox
    {
        $neighborsCoord = $this->coord;
        $maxColOffset = ceil($this->grid->getMaxWidth() / 2);
        switch($direction) {
            case AbstractGrid::DIRECTION_UP:
                $neighborsCoord[0]--;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    $neighborsCoord[0] += $this->grid->getMaxHeight();
                }
                break;
            case AbstractGrid::DIRECTION_UPRIGHT:
                $neighborsCoord[1]++;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    if($neighborsCoord[1] === $this->grid->getMaxWidth()) {
                        $neighborsCoord[1] = 0;
                        $neighborsCoord[0] -= $maxColOffset;
                    } else {
                        $neighborsCoord[0] += $this->grid->getMaxHeight();
                    }
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
                    if($neighborsCoord[1] === $this->grid->getMaxWidth()) {
                        $neighborsCoord[1] = 0;
                        if($neighborsCoord[0] === $this->grid->getMaxHeight() + $maxColOffset) {
                            $neighborsCoord[0] = 0;
                        } else {
                            $neighborsCoord[0] -= $maxColOffset;
                        }
                    } else {
                        $neighborsCoord[0] -= $this->grid->getMaxHeight();
                    }
                }
                break;
            case AbstractGrid::DIRECTION_DOWN:
                $neighborsCoord[0]++;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    $neighborsCoord[0] -= $this->grid->getMaxHeight();
                }
                break;
            case AbstractGrid::DIRECTION_DOWNLEFT:
                $neighborsCoord[1]--;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    if($neighborsCoord[1] === -1) {
                        $neighborsCoord[1] = $this->grid->getMaxWidth() - 1;
                        $neighborsCoord[0] += $maxColOffset;
                    } else {
                        $neighborsCoord[0] -= $this->grid->getMaxHeight();
                    }
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
                    if($neighborsCoord[1] === -1) {
                        $neighborsCoord[1] = $this->grid->getMaxWidth() - 1;
                        if($neighborsCoord[0] === -1) {
                            $neighborsCoord[0] = $this->grid->getMaxHeight() + $maxColOffset - 1;
                        } else {
                            $neighborsCoord[0] += $maxColOffset;
                        }
                    } else {
                        $neighborsCoord[0] += $this->grid->getMaxHeight();
                    }
                }
                break;
            default: return null;
        }
        if(!HexagonHorizontalGrid::isValidCoord($neighborsCoord)) return null;
        return $this->grid->getBox($neighborsCoord);
    }


}
