<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\DataObjects\DataTableQueryParams;
use App\DataObjects\TransactionData;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TransactionService
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManager)
    {
    }

    public function create(TransactionData $transactionData, User $user): Transaction
    {
        $transaction = new Transaction();

        $transaction->setUser($user);

        return $this->update($transaction, $transactionData);
    }

    public function getPaginatedTransactions(DataTableQueryParams $params): Paginator
    {
        $query = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('t', 'c', 'r')
            ->leftJoin('t.category', 'c')
            ->leftJoin('t.receipts', 'r')
            ->setFirstResult($params->start)
            ->setMaxResults($params->length);

        $orderBy  = in_array($params->orderBy, ['description', 'amount', 'date', 'category'])
            ? $params->orderBy
            : 'date';
        $orderDir = strtolower($params->orderDir) === 'asc' ? 'asc' : 'desc';

        if (! empty($params->searchTerm)) {
            $query->where('t.description LIKE :description')
                  ->setParameter('description', '%' . addcslashes($params->searchTerm, '%_') . '%');
        }

        if ($orderBy === 'category') {
            $query->orderBy('c.name', $orderDir);
        } else {
            $query->orderBy('t.' . $orderBy, $orderDir);
        }

        return new Paginator($query);
    }

    public function getById(int $id): ?Transaction
    {
        return $this->entityManager->find(Transaction::class, $id);
    }

    public function update(Transaction $transaction, TransactionData $transactionData): Transaction
    {
        $transaction->setDescription($transactionData->description);
        $transaction->setAmount($transactionData->amount);
        $transaction->setDate($transactionData->date);
        $transaction->setCategory($transactionData->category);

        return $transaction;
    }

    public function toggleReviewed(Transaction $transaction): void
    {
        $transaction->setReviewed(! $transaction->wasReviewed());
    }

    public function getTotals(\DateTime $startDate, \DateTime $endDate): array
    {
        $amounts = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) AS income')
            ->addSelect('SUM(CASE WHEN t.amount < 0 THEN -t.amount ELSE 0 END) AS expense')
            ->where('t.date >= :startDate')
            ->andWhere('t.date <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleResult();

        $income = abs((float)$amounts['income']);
        $expense = abs((float)$amounts['expense']);
        $net = $income - $expense;

        return ['net' => $net, 'income' => $income, 'expense' => $expense];
    }

    public function getRecentTransactions(int $limit): array
    {
        return $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('t', 'c')
            ->leftJoin('t.category', 'c')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getMonthlySummary(int $year): array
    {
        return $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->select('MONTH(t.createdAt) AS m')
            ->addSelect('SUM(CASE WHEN t.amount > 0 THEN t.amount ELSE 0 END) AS income')
            ->addSelect('SUM(CASE WHEN t.amount < 0 THEN t.amount ELSE 0 END) AS expense')
            ->where('YEAR(t.createdAt) = :year')
            ->setParameter('year', $year)
            ->groupBy('m')
            ->getQuery()
            ->getResult();
    }
}
