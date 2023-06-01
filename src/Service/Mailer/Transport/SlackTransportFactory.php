<?php

declare(strict_types=1);

namespace App\Service\Mailer\Transport;

use App\Service\Mailer\Transport\Slack\SlackWebhookProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SlackTransportFactory extends AbstractTransportFactory
{
    public function __construct(
        ?EventDispatcherInterface $dispatcher,
        ?HttpClientInterface $client,
        ?LoggerInterface $logger,
        private readonly string $webhookBaseUrl,
        private readonly SlackWebhookProviderInterface $slackWebhookProvider,
    )
    {
        parent::__construct($dispatcher, $client, $logger);
    }

    public function getSupportedSchemes(): array
    {
        return [
            'slack+webhook',
        ];
    }

    public function create(Dsn $dsn): TransportInterface
    {
        return match ($dsn->getScheme()) {
            'slack+webhook' => new SlackWebhookTransport(
                $this->dispatcher,
                $this->client,
                $this->logger,
                $this->webhookBaseUrl,
                $this->slackWebhookProvider,
            ),
            default => throw new UnsupportedSchemeException($dsn, 'slack', $this->getSupportedSchemes()),
        };
    }
}