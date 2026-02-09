<?php
declare(strict_types = 1);

namespace MagicLogin\Dependencies\BaconQrCode\Renderer;

use MagicLogin\Dependencies\BaconQrCode\Encoder\QrCode;

interface RendererInterface
{
    public function render(QrCode $qrCode) : string;
}
