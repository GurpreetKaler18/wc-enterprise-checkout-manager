<?php

declare(strict_types=1);

namespace SCE\Logging;

use SCE\Contracts\LoggerInterface;

final class EventLogger implements LoggerInterface
{
    private const SOURCE = 'smart-checkout-enhancer';

    /**
     * @param array<string, scalar|null> $context
     */
    public function info(string $event, array $context = []): void
    {
        $this->log('info', $event, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function warning(string $event, array $context = []): void
    {
        $this->log('warning', $event, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    public function error(string $event, array $context = []): void
    {
        $this->log('error', $event, $context);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    private function log(string $level, string $event, array $context): void
    {
        if (!function_exists('wc_get_logger')) {
            return;
        }

        $logger = wc_get_logger();

        $sanitizedContext = [];
        foreach ($context as $key => $value) {
            $sanitizedContext[sanitize_key((string) $key)] = is_string($value)
                ? sanitize_text_field($value)
                : $value;
        }

        $payload = wp_json_encode([
            'event' => sanitize_key($event),
            'context' => $sanitizedContext,
        ]);

        $logger->log($level, (string) $payload, ['source' => self::SOURCE]);
    }
}
