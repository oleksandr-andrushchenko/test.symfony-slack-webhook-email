<?php

declare(strict_types=1);

namespace App\Service\Mailer\Transport;

use App\Service\Mailer\Transport\Slack\SlackWebhookProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractHttpTransport;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class SlackWebhookTransport extends AbstractHttpTransport
{
    public function __construct(
        ?EventDispatcherInterface $dispatcher,
        ?HttpClientInterface $client,
        ?LoggerInterface $logger,
        private readonly string $webhookBaseUrl,
        private readonly SlackWebhookProviderInterface $slackWebhookProvider,
    )
    {
        parent::__construct($client, $dispatcher, $logger);
    }

    protected function doSendHttp(SentMessage $message): ResponseInterface
    {
        foreach ($message->getEnvelope()->getRecipients() as $recipient) {
            $response = null;
            $exception = null;
            $statusCode = null;
            $content = null;

            try {
                $webhook = $this->slackWebhookProvider->getSlackWebhookByAddress($recipient);
                $endpoint = sprintf('%s/%s', $this->webhookBaseUrl, $webhook);

                $response = $this->client->request('POST', $endpoint, [
                    'json' => [
                        'text' => $message->toString(),
                    ],
                ]);

                $statusCode = $response->getStatusCode();

                if ($statusCode !== 200) {
                    throw new HttpTransportException(sprintf('Failed to send an email ("%d" code)', $statusCode), $response);
                }

                $content = $response->getContent();

                if ($content !== 'ok') {
                    throw new HttpTransportException(sprintf('Failed to send an email ("%s" content)', $content), $response);
                }
            } catch (ExceptionInterface $exception) {
                if (isset($response)) {
                    $previousException = new HttpTransportException($exception->getMessage(), $response, previous: $previousException ?? null);
                } else {
                    $previousException = new TransportException($exception->getMessage(), previous: $previousException ?? null);
                }
            }
        }

        if (isset($previousException)) {
            throw $previousException;
        }

        return $response;
    }

    public function __toString(): string
    {
        return sprintf('slack+webhook://%s', $this->getHost());
    }

    private function getHost(): string
    {
        return parse_url($this->webhookBaseUrl, PHP_URL_HOST);
    }
}