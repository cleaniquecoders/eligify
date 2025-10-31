<?php

declare(strict_types=1);

namespace CleaniqueCoders\Eligify\Support;

final class Theme
{
    public static function isBootstrap(): bool
    {
        return config('eligify.ui.theme') === 'bootstrap';
    }

    public static function isTailwind(): bool
    {
        return ! self::isBootstrap();
    }

    public static function classes(string $token): string
    {
        $tw = [
            'btn.primary' => 'px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500',
            'btn.secondary' => 'px-3 py-2 text-sm border rounded',
            'badge' => 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800',
            'input' => 'w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
            'select' => 'w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500',
            'textarea' => 'w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono',
            'card' => 'bg-white border rounded p-4',
            'alert.success' => 'p-3 rounded bg-green-100 text-green-800 text-sm',
            'checkbox' => 'rounded border-gray-300 text-blue-600 focus:ring-blue-500',
            'radio' => 'rounded-full border-gray-300 text-blue-600 focus:ring-blue-500',
        ];

        $bs = [
            'btn.primary' => 'btn btn-primary btn-sm',
            'btn.secondary' => 'btn btn-outline-secondary btn-sm',
            'badge' => 'badge text-bg-secondary',
            'input' => 'form-control form-control-sm',
            'select' => 'form-select form-select-sm',
            'textarea' => 'form-control form-control-sm',
            'card' => 'card card-body p-3',
            'alert.success' => 'alert alert-success py-2 px-3 mb-0',
            'checkbox' => 'form-check-input',
            'radio' => 'form-check-input',
        ];

        $map = self::isBootstrap() ? $bs : $tw;

        return $map[$token] ?? '';
    }
}
