<?php

declare(strict_types=1);

namespace App\Service\Mailer\Transport\Slack;

use Symfony\Component\Mime\Address;

readonly class StaticSlackWebhookProvider implements SlackWebhookProviderInterface
{
    public function __construct(
        private string $fallbackWebhook,
        private array $webhooks
    )
    {
    }

    public function getSlackWebhookByAddress(Address $address): string
    {
        return $this->webhooks[$address->getAddress()] ?? $this->fallbackWebhook;
    }
}