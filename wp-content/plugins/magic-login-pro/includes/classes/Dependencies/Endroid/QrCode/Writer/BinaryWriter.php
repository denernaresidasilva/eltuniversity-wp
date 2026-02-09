<?php

declare(strict_types=1);

namespace MagicLogin\Dependencies\Endroid\QrCode\Writer;

use MagicLogin\Dependencies\Endroid\QrCode\Bacon\MatrixFactory;
use MagicLogin\Dependencies\Endroid\QrCode\Label\LabelInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Logo\LogoInterface;
use MagicLogin\Dependencies\Endroid\QrCode\QrCodeInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Writer\Result\BinaryResult;
use MagicLogin\Dependencies\Endroid\QrCode\Writer\Result\ResultInterface;

final class BinaryWriter implements WriterInterface
{
    public function write(QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = []): ResultInterface
    {
        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);

        return new BinaryResult($matrix);
    }
}
