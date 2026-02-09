<?php

declare(strict_types=1);

namespace MagicLogin\Dependencies\Endroid\QrCode\Bacon;

use MagicLogin\Dependencies\BaconQrCode\Encoder\Encoder;
use MagicLogin\Dependencies\Endroid\QrCode\Matrix\Matrix;
use MagicLogin\Dependencies\Endroid\QrCode\Matrix\MatrixFactoryInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Matrix\MatrixInterface;
use MagicLogin\Dependencies\Endroid\QrCode\QrCodeInterface;

final class MatrixFactory implements MatrixFactoryInterface
{
    public function create(QrCodeInterface $qrCode): MatrixInterface
    {
        $baconErrorCorrectionLevel = ErrorCorrectionLevelConverter::convertToBaconErrorCorrectionLevel($qrCode->getErrorCorrectionLevel());
        $baconMatrix = Encoder::encode($qrCode->getData(), $baconErrorCorrectionLevel, strval($qrCode->getEncoding()))->getMatrix();

        $blockValues = [];
        $columnCount = $baconMatrix->getWidth();
        $rowCount = $baconMatrix->getHeight();
        for ($rowIndex = 0; $rowIndex < $rowCount; ++$rowIndex) {
            $blockValues[$rowIndex] = [];
            for ($columnIndex = 0; $columnIndex < $columnCount; ++$columnIndex) {
                $blockValues[$rowIndex][$columnIndex] = $baconMatrix->get($columnIndex, $rowIndex);
            }
        }

        return new Matrix($blockValues, $qrCode->getSize(), $qrCode->getMargin(), $qrCode->getRoundBlockSizeMode());
    }
}
