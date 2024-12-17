<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Entity\Order;
use App\Message\OrderMessage;
use App\Dto\OrderResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class OrderProcessor implements ProcessorInterface
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;

    private const CONTENT_TYPE_JSON_MERGE = 'application/merge-patch+json';

    public function __construct(
        HttpClientInterface $httpClient,
        EntityManagerInterface $entityManager,
        MessageBusInterface $messageBus
    ) {
        $this->httpClient = $httpClient;
        $this->entityManager = $entityManager;
        $this->messageBus = $messageBus;
    }

    //public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Order
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse

    {
        if (!$data instanceof Order) {
            throw new \RuntimeException('Invalid data');
        }

        // Fetch product details
        $productUrl = $_ENV['PRODUCT_SERVICE_URL'] . '/' . $data->getProductId();

        try {
            $productResponse = $this->httpClient->request('GET', $productUrl);
            if ($productResponse->getStatusCode() !== 200) {
                throw new \RuntimeException('Product not found');
            }
        } catch (\Throwable $e) {
            throw new \RuntimeException('Failed to fetch product details: ' . $e->getMessage());
        }

        $product = $productResponse->toArray();

        // Validate product response
        if (!isset($product['qty'], $product['price'], $product['income'])) {
            throw new \RuntimeException('Invalid product data received from the product service');
        }

        // Check stock availability
        if ($product['qty'] < $data->getQty()) {
            throw new \RuntimeException('Insufficient product quantity');
        }

        // Calculate total amount
        $amount = $data->getQty() * $product['price'];
        $totalIncome = $product['income'] + $amount;

        // Persist the order
        $data->setAmount($amount);
        $this->entityManager->persist($data);
        $this->entityManager->flush();

        // Update product quantity in Product Service
        $this->httpClient->request(
            'PATCH',
            $productUrl,
            [
                'headers' => ['Content-Type' => self::CONTENT_TYPE_JSON_MERGE],
                'json' => ['qty' => $product['qty'] - $data->getQty()]
            ]
        );

        // Send RabbitMQ message to update product income
        $this->messageBus->dispatch(new OrderMessage($data->getProductId(), $totalIncome));

        // Transform the output using OrderResponse DTO
        $orderResponse = OrderResponse::fromEntity($data, [
            'id' => $product['id'],
            'name' => $product['name'],
            'qty' => $product['qty'] - $data->getQty(),
            'price' => $product['price'],
        ]);

        return new JsonResponse($orderResponse, 201);
    }
}
