<?php

declare(strict_types=1);

namespace MagicLogin\Dependencies\Endroid\QrCode\Writer;

use MagicLogin\Dependencies\Endroid\QrCode\Label\LabelInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Logo\LogoInterface;
use MagicLogin\Dependencies\Endroid\QrCode\QrCodeInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Writer\Result\ResultInterface;

interface WriterInterface
{
    /** @param array<mixed> $options */
    public function write(QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = []): ResultInterface;
}
