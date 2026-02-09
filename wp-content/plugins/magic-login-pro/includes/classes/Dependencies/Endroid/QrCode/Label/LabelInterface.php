<?php

declare(strict_types=1);

namespace MagicLogin\Dependencies\Endroid\QrCode\Label;

use MagicLogin\Dependencies\Endroid\QrCode\Color\ColorInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Label\Alignment\LabelAlignmentInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Label\Font\FontInterface;
use MagicLogin\Dependencies\Endroid\QrCode\Label\Margin\MarginInterface;

interface LabelInterface
{
    public function getText(): string;

    public function getFont(): FontInterface;

    public function getAlignment(): LabelAlignmentInterface;

    public function getMargin(): MarginInterface;

    public function getTextColor(): ColorInterface;
}
