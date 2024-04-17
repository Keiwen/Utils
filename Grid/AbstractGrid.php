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
        if (!$hasBorder) {
            if ($maxWidth <= 0) throw new GridException('A grid without border cannot have unlimited width');
            if ($maxHeight <= 0) throw new GridException('A grid without border cannot have unlimited height');
        }
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
     * @return AbstractBox|null
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
    static public function isValidCoord(?array $coord, string &$msg = ''): bool
    {
        if ($coord == null) return false;
        if(count($coord) !== 2) {
            $msg = 'Coordinates should contain exactly 2 elements';
            return false;
        }
        $coord = array_values($coord);
        if(!is_int($coord[0]) || !is_int($coord[1])) {
            $msg = 'Coordinates should be integers';
            return false;
        }
        return true;
    }

    /**
     * Call this function to adjust coord in 'normal grid', mainly for coord that should be out of the grid
     * For example when you want to get [-1;-1]
     * If there are borders and you are blocked by, this will return coord at the border ([0;0] in given example)
     * If there are no border, this will adjust coord considering grid definition (last row and last column in given example)
     * @param array $coord
     * @param bool $blockedByBorder false by default: if there is border and coord are out of grid, coord cannot be found
     * @return array|null null if coord cannot be adjusted
     */
    public function adjustCoord(array $coord, bool $blockedByBorder = false): ?array
    {
        if (!static::isValidCoord($coord)) return null;
        // if already on grid, return as this
        if ($this->isOnGrid($coord)) return $coord;
        // if we have border, and not blocked by them, cannot do anything
        if ($this->hasBorder() && !$blockedByBorder) return null;

        $adjustedCoord = $coord;
        if ($this->hasBorder()) {
            // below min row
            if ($adjustedCoord[0] < 0) $adjustedCoord[0] = 0;
            // above max row
            if ($adjustedCoord[0] >= $this->getMaxHeight()) $adjustedCoord[0] = $this->getMaxHeight() - 1;
            // below min column
            if ($adjustedCoord[1] < 0) $adjustedCoord[1] = 0;
            // above max column
            if ($adjustedCoord[1] >= $this->getMaxWidth()) $adjustedCoord[1] = $this->getMaxWidth() - 1;

            return $adjustedCoord;
        }

        // no border, consider max offset
        // should be RECURSIVE if user got coord really far away of 'normal grid'
        // after some iteration, we will be on the grid and fall back to isOnGrid() check at the beginning

        // below min row
        if ($adjustedCoord[0] < 0) $adjustedCoord[0] += $this->getMaxHeight();
        // above max row
        if ($adjustedCoord[0] >= $this->getMaxHeight()) $adjustedCoord[0] -= $this->getMaxHeight();
        // below min column
        if ($adjustedCoord[1] < 0) $adjustedCoord[1] += $this->getMaxWidth();
        // above max column
        if ($adjustedCoord[1] >= $this->getMaxWidth()) $adjustedCoord[1] -= $this->getMaxWidth();

        return $this->adjustCoord($adjustedCoord);
    }

}
