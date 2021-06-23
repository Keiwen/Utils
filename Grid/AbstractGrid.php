<?php
namespace Keiwen\Utils\Grid;

abstract class AbstractGrid
{

    protected $maxWidth = 0;
    protected $maxHeight = 0;

    /** @var AbstractBox[] $boxes */
    protected $boxes = array();

    protected $hasBorder = true;

    public const DIRECTION_UP = 1;
    public const DIRECTION_RIGHT = 2;
    public const DIRECTION_DOWN = 3;
    public const DIRECTION_LEFT = 4;
    public const DIRECTION_UPRIGHT = 5;
    public const DIRECTION_DOWNRIGHT = 6;
    public const DIRECTION_DOWNLEFT = 7;
    public const DIRECTION_UPLEFT = 8;


    /**
     * AbstractGrid constructor.
     * @param int $maxWidth max number of boxes in width (0 = unlimited)
     * @param int $maxHeight max number of boxes in height (0 = unlimited)
     * @param bool $hasBorder
     */
    public function __construct(int $maxWidth = 0, int $maxHeight = 0, bool $hasBorder = true)
    {
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
        $this->hasBorder = $hasBorder;
    }


    /**
     * generate string with coord to use as key in boxes list
     * @param int[] $coord
     * @return string
     */
    protected function getFlatCoord(array $coord)
    {
        return implode(',', $coord);
    }


    /**
     * get box to given coordinates or create a new one
     * @param int[] $coord
     * @return AbstractBox
     */
    public function getBox(array $coord)
    {
        if (!static::isValidCoord($coord)) return null;
        if (!$this->isOnGrid($coord)) return null;
        $flatCoord = $this->getFlatCoord($coord);
        if(isset($this->boxes[$flatCoord])) {
            return $this->boxes[$flatCoord];
        }
        return $this->createBox($coord);
    }


    /**
     * @return AbstractBox[]
     */
    public function getBoxes()
    {
        return $this->boxes;
    }

    /**
     * create a new box
     * @param int[] $coord
     * @return AbstractBox
     */
    protected abstract function createBox(array $coord): AbstractBox;

    /**
     * add box to grid. Replace any existing box with same coordinates
     * @param AbstractBox $box
     */
    public function addBox(AbstractBox $box)
    {
        $this->boxes[$this->getFlatCoord($box->getCoord())] = $box;
        $box->setGrid($this);
    }

    /**
     * remove box to coordinates
     * @param AbstractBox $box
     */
    public function removeBox(AbstractBox $box)
    {
        unset($this->boxes[$this->getFlatCoord($box->getCoord())]);
    }


    abstract public function getBoxNeighborDirections();


    public function hasBorder()
    {
        return $this->hasBorder;
    }


    public function getMaxWidth()
    {
        return $this->maxWidth;
    }

    public function getMaxHeight()
    {
        return $this->maxHeight;
    }


    /**
     * @param int[] $coord
     * @return bool
     */
    public function isOnGrid(array $coord): bool
    {
        if (!static::isValidCoord($coord)) return false;
        if ($coord[0] < 0) return false;
        if ($coord[1] < 0) return false;
        if ($this->maxHeight !== 0 && $coord[0] >= $this->maxHeight) return false;
        if ($this->maxWidth !== 0 && $coord[1] >= $this->maxWidth) return false;
        return true;
    }


    /**
     * @param int[] $coord
     * @return bool
     */
    public function isOnBorder(array $coord): bool
    {
        if (!static::isValidCoord($coord)) return false;
        if (!$this->hasBorder) return false;
        if ($coord[0] === 0) return true;
        if ($coord[1] === 0) return true;
        if ($this->maxHeight !== 0 && $coord[0] === $this->maxHeight - 1) return true;
        if ($this->maxWidth !== 0 && $coord[1] === $this->maxWidth - 1) return true;
        return false;
    }


    /**
     * @param array $coord
     * @param string $msg invalid message if return is false
     * @return bool
     */
    static public function isValidCoord(array $coord, string &$msg = '')
    {
        if(count($coord) !== 2) {
            $msg = 'Coordinates should contain exactly 2 elements';
            return false;
        }
        $coord = array_values($coord);
        if(!is_int($coord[0] || !is_int($coord[1]))) {
            $msg = 'Coordinates should be integers';
            return false;
        }
        return true;
    }

}
