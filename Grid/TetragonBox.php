<?php
namespace Keiwen\Utils\Grid;

class TetragonBox extends AbstractBox
{

    /**
     * @param int $direction
     * @return TetragonBox|null
     */
    public function getNeighbor(int $direction): AbstractBox
    {
        $neighborsCoord = $this->coord;
        switch($direction) {
            case AbstractGrid::DIRECTION_UP:
                $neighborsCoord[0]--;
                //if "out of grid"
                if(!$this->grid->isOnGrid($neighborsCoord)) {
                    if($this->grid->hasBorder()) {
                        //we have border => no neighbor
                        return null;
                    }
                    //we have no border => move to bottom
                    $neighborsCoord[0] = $this->grid->getMaxHeight() - 1;
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
                    //we have no border => move to left
                    $neighborsCoord[1] = 0;
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
                    //we have no border => move to top
                    $neighborsCoord[0] = 0;
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
                    //we have no border => move to right
                    $neighborsCoord[1] = $this->grid->getMaxWidth() - 1;
                }
                break;
            default: return null;
        }
        if(!TetragonGrid::isValidCoord($neighborsCoord)) return null;
        return $this->grid->getBox($neighborsCoord);
    }


}
