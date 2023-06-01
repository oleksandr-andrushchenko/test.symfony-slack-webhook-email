<?php

declare(strict_types=1);

namespace App\Service\Mailer\Transport\Slack;

use Symfony\Component\Mime\Address;

interface SlackWebhookProviderInterface
{
    public function getSlackWebhookByAddress(Address $address): string;
}