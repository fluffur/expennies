<?php

namespace App\Services;

use App\Contracts\UserInterface;
use App\DataObjects\DataTableQueryParams;
use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TransactionService
{

    public function __construct(private readonly EntityManager $entityManager)
    {
    }

    public function create(User $user, Category $category, string $description, \DateTime $date, float $amount): Transaction
    {
        $transaction = new Transaction();
        $this->entityManager->persist($transaction);
        $this->update($transaction, $user, $category, $description, $date, $amount);

        return $transaction;
    }

    public function load()
    {

    }

    public function getPaginatedTransactions(DataTableQueryParams $params): Paginator
    {
        $query = $this->entityManager
            ->getRepository(Transaction::class)
            ->createQueryBuilder('t')
            ->setFirstResult($params->start)
            ->setMaxResults($params->length);

        $orderBy  = in_array($params->orderBy, ['description', 'date', 'amount', 'createdAt', 'updatedAt']) ? $params->orderBy : 'updatedAt';
        $orderDir = strtolower($params->orderDir) === 'asc' ? 'asc' : 'desc';

        if (! empty($params->searchTerm)) {
            $query->where('t.name LIKE :description')->setParameter('description', '%' . addcslashes($params->searchTerm, '%_') . '%');
        }

        $query->orderBy('t.' . $orderBy, $orderDir);

        return new Paginator($query);
    }

    public function delete(int $id)
    {
        $transaction = $this->entityManager->find(Transaction::class, $id);

        $this->entityManager->remove($transaction);
        $this->entityManager->flush();
    }

    public function getById(int $id)
    {
        return $this->entityManager->find(Transaction::class, $id);
    }

    public function update(Transaction $transaction, User $user, Category $category, string $description, \DateTime $date, float $amount): void
    {
        $transaction->setDescription($description);
        $transaction->setCategory($category);
        $transaction->setDate($date);
        $transaction->setAmount($amount);
        $transaction->setUser($user);

        $this->entityManager->flush();
    }
}