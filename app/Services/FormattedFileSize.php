<?php

declare(strict_types = 1);

namespace App\Services;

class FormattedFileSize
{
    /**
     * @var array
     */
    private static $sizeSteps = [
        [
            'tag' => 'GB',
            'value' => 1024 ** 3
        ],
        [
            'tag' => 'MB',
            'value' => 1024 ** 2
        ],
        [
            'tag' => 'KB',
            'value' => 1024
        ],
        [
            'tag' => 'B',
            'value' => 1
        ]
    ];

    /**
     * @param float $size
     * @return string
     */
    public static function getFormattedFileSize(float $size): string
    {
        $result = '0';
        foreach (static::$sizeSteps as $sizeStep) {
            if ($size >= $sizeStep['value']) {
                $result = $size / $sizeStep['value'];
                $result = str_replace('.', ',' , (string)round($result, 2)) . ' ' . $sizeStep['tag'];
                break;
            }
        }
        return $result;
    }
}
