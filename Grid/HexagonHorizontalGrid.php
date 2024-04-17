<?php
namespace Keiwen\Utils\Grid;


use Keiwen\Utils\Math\Divisibility;

class HexagonHorizontalGrid extends AbstractGrid
{


    /**
     * If no border, max height should be even
     * @inheritdoc
     * @throws GridException odd height without border
     */
    public function __construct(int $maxWidth = 0, int $maxHeight = 0, bool $hasBorder = true)
    {
        if(!$hasBorder && $maxHeight > 0 && !Divisibility::isNumberEven($maxHeight)) {
            throw new GridException('HexagonHorizontalGrid without border should have maxHeight as even number');
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
            static::DIRECTION_UPRIGHT,
            static::DIRECTION_RIGHT,
            static::DIRECTION_DOWNRIGHT,
            static::DIRECTION_DOWNLEFT,
            static::DIRECTION_LEFT,
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
        //up
        if ($coord[0] < 0) return false;
        //bottom
        if ($this->maxHeight !== 0 && $coord[0] >= $this->maxHeight) return false;

        $rowOffset = ceil($coord[0] / 2);
        //left
        if($coord[1] < $rowOffset) return false;
        //right
        if ($this->maxWidth !== 0 && $coord[1] >= ($this->maxWidth + $rowOffset)) return false;

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
        if ($this->maxHeight !== 0 && $coord[0] === $this->maxHeight - 1) return true;

        $rowOffset = ceil($coord[0] / 2);
        if($coord[1] === $rowOffset) return true;
        if ($this->maxWidth !== 0 && $coord[1] === ($this->maxWidth + $rowOffset - 1)) return true;
        return false;
    }

}
