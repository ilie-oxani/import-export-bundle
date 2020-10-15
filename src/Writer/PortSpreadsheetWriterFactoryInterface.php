<?php

declare(strict_types=1);

namespace LWC\ImportExportBundle\Writer;

interface PortSpreadsheetWriterFactoryInterface
{
    public function get(string $filename): \Port\Spreadsheet\SpreadsheetWriter;
}
