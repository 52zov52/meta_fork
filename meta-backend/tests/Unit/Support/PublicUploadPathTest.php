<?php

use App\Support\PublicUploadPath;
use Carbon\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('builds unified public upload directories for dated files', function () {
    Carbon::setTestNow('2026-05-07 12:00:00');

    expect(PublicUploadPath::activityCover())->toBe('files/activities/covers/2026-05')
        ->and(PublicUploadPath::memberAvatar())->toBe('files/members/avatars/2026-05')
        ->and(PublicUploadPath::portfolioFile())->toBe('files/portfolio/2026-05')
        ->and(PublicUploadPath::marketplaceProduct())->toBe('files/marketplace/products/2026-05');
});

it('builds activity attachment directories under the activity id', function () {
    expect(PublicUploadPath::activityAttachment(42))->toBe('files/activities/attachments/42');
});
