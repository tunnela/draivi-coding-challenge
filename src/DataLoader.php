<?php

namespace Tunnela\DraiviCodingChallenge;

use Smalot\PdfParser\Parser as PdfParser;
use Smalot\PdfParser\Config as PdfConfig;
use PhpOffice\PhpSpreadsheet\IOFactory as ExcelParser;
use Nesk\Puphpeteer\Puppeteer;
use Nesk\Rialto\Data\JsFunction;

class DataLoader
{
    protected $options = [];

    public function __construct($options = [])
    {
        $defaults = [
            'nodePath' => 'node'
        ];

        $this->options = array_merge($defaults, $options);
    }

    public function load($source)
    {
        if (preg_match('#.js$#', $source)) {
            return $this->loadNodeScript($source);
        }
        return $this->loadUrl($source);
    }

    public function loadNodeScript($script) 
    {
        $result = exec($this->options['nodePath'] . ' ' . escapeshellarg($script));

        if (!$result) {
            throw new \Exception("Could not load via node script!");
        }
        $ext = $this->extension($result);

        return $this->parse($result, $ext);
    }

    public function loadUrl($url) 
    {
        $ext = $this->extension($url);
        $tempFilePath = tempnam(sys_get_temp_dir(), 'data-loader') . '.' . $ext;
        
        file_put_contents($tempFilePath, file_get_contents($url));

        return $this->parse($tempFilePath, $ext);
    }

    protected function extension($url)
    {
        return strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    }

    protected function parse($path, $ext)
    {
        $data = [];

        if (in_array($ext, ['xls', 'xlsx', 'xml', 'ods', 'slk', 'gnumeric', 'csv'])) {
            $reader = ExcelParser::createReaderForFile($path);
            $spreadsheet = $reader->load($path);

            $data = [];

            for ($i = 0, $l = $spreadsheet->getSheetCount(); $i < $l; $i++) {
                $sheet = $spreadsheet->getSheet($i);

                $data[] = $sheet->toArray(null, true, true, true);
            }
        } else if ($ext == 'pdf') {
            // fixes the presentation of extra spaces issue
            $config = new PdfConfig();
            $config->setHorizontalOffset('');

            $parser = new PdfParser([], $config);
            $pdf = $parser->parseFile($path);

            foreach ($pdf->getPages() as $page) {
                $lines = preg_split('#(\r\n|\n)+#', $page->getText());

                foreach ($lines as &$line) {
                    $line = preg_split('#\t+#', $line);

                    unset($line);
                }
                $data[] = $lines;
            }
        }
        return $data;
    }
}