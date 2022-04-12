<?php


namespace app\reports\interfaces;


interface IHtmlReport extends IStyledReport
{
    /**
     * Returns the content of a js file in the file system
     * or a string of javascript code.
     * @return string Js content
     */
    function getJs();
}