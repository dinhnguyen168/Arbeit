<?php


namespace app\reports\interfaces;


interface ILabelsReport
{
    /**
     * @return int[] of width and height in mm [width, height] e.g. [200, 180]
     */
    function getLabelSize();
}