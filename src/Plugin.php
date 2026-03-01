<?php

declare(strict_types=1);

namespace SCE;

use SCE\Admin\SettingsPage;
use SCE\Analytics\CheckoutAnalyticsRecorder;
use SCE\Background\OrderCompletionJob;
use SCE\Checkout\ConditionalFeeService;
use SCE\Checkout\DynamicPriceService;
use SCE\Logging\EventLogger;

final class Plugin
{
    public function boot(): void
    {
        $settingsPage = new SettingsPage();
        $settingsPage->register();

        $logger = new EventLogger();

        $feeService = new ConditionalFeeService($logger);
        $feeService->register();

        $dynamicPriceService = new DynamicPriceService($logger);
        $dynamicPriceService->register();

        $analyticsRecorder = new CheckoutAnalyticsRecorder($logger);
        $analyticsRecorder->register();

        $orderCompletionJob = new OrderCompletionJob($logger);
        $orderCompletionJob->register();
    }
}
