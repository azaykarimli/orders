<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Order;
use App\Dto\OrderResponse;

class OrderProvider implements ProviderInterface
{
    private EntityManagerInterface $entityManager;
    private HttpClientInterface $httpClient;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $httpClient)
    {
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        // Fetch all orders
        $orders = $this->entityManager->getRepository(Order::class)->findAll();

        $responses = [];

        foreach ($orders as $order) {
            $productId = $order->getProductId();

            // Fetch product details from the Product Service
            try {
                $response = $this->httpClient->request('GET', $_ENV['PRODUCT_SERVICE_URL'] . '/' . $productId);
                if ($response->getStatusCode() === 200) {
                    $product = $response->toArray();
                } else {
                    $product = [
                        'id' => $productId,
                        'name' => 'Unknown Product',
                        'qty' => 0,
                        'price' => 0,
                    ];
                }
            } catch (\Throwable $e) {
                $product = [
                    'id' => $productId,
                    'name' => 'Error fetching product',
                    'qty' => 0,
                    'price' => 0,
                ];
            }

            // Map the order and product details to the OrderResponse DTO
            $responses[] = OrderResponse::fromEntityWithProduct($order, $product);
        }

        return $responses;
    }
}
