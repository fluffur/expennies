<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class UploadTransactionsCsvRequestValidator  implements RequestValidatorInterface
{

    public function validate(array $data): array
    {
        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $data['transaction'] ?? null;

        // 1. Validate uploaded file
        if (! $uploadedFile) {
            throw new ValidationException(['transaction' => ['Please select a transaction csv file']]);
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['transaction' => ['Failed to upload the transaction file']]);
        }

        // 2. Validate the file size
        $maxFileSize = 5 * 1024 * 1024;

        if ($uploadedFile->getSize() > $maxFileSize) {
            throw new ValidationException(['transaction' => ['Maximum allowed size is 5 MB']]);
        }

        // 3. Validate the file name
        $filename = $uploadedFile->getClientFilename();

        if (! preg_match('/^[a-zA-Z0-9\s._-]+$/', $filename)) {
            throw new ValidationException(['transaction' => ['Invalid filename']]);
        }

        // 4. Validate file type
        $allowedMimeTypes = ['text/csv', 'application/vnd.ms-excel'];
        $tmpFilePath      = $uploadedFile->getStream()->getMetadata('uri');

        if (! in_array($uploadedFile->getClientMediaType(), $allowedMimeTypes)) {
            throw new ValidationException(['transaction' => ['File has to be csv but was ' . $uploadedFile->getClientMediaType()]]);
        }

        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeTypeFromFile($tmpFilePath);

        if (! in_array($mimeType, $allowedMimeTypes)) {
            throw new ValidationException(['transaction' => ['Invalid file type']]);
        }

        return $data;
    }
}