<?php

namespace App\Support;

use App\Models\Playground;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Str;

class PlaygroundQrTarget
{
    public const VERSION = 2;

    public static function png(Playground $playground): string
    {
        $matrix = Builder::create()
            ->writer(new SvgWriter)
            ->data($playground->qr_url)
            ->size(900)
            ->margin(54)
            ->build()
            ->getMatrix();

        $image = imagecreatetruecolor(1200, 1400);
        $background = imagecolorallocate($image, 238, 246, 255);
        $white = imagecolorallocate($image, 255, 255, 255);
        $navy = imagecolorallocate($image, 11, 31, 79);
        $blue = imagecolorallocate($image, 37, 99, 235);
        $cyan = imagecolorallocate($image, 14, 165, 233);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $background);

        imagefilledrectangle($image, 0, 0, 1199, 126, $navy);
        imagefilledellipse($image, 76, 64, 58, 58, $cyan);
        imagefilledrectangle($image, 1080, 27, 1160, 45, $blue);
        imagefilledrectangle($image, 1105, 55, 1160, 73, $cyan);
        imagefilledrectangle($image, 1060, 83, 1160, 101, $white);

        self::drawText($image, 'MINDAR PLAYGROUND', 150, 40, 3, $white, $navy);
        self::drawText(
            $image,
            Str::upper(Str::limit(Str::ascii($playground->name), 34, '')),
            150,
            78,
            2,
            $white,
            $navy
        );

        imagefilledrectangle($image, 111, 157, 1089, 1135, $white);
        imagerectangle($image, 110, 156, 1090, 1136, $blue);

        $qrX = 150;
        $qrY = 196;
        $blockSize = $matrix->getBlockSize();
        $margin = $matrix->getMarginLeft();
        $blockCount = $matrix->getBlockCount();

        for ($row = 0; $row < $blockCount; $row++) {
            for ($column = 0; $column < $blockCount; $column++) {
                if ($matrix->getBlockValue($row, $column) !== 1) {
                    continue;
                }

                $left = $qrX + $margin + ($column * $blockSize);
                $top = $qrY + $margin + ($row * $blockSize);
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

        self::drawText($image, 'SCAN TO OPEN  -  POINT CAMERA AT THE WHOLE CARD', 150, 1175, 2, $navy, $background);

        $fingerprint = hash('sha256', $playground->qr_token, true);
        $colors = [$navy, $blue, $cyan];

        for ($index = 0; $index < 16; $index++) {
            $value = ord($fingerprint[$index]);
            $left = 150 + ($index * 57);
            $height = 25 + ($value % 90);
            $color = $colors[$value % count($colors)];
            imagefilledrectangle($image, $left, 1350 - $height, $left + 37, 1350, $color);

            if (($value & 1) === 1) {
                imagefilledellipse($image, $left + 19, 1300 - $height, 22, 22, $color);
            }
        }

        ob_start();
        imagepng($image, null, 9);
        $png = (string) ob_get_clean();
        imagedestroy($image);

        return $png;
    }

    private static function drawText($image, string $text, int $x, int $y, int $scale, int $color, int $background): void
    {
        $font = 5;
        $sourceWidth = max(1, imagefontwidth($font) * strlen($text));
        $sourceHeight = imagefontheight($font);
        $textImage = imagecreatetruecolor($sourceWidth, $sourceHeight);
        imagefill($textImage, 0, 0, $background);
        imagestring($textImage, $font, 0, 0, $text, $color);
        imagecopyresized(
            $image,
            $textImage,
            $x,
            $y,
            0,
            0,
            $sourceWidth * $scale,
            $sourceHeight * $scale,
            $sourceWidth,
            $sourceHeight
        );
        imagedestroy($textImage);
    }
}
