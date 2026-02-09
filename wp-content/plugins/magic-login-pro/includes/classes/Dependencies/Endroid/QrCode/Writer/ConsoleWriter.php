<?php

declare(strict_types=1);

namespace MagicLogin\Dependencies\Endroid\QrCode\Writer;

use MagicLogin\Dependencies\Endroid\QrCode\Bacon\MatrixFactory;
use MagicLogin\Dependencies\Endroid\QrCode\Label\LabelInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Logo\LogoInterface;
use MagicLogin\Dependencies\Endroid\QrCode\QrCodeInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Writer\Result\ConsoleResult;
use MagicLogin\Dependencies\Endroid\QrCode\Writer\Result\ResultInterface;

/**
 * Writer of QR Code for CLI.
 */
class ConsoleWriter implements WriterInterface
{
    /**
     * {@inheritDoc}
     */
    public function write(QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, $options = []): ResultInterface
    {
        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);

        return new ConsoleResult($matrix, $qrCode->getForegroundColor(), $qrCode->getBackgroundColor());
    }
}
