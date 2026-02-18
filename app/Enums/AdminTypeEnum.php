<?php

namespace App\Enums;

enum AdminTypeEnum: string
{
    case SUPER = 'super';
    case MANAGER = 'manager';
    case OPERATOR = 'operator';

    public function label(): string
    {
        return match($this) {
            self::SUPER => 'Super Admin',
            self::MANAGER => 'Manager',
            self::OPERATOR => 'Operator',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::SUPER => 'Full system access and admin management',
            self::MANAGER => 'Can manage fees, workflows, and operators',
            self::OPERATOR => 'Can approve payments and view reports',
        };
    }

    /**
     * Get all admin type values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
