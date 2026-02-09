<?php

declare(strict_types=1);

namespace MagicLogin\Dependencies\Endroid\QrCode\Matrix;

use MagicLogin\Dependencies\Endroid\QrCode\QrCodeInterface;

interface MatrixFactoryInterface
{
    public function create(QrCodeInterface $qrCode): MatrixInterface;
}
