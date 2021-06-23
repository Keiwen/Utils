<?php
namespace Keiwen\Utils\Grid;

abstract class AbstractBox
{

    /** @var int[] $coord */
    protected $coord = array();
    /** @var AbstractGrid $grid */
    protected $grid;


    /**
     * AbstractBox constructor.
     * @param int[] $coord
     */
    public function __construct(array $coord)
    {
        $this->moveTo($coord);
    }


    /**
     * @return int[]
     */
    public function getCoord()
    {
        return $this->coord;
    }


    /**
     * @param int[] $coord
     * @throws KeiwenGridException Invalid coordinates
     */
    public function moveTo(array $coord)
    {
        $msg = '';
        if(!AbstractGrid::isValidCoord($coord, $msg)) {
            throw new KeiwenGridException($msg);
        }
        $this->coord = array_values($coord);
    }


    /**
     * @return AbstractGrid
     */
    public function getGrid()
    {
        return $this->grid;
    }

    /**
     * define grid of current box. Does NOT add current box to grid
     * @param AbstractGrid $grid
     */
    public function setGrid(AbstractGrid $grid)
    {
        $this->grid = $grid;
    }


    /**
     * @return bool
     */
    public function isOnBorder()
    {
        return $this->grid->isOnBorder($this->coord);
    }

    /**
     * @return AbstractBox[] array key = direction
     */
    public function getNeighbors() {
        $neighbors = array();
        foreach($this->grid->getBoxNeighborDirections() as $direction) {
            $neighbor = $this->getNeighbor($direction);
            if($neighbor !== null) $neighbors[$direction] = $this->getNeighbor($direction);
        }
        return $neighbors;
    }


    /**
     * @param int $direction
     * @return AbstractBox|null
     */
    abstract public function getNeighbor(int $direction): AbstractBox;

}
