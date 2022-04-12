<?php


namespace app\reports\interfaces;


interface IStyledReport extends IReport
{
    /**
     * Returns the content of the css file in the file system
     * or a string of css rules.
     * @return string CSS content
     */
    function getCss();
}