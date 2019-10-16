<?php

use MyParcelNL\Sdk\src\Support\Arr;

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMP_Export_Pdf")) {
    return;
}

class WCMP_Export_Pdf
{
    /**
     * @var string
     */
    private static $data;

    /**
     * WCMP_Export_Pdf constructor.
     *
     * @param array $response
     * @param bool  $download
     */
    public function __construct(array $response, bool $download = true)
    {
        self::$data = $response;

        if ($download) {
            self::outputPdfUrl();
        } else {
            self::outputPdf();
        }
    }

    private static function outputPdfUrl(): void
    {
        $url = Arr::get(self::$data, "body.data.pdfs.url");

        echo $url;
        die();
    }

    private static function outputPdf(): void
    {
        echo json_decode(self::$data);
        die();
    }
}

