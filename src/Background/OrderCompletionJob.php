<?php

declare(strict_types=1);

namespace SCE\Background;

use SCE\Contracts\LoggerInterface;

final class OrderCompletionJob
{
    private const ACTION_HOOK = 'sce_process_completed_order';

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function register(): void
    {
        add_action('woocommerce_order_status_completed', [$this, 'scheduleOrderJob'], 20, 1);
        add_action(self::ACTION_HOOK, [$this, 'handleOrderJob'], 10, 1);
    }

    public function scheduleOrderJob(int $orderId): void
    {
        $timestamp = time() + MINUTE_IN_SECONDS;

        wp_schedule_single_event($timestamp, self::ACTION_HOOK, [$orderId]);

        $this->logger->info('order_background_job_scheduled', [
            'order_id' => $orderId,
            'run_at' => gmdate('c', $timestamp),
        ]);
    }

    public function handleOrderJob(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (!$order) {
            $this->logger->warning('order_background_job_missing_order', ['order_id' => $orderId]);

            return;
        }

        $order->update_meta_data('_sce_post_completion_processed', '1');
        $order->save();

        $this->logger->info('order_background_job_completed', [
            'order_id' => $orderId,
            'order_total' => (float) $order->get_total(),
        ]);
    }
}
