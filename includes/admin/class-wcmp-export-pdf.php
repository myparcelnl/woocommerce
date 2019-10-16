<?php

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
     * @param string $response
     */
    public function __construct(string $response)
    {
        self::$data = $response;
        self::outputPdfUrl();
    }

    private static function outputPdfUrl(): void
    {
        echo self::$data;
        die();
    }
}

