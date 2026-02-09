<?php

declare(strict_types=1);

namespace MagicLogin\Dependencies\Endroid\QrCode\Bacon;

use MagicLogin\Dependencies\BaconQrCode\Common\ErrorCorrectionLevel;
use MagicLogin\Dependencies\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use MagicLogin\Dependencies\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use MagicLogin\Dependencies\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use MagicLogin\Dependencies\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use MagicLogin\Dependencies\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;

final class ErrorCorrectionLevelConverter
{
    public static function convertToBaconErrorCorrectionLevel(ErrorCorrectionLevelInterface $errorCorrectionLevel): ErrorCorrectionLevel
    {
        if ($errorCorrectionLevel instanceof ErrorCorrectionLevelLow) {
            return ErrorCorrectionLevel::valueOf('L');
        } elseif ($errorCorrectionLevel instanceof ErrorCorrectionLevelMedium) {
            return ErrorCorrectionLevel::valueOf('M');
        } elseif ($errorCorrectionLevel instanceof ErrorCorrectionLevelQuartile) {
            return ErrorCorrectionLevel::valueOf('Q');
        } elseif ($errorCorrectionLevel instanceof ErrorCorrectionLevelHigh) {
            return ErrorCorrectionLevel::valueOf('H');
        }

        throw new \Exception('Error correction level could not be converted');
    }
}
