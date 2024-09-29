<?php

namespace App\RequestValidators;

use App\Config;
use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use finfo;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;
use Valitron\Validator;

class UploadReceiptRequestValidator implements RequestValidatorInterface
{

    public function validate(array $data): array
    {
        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $data['receipt'] ?? null;

        // 1. Validate uploaded file
        if (!$uploadedFile) {
            throw new ValidationException(['receipt' => ['Please select a file']]);
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['receipt' => ['Failed to upload the receipt file']]);
        }
        // 2. Validate file size
        $fileSize = $uploadedFile->getSize();

        $maxUploadSize = 5 * 1024 * 1024;
        if ($fileSize > $maxUploadSize) {
            throw new ValidationException(['receipt' => ['File size is too large']]);
        }

        // 3. Validate the file name
        $fileName = $uploadedFile->getClientFilename();

        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $fileName)) {
            throw new ValidationException(['receipt' => ['Invalid file name']]);
        }

        // 4. Validate file type
        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];

        if (!in_array($uploadedFile->getClientMediaType(), $allowedMimeTypes)) {
            throw new ValidationException(['receipt' => ['Receipt has to be either image or a pdf document']]);
        }

        $tmpFilePath = $uploadedFile->getStream()->getMetadata('uri');

        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeType($tmpFilePath, $uploadedFile->getStream()->getContents());

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new ValidationException(['receipt' => ['Invalid file type']]);
        }

        return $data;
    }
}