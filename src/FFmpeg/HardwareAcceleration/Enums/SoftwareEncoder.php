<?php

declare(strict_types=1);

namespace Foxws\AV1\FFmpeg\HardwareAcceleration\Enums;

enum SoftwareEncoder: string
{
    case SVT_AV1 = 'libsvtav1';
    case AOM_AV1 = 'libaom-av1';
    case RAV1E = 'librav1e';

    /**
     * Get the display name for the encoder
     */
    public function label(): string
    {
        return match ($this) {
            self::SVT_AV1 => 'SVT-AV1 (CPU)',
            self::AOM_AV1 => 'AOM AV1 (CPU)',
            self::RAV1E => 'rav1e (CPU)',
        };
    }

    /**
     * Get the priority order (lower = higher priority, offset by 100)
     */
    public function priority(): int
    {
        return match ($this) {
            self::SVT_AV1 => 100,
            self::AOM_AV1 => 101,
            self::RAV1E => 102,
        };
    }
}
