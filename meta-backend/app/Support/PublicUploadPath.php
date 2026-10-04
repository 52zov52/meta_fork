<?php

namespace App\Support;

use Carbon\Carbon;

final class PublicUploadPath
{
    public static function activityCover(): string
    {
        return 'files/activities/covers/'.self::month();
    }

    public static function activityAttachment(int $activityId): string
    {
        return 'files/activities/attachments/'.$activityId;
    }

    public static function memberAvatar(): string
    {
        return 'files/members/avatars/'.self::month();
    }

    public static function portfolioFile(): string
    {
        return 'files/portfolio/'.self::month();
    }

    public static function marketplaceProduct(): string
    {
        return 'files/marketplace/products/'.self::month();
    }

    private static function month(): string
    {
        return Carbon::now()->format('Y-m');
    }
}
