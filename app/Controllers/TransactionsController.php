<?php

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\Entity\Transaction;
use App\RequestValidators\CreateTransactionRequestValidator;
use App\RequestValidators\UpdateTransactionRequestValidator;
use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\RequestService;
use App\Services\TransactionService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class TransactionsController
{
    public function __construct(
        private readonly Twig $twig,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly TransactionService $transactionService,
        private readonly RequestService $requestService,
        private readonly ResponseFormatter $responseFormatter,
        private readonly CategoryService $categoryService
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->twig->render(
            $response,
            'transactions/index.twig',
            ['categories' => $this->categoryService->getAll()]
        );
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(
            CreateTransactionRequestValidator::class
        )->validate(
            $request->getParsedBody()
        );
        $category = $this->categoryService->getById($data['category']);
        $this->transactionService->create(
            $request->getAttribute('user'),
            $category,
            $data['description'],
            $data['date'],
            $data['amount']
        );

        return $response->withStatus(302)->withHeader(
            'Location',
            '/transactions'
        );
    }

    public function load(Request $request, Response $response): Response
    {
        $params = $this->requestService->getDataTableQueryParameters($request);
        $transactions = $this->transactionService->getPaginatedTransactions(
            $params
        );
        $transformer = function (Transaction $transaction) {
            return [
                'id'          => $transaction->getId(),
                'description' => $transaction->getDescription(),
                'date'        => $transaction->getDate()->format('m/d/Y g:i A'),
                'category'    => $transaction->getCategory()->getName(),
                'amount'      => $transaction->getAmount(),
                'createdAt'   => $transaction->getCreatedAt()->format(
                    'm/d/Y g:i A'
                ),
                'updatedAt'   => $transaction->getCreatedAt()->format(
                    'm/d/Y g:i A'
                ),
            ];
        };

        $totalCategories = count($transactions);

        return $this->responseFormatter->asDataTable(
            $response,
            array_map($transformer, (array)$transactions->getIterator()),
            $params->draw,
            $totalCategories
        );
    }

    public function delete(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $this->transactionService->delete((int)$args['id']);

        return $response;
    }

    public function update(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $data = $this->requestValidatorFactory->make(
            UpdateTransactionRequestValidator::class
        )->validate(
            $args + $request->getParsedBody()
        );

        $transaction = $this->transactionService->getById((int)$data['id']);
        $category = $this->categoryService->getById((int)$data['category']);
        if (!$transaction) {
            return $response->withStatus(404);
        }

        $this->transactionService->update(
            $transaction,
            $request->getAttribute('user'),
            $category,
            $data['description'],
            $data['date'],
            $data['amount']
        );

        return $response;
    }

    public function get(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $transaction = $this->transactionService->getById((int)$args['id']);

        if (!$transaction) {
            return $response->withStatus(404);
        }

        $data = [
            'id'          => $transaction->getId(),
            'description' => $transaction->getDescription(),
            'category'    => $transaction->getCategory()->getId(),
            'amount'      => $transaction->getAmount(),
            'date'        => $transaction->getDate()->format('Y-m-d\TH:i'),
        ];

        return $this->responseFormatter->asJson($response, $data);
    }
}