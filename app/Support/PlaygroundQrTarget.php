<?php

namespace App\Support;

use App\Models\Playground;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

class PlaygroundQrTarget
{
    public const VERSION = 3;

    public static function png(Playground $playground): string
    {
        $matrix = Builder::create()
            ->writer(new SvgWriter)
            ->data($playground->qr_url)
            ->size(840)
            ->margin(0)
            ->build()
            ->getMatrix();

        $image = imagecreatetruecolor(1000, 1000);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        $blockSize = $matrix->getBlockSize();
        $blockCount = $matrix->getBlockCount();
        $qrPixelSize = $blockCount * $blockSize;
        $qrX = (int) floor((1000 - $qrPixelSize) / 2);
        $qrY = (int) floor((1000 - $qrPixelSize) / 2);

        for ($row = 0; $row < $blockCount; $row++) {
            for ($column = 0; $column < $blockCount; $column++) {
                if ($matrix->getBlockValue($row, $column) !== 1) {
                    continue;
                }

                $left = $qrX + ($column * $blockSize);
                $top = $qrY + ($row * $blockSize);
                imagefilledrectangle(
                    $image,
                    $left,
                    $top,
                    $left + $blockSize - 1,
                    $top + $blockSize - 1,
                    $black
                );
            }
        }

        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }
}
