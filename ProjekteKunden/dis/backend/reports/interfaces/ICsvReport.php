<?php


namespace app\reports\interfaces;


interface ICsvReport extends IReport
{
    /**
     * Delimiter for CSV file
     */
    const CSV_DELIMITER = ";";
    /**
     * Enclosure of values in the CSV file
     */
    const CSV_ENCLOSURE = '"';
    /**
     * Escape character for special characters in the CSV file
     */
    const CSV_ESCAPE = "\\";
    /**
     * Newsline character for the CSV file
     */
    const CSV_NEWLINE = "\n";
}