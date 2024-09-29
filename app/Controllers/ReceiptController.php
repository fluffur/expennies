<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\RequestValidators\UploadReceiptRequestValidator;
use App\Services\ReceiptService;
use App\Services\TransactionService;
use League\Flysystem\Filesystem;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UploadedFileInterface;

class ReceiptController
{


    public function __construct(
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly TransactionService               $transactionService,
        private readonly ReceiptService                   $receiptService
    )
    {
    }

    public function store(Request $request, Response $response, array $args): Response
    {
        $id = (int)$args['id'];

        if (!$id || !($transaction = $this->transactionService->getById($id))) {
            return $response->withStatus(404);
        }


        /** @var UploadedFileInterface $file */
        $file = $this->requestValidatorFactory->make(UploadReceiptRequestValidator::class)->validate(
            $request->getUploadedFiles()
        )['receipt'];

        $filename = $file->getClientFilename();

        $storageFilename = $this->receiptService->upload($file);
        $this->receiptService->create($transaction, $filename, $storageFilename);

        return $response;
    }
}
