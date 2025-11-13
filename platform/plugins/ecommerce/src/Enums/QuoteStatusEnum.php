<?php

namespace Botble\Ecommerce\Enums;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;

/**
 * @method static QuoteStatusEnum PENDING()
 * @method static QuoteStatusEnum UNDER_REVIEW()
 * @method static QuoteStatusEnum QUOTED()
 * @method static QuoteStatusEnum APPROVED()
 * @method static QuoteStatusEnum REJECTED()
 */
class QuoteStatusEnum extends Enum
{
    public const NEW = 'new';

    public const UNDER_REVIEW = 'under_review';
    public const QUOTED = 'quoted';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    public static $langPath = 'plugins/ecommerce::order.statuses';

    public function toHtml(): HtmlString|string
    {
        $color = match ($this->value) {
            self::NEW => 'warning',
            self::UNDER_REVIEW => 'info',
            self::QUOTED => 'success',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            default => 'primary',
        };

        return BaseHelper::renderBadge($this->label(), $color, icon: $this->getIcon());
    }

    public function getIcon(): string
    {
        return match ($this->value) {
            self::NEW => 'ti ti-clock',
            self::UNDER_REVIEW => 'ti ti-refresh',
            self::QUOTED => 'ti ti-circle-check',
            self::APPROVED => 'ti ti-circle-check',
            self::REJECTED => 'ti ti-circle-x',
            default => 'ti ti-circle',
        };
    }
}
