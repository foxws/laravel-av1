<?php

declare(strict_types=1);

namespace Foxws\AV1\FFmpeg\HardwareAcceleration\Enums;

enum HardwareEncoder: string
{
    case QSV = 'av1_qsv';
    case AMF = 'av1_amf';
    case NVENC = 'av1_nvenc';

    /**
     * Get the display name for the encoder
     */
    public function label(): string
    {
        return match ($this) {
            self::QSV => 'Intel Quick Sync Video',
            self::AMF => 'AMD Advanced Media Framework',
            self::NVENC => 'NVIDIA NVENC',
        };
    }

    /**
     * Get the priority order (lower = higher priority)
     */
    public function priority(): int
    {
        return match ($this) {
            self::QSV => 0,
            self::AMF => 1,
            self::NVENC => 2,
        };
    }
}
