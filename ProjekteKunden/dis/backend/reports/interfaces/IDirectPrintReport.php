<?php


namespace app\reports\interfaces;


interface IDirectPrintReport extends IPdfReport
{
    /**
     * @param $pdfFile string full path of the file to print
     * @return mixed
     */
    function getPrintOptions($pdfFile);
}
