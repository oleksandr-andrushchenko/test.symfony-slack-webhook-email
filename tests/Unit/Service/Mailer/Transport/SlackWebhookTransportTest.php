<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Mailer\Transport;

use App\Service\Mailer\Transport\Slack\SlackWebhookProviderInterface;
use App\Service\Mailer\Transport\SlackWebhookTransport;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SlackWebhookTransportTest extends TestCase
{
    private EventDispatcherInterface|MockObject $dispatcher;
    private HttpClientInterface|MockObject $client;
    private LoggerInterface $logger;
    private string $webhookBaseUrl;
    private SlackWebhookProviderInterface $slackWebhookProvider;

    private SlackWebhookTransport $slackWebhookTransport;

    public function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->client = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->webhookBaseUrl = 'https://example.com';
        $this->slackWebhookProvider = $this->createMock(SlackWebhookProviderInterface::class);

        $this->slackWebhookTransport = new SlackWebhookTransport(
            $this->dispatcher,
            $this->client,
            $this->logger,
            $this->webhookBaseUrl,
            $this->slackWebhookProvider,
        );
    }

    /**
     * @param SentMessage $message
     * @param string $webhookByAddress
     * @param string $expectedEndpoint
     * @param string $expectedMessage
     * @return void
     * @dataProvider doSendHttpSingleReceiverSuccessDataProvider
     */
    public function testDoSendHttpSingleReceiverSuccess(
        SentMessage $message,
        string $webhookByAddress,
        string $expectedEndpoint,
        string $expectedMessage
    ): void
    {
        $this->slackWebhookProvider
            ->expects($this->once())
            ->method('getSlackWebhookByAddress')
            ->willReturn($webhookByAddress)
        ;

        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('POST', $expectedEndpoint, [
                'json' => [
                    'text' => $expectedMessage,
                ],
            ])
            ->willReturn(
                $this->createConfiguredMock(ResponseInterface::class, [
                    'getStatusCode' => 200,
                    'getContent' => 'ok',
                ])
            )
        ;

        $this->doSend($message);
    }

    public function doSendHttpSingleReceiverSuccessDataProvider(): Generator
    {
        yield 'single receiver & simple case' => [
            'message' => new SentMessage(
                new RawMessage('some message'),
                new Envelope(
                    new Address('from@gmail.com'),
                    [
                        new Address('to@gmail.com'),
                    ]
                )
            ),
            'webhookByAddress' => 'webhook/for/to@gmail.com',
            'expectedEndpoint' => 'https://example.com/webhook/for/to@gmail.com',
            'expectedMessage' => 'some message',
        ];
    }

    /**
     * @param SentMessage $message
     * @param array $webhookByAddresses
     * @param array $expectedEndpoints
     * @param array $expectedMessages
     * @return void
     * @dataProvider doSendHttpMultipleReceiversSuccessDataProvider
     */
    public function testDoSendHttpMultipleReceiversSuccess(
        SentMessage $message,
        array $webhookByAddresses,
        array $expectedEndpoints,
        array $expectedMessages
    ): void
    {
        $this->slackWebhookProvider
            ->expects($this->exactly(count($webhookByAddresses)))
            ->method('getSlackWebhookByAddress')
            ->willReturnOnConsecutiveCalls(...$webhookByAddresses)
        ;

        $this->client
            ->expects($this->exactly(count($expectedEndpoints)))
            ->method('request')
            ->withConsecutive(
                ...array_map(fn ($index) => [
                    'POST', $expectedEndpoints[$index], ['json' => ['text' => $expectedMessages[$index]]],
                ], range(0, count($expectedEndpoints) - 1))
            )
            ->willReturnOnConsecutiveCalls(
                ...array_fill(0, count($expectedEndpoints), $this->createConfiguredMock(ResponseInterface::class, [
                    'getStatusCode' => 200,
                    'getContent' => 'ok',
                ]))
            )
        ;

        $this->doSend($message);
    }

    public function doSendHttpMultipleReceiversSuccessDataProvider(): Generator
    {
        yield 'multiple receivers & simple case' => [
            'message' => new SentMessage(
                new RawMessage('some message'),
                new Envelope(
                    new Address('from@gmail.com'),
                    [
                        new Address('to1@gmail.com'),
                        new Address('to2@gmail.com'),
                    ]
                )
            ),
            'webhookByAddresses' => [
                'webhook/for/to1@gmail.com',
                'webhook/for/to2@gmail.com',
            ],
            'expectedEndpoints' => [
                'https://example.com/webhook/for/to1@gmail.com',
                'https://example.com/webhook/for/to2@gmail.com',
            ],
            'expectedMessages' => [
                'some message',
                'some message',
            ],
        ];
    }

    /**
     * @param SentMessage $message
     * @param int $statusCode
     * @param string $expectedException
     * @return void
     * @dataProvider doSendHttpSingleReceiverStatusCodeFailureDataProvider
     */
    public function testDoSendHttpSingleReceiverStatusCodeFailure(
        SentMessage $message,
        int $statusCode,
        string $expectedException
    ): void
    {
        $this->slackWebhookProvider
            ->expects($this->once())
            ->method('getSlackWebhookByAddress')
            ->willReturn('any')
        ;

        $this->client
            ->expects($this->any())
            ->method('request')
            ->willReturn(
                $this->createConfiguredMock(ResponseInterface::class, [
                    'getStatusCode' => $statusCode,
                    'getContent' => 'ok',
                ])
            )
        ;

        $this->expectException($expectedException);

        $this->doSend($message);
    }

    public function doSendHttpSingleReceiverStatusCodeFailureDataProvider(): Generator
    {
        yield '201 status code' => [
            'message' => new SentMessage(
                new RawMessage('some message'),
                new Envelope(
                    new Address('from@gmail.com'),
                    [
                        new Address('to@gmail.com'),
                    ]
                )
            ),
            'statusCode' => 201,
            'expectedException' => HttpTransportException::class,
        ];
    }

    private function doSend(SentMessage $message): void
    {
        $class = new ReflectionClass($this->slackWebhookTransport);
        $method = $class->getMethod('doSendHttp');
        $method->setAccessible(true);
        $method->invokeArgs($this->slackWebhookTransport, [$message]);
    }
}