<?php
namespace Keiwen\Utils\Grid;


use Keiwen\Utils\Math\Divisibility;

class HexagonHorizontalGrid extends AbstractGrid
{


    /**
     * If no border, max width should be even
     * @inheritdoc
     * @throws GridException odd width without border
     */
    public function __construct(int $maxWidth = 0, int $maxHeight = 0, bool $hasBorder = true)
    {
        if(!$hasBorder && $maxWidth > 0 && !Divisibility::isNumberEven($maxWidth)) {
            throw new GridException('HexagonHorizontalGrid without border should have maxWidth as even number');
        }
        parent::__construct($maxWidth, $maxHeight, $hasBorder);
    }



    /**
     * create a new box
     * @param int[] $coord
     * @return HexagonHorizontalBox
     */
    protected function createBox(array $coord): AbstractBox
    {
        $box = new HexagonHorizontalBox($coord);
        $this->addBox($box);
        return $box;
    }


    public function getBoxNeighborDirections()
    {
        return array(
            static::DIRECTION_UP,
            static::DIRECTION_UPRIGHT,
            static::DIRECTION_DOWNRIGHT,
            static::DIRECTION_DOWN,
            static::DIRECTION_DOWNLEFT,
            static::DIRECTION_UPLEFT,
        );
    }

    /**
     * @param int[] $coord
     * @return bool
     */
    public function isOnGrid(array $coord): bool
    {
        if (!static::isValidCoord($coord)) return false;
        //left
        if ($coord[1] < 0) return false;
        //right
        if ($this->maxWidth !== 0 && $coord[1] >= $this->maxWidth) return false;

        $colOffset = ceil($coord[1] / 2);
        //top
        if($coord[0] < $colOffset) return false;
        //bottom
        if ($this->maxHeight !== 0 && $coord[0] >= ($this->maxHeight + $colOffset)) return false;

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
        if ($coord[1] === 0) return true;
        if ($this->maxWidth !== 0 && $coord[1] === $this->maxWidth - 1) return true;

        $colOffset = ceil($coord[1] / 2);
        if($coord[0] === $colOffset) return true;
        if ($this->maxHeight !== 0 && $coord[0] === ($this->maxHeight + $colOffset - 1)) return true;
        return false;
    }

}
