<?php

declare(strict_types=1);

namespace MagicLogin\Dependencies\Endroid\QrCode\Logo;

interface LogoInterface
{
    public function getPath(): string;

    public function getResizeToWidth(): ?int;

    public function getResizeToHeight(): ?int;

    public function getPunchoutBackground(): bool;
}
