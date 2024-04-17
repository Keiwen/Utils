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

}
