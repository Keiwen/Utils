<?php
namespace Keiwen\Utils\Grid;


use Keiwen\Utils\Math\Divisibility;

class HexagonVerticalGrid extends AbstractGrid
{


    /**
     * If no border, max width should be even
     * @inheritdoc
     * @throws GridException odd width without border
     */
    public function __construct(int $maxWidth = 0, int $maxHeight = 0, bool $hasBorder = true)
    {
        if(!$hasBorder && $maxWidth > 0 && !Divisibility::isNumberEven($maxWidth)) {
            throw new GridException('HexagonVerticalGrid without border should have maxWidth as even number');
        }
        parent::__construct($maxWidth, $maxHeight, $hasBorder);
    }



    /**
     * create a new box
     * @param int[] $coord
     * @return HexagonVerticalBox
     */
    protected function createBox(array $coord): AbstractBox
    {
        $box = new HexagonVerticalBox($coord);
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

}
