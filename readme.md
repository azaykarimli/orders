# Order Service

The **Order Service** is a Symfony-based microservice that manages customer orders. It validates product stock, calculates order amounts, and integrates with the **Product Service** to update stock and income asynchronously using RabbitMQ.

---

## Features

- **Create and list orders**.
- **Validate stock availability** before processing orders.
- **Update product stock** in the Product Service.
- **Asynchronous income updates** using RabbitMQ.
- **Clean and robust API** for order management.

---

## Requirements

- **PHP 8.3+**
- **Symfony CLI**
- **Composer**
- **RabbitMQ** (Running locally or via Docker)
- **PostgreSQL** for the database.

---

## Setup

Follow these steps to set up the Order Service locally:

### 1. Clone the Repository



```bash

git clone https://github.com/azaykarimli/orders.git
cd orders

```

### 2. Install Dependencies


```bash

composer install

```


### 3. Configure Environment ### update it according to your db url configiration
```bash

# Database Configuration
DATABASE_URL="postgresql://orders_user:password@127.0.0.1:5432/orders_db?serverVersion=15&charset=utf8"

# RabbitMQ Configuration
MESSENGER_TRANSPORT_DSN=amqp://guest:guest@127.0.0.1:5672/%2f

# Product Service URL
PRODUCT_SERVICE_URL=http://127.0.0.1:8001/api/products


```

### 4. Set Up the Database

```bash

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate



```

### 5. Start the Symfony Server

```bash

symfony server:start


```

### 6.Endpoints


POST /orders
Description: Create a new order.

Request:


{
  "productId": "9100dd46-b1ef-479d-af0b-e226a399c8cd",
  "qty": 10
}


Response:


{
  "id": "1100dd46-b1ef-479d-af0b-e226a399c8cd",
  "product": {
    "id": "9100dd46-b1ef-479d-af0b-e226a399c8cd",
    "name": "New Brand Product",
    "qty": 90,
    "price": 99.99
  },
  "qty": 10,
  "amount": 999.9
}


GET /orders
Description: List all orders.

Response:


{
  "data": [
    {
      "id": "1100dd46-b1ef-479d-af0b-e226a399c8cd",
      "product": {
        "id": "9100dd46-b1ef-479d-af0b-e226a399c8cd",
        "name": "New Brand Product",
        "qty": 90,
        "price": 99.99
      },
      "qty": 10,
      "amount": 999.9
    }
  ]
}

### RabbitMQ Integration
RabbitMQ is used for asynchronous messaging to update the income field in the Product Service after an order is placed.

### Start RabbitMQ with Docker
If RabbitMQ is not already running, you can start it using Docker:


```bash

docker run -d --name rabbitmq -p 5672:5672 -p 15672:15672 rabbitmq:3-management
Access RabbitMQ management UI: http://localhost:15672
Username: guest
Password: guest
Start RabbitMQ Consumer
Start the Symfony Messenger to consume RabbitMQ messages:
```

```bash
php bin/console messenger:consume rabbitmq

```

### Documentation for Developers
Order Processing Logic: Located in src/State/OrderProcessor.php.
RabbitMQ Message Handling: Implemented in src/MessageHandler/OrderMessageHandler.php.
DTO for Response: Located in src/Dto/OrderResponse.php.




Project Structure

order-service/
├── src/
│   ├── Entity/            # Doctrine entity for orders
│   ├── Message/           # RabbitMQ message classes
│   ├── MessageHandler/    # Handlers for RabbitMQ messages
│   ├── State/             # Custom state processors
│   ├── Dto/               # Data Transfer Objects (DTOs)
│   └── Repository/        # Doctrine repository for orders
├── config/
│   ├── packages/          # Symfony configuration files
│   └── messenger.yaml     # RabbitMQ Messenger configuration
├── migrations/            # Database migrations
├── .env                   # Environment configuration
├── composer.json          # Composer dependencies
└── README.md              # This documentation


### Notes
Product Service is expected to run independently and expose endpoints at: http://127.0.0.1:8001/api/products.

Ensure RabbitMQ is running and configured properly for message consumption.


