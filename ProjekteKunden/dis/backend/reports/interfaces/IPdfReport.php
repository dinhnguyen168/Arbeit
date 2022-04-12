<?php


namespace app\reports\interfaces;


use Mpdf\Mpdf;

/**
 * Interface IPdfReport
 * @package app\reports\interfaces
 *
 */
interface IPdfReport extends IStyledReport
{
    /**
     * initialize the Mpdf object to be returned using getPdf method
     * @see IPdfReport::getPdf()
     * @return Mpdf
     */
    function initPdf();

    /**
     * @return Mpdf
     */
    function getPdf();

    function getPdfFilename();
}