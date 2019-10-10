<?php

if (! defined("ABSPATH")) {
    exit;
} // Exit if accessed directly

if (class_exists("WCMP_Export_Pdf")) {
    return;
}

class WCMP_Export_Pdf
{
    public function __construct(string $pdf_data, array $order_ids)
    {
        $outputMode = WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY);

        if ($outputMode === "display") {
            self::stream_pdf($pdf_data, $order_ids);
        } else {
            self::download_pdf($pdf_data, $order_ids);
        }
    }

    /**
     * @param $pdf_data
     * @param $order_ids
     */
    public static function stream_pdf($pdf_data, array $order_ids): void
    {
        $filename = self::get_filename($order_ids);

        header("Content-type: application/pdf");
        header("Content-Disposition: inline; filename=\"$filename\"");
        echo base64_encode($pdf_data);
        die();
    }

    /**
     * @param $pdf_data
     * @param $order_ids
     */
    public static function download_pdf($pdf_data, array $order_ids): void
    {
        $filename = self::get_filename($order_ids);

        header("Content-Description: File Transfer");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Transfer-Encoding: binary");
        header("Connection: Keep-Alive");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
        echo $pdf_data;
        die();
    }

    /**
     * @param array $order_ids
     *
     * @return string
     */
    private static function get_filename(array $order_ids): string
    {
        $filename = "MyParcelBE-" . date("Y-m-d") . ".pdf";

        return apply_filters("wcmyparcelbe_filename", $filename, $order_ids);
    }
}

