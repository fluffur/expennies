<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\TransactionService;
use Clockwork\Request\Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

class HomeController
{
    public function __construct(
        private readonly Twig $twig,
        private readonly TransactionService $transactionService,
        private readonly CategoryService $categoryService,
        private readonly ResponseFormatter $responseFormatter
    ) {
    }

    public function index(ServerRequestInterface $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();

        $startDate             = \DateTime::createFromFormat('Y-m-d', $queryParams['startDate'] ?? date('Y-m-01'));
        $endDate               = new \DateTime($queryParams['endDate'] ?? 'now');
        $totals                = $this->transactionService->getTotals($startDate, $endDate);
        $recentTransactions    = $this->transactionService->getRecentTransactions(10);
        $topSpendingCategories = $this->categoryService->getTopSpendingCategories(4);

        return $this->twig->render(
            $response,
            'dashboard.twig',
            [
                'totals'                => $totals,
                'transactions'          => $recentTransactions,
                'topSpendingCategories' => $topSpendingCategories,
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
            ]
        );
    }

    public function getYearToDateStatistics(ServerRequestInterface $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $year = (int)($queryParams['year'] ?? date('Y'));
        $data = $this->transactionService->getMonthlySummary($year);

        return $this->responseFormatter->asJson($response, $data);
    }
}
