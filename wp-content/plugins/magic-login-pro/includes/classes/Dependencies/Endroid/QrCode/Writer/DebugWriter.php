<?php

declare(strict_types=1);

namespace MagicLogin\Dependencies\Endroid\QrCode\Writer;

use MagicLogin\Dependencies\Endroid\QrCode\Bacon\MatrixFactory;
use MagicLogin\Dependencies\Endroid\QrCode\Label\LabelInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Logo\LogoInterface;
use MagicLogin\Dependencies\Endroid\QrCode\QrCodeInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Writer\Result\DebugResult;
use MagicLogin\Dependencies\Endroid\QrCode\Writer\Result\ResultInterface;

final class DebugWriter implements WriterInterface, ValidatingWriterInterface
{
    public function write(QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = []): ResultInterface
    {
        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);

        return new DebugResult($matrix, $qrCode, $logo, $label, $options);
    }

    public function validateResult(ResultInterface $result, string $expectedData): void
    {
        if (!$result instanceof DebugResult) {
            throw new \Exception('Unable to write logo: instance of DebugResult expected');
        }

        $result->setValidateResult(true);
    }
}
