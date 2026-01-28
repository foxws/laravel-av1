<?php

declare(strict_types=1);

namespace Foxws\AV1\FFmpeg\Enums;

enum HardwareAccelMethod: string
{
    case QSV = 'qsv';
    case VAAPI = 'vaapi';
    case CUDA = 'cuda';
    case VULKAN = 'vulkan';

    /**
     * Get the display name for the hardware acceleration method
     */
    public function label(): string
    {
        return match ($this) {
            self::QSV => 'Intel Quick Sync',
            self::VAAPI => 'VA-API (Linux)',
            self::CUDA => 'NVIDIA CUDA',
            self::VULKAN => 'Vulkan',
        };
    }
}
