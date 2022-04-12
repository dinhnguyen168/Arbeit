<?php


namespace app\reports\interfaces;


interface IReport
{
    /**
     * Abstract method to generate the result of the report
     * @param $options array query parameters provided to the reportController
     * @return mixed Output of the report
     */
    public function generate($options = []);
    function output();
}