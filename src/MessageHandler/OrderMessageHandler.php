<?php

namespace App\MessageHandler;

use App\Message\OrderMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class OrderMessageHandler
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function __invoke(OrderMessage $message)
    {
        $productId = $message->getProductId();
        $amount = $message->getAmount();

        // Validate input
        if (empty($productId) || $amount <= 0) {
            throw new \InvalidArgumentException('Invalid product ID or amount.');
        }

        try {
            $response = $this->httpClient->request(
                'PATCH',
                $_ENV['PRODUCT_SERVICE_URL'] . '/' . $productId,
                [
                    'headers' => ['Content-Type' => 'application/merge-patch+json'],
                    'json' => ['income' => $amount],
                ]
            );

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException("Failed to update product income.");
            }
        } catch (\Throwable $e) {
            $this->logger->error("Error updating product income", [
                'productId' => $productId,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Allow message retries
        }
    }
}
