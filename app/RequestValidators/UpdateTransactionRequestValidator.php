<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class UpdateTransactionRequestValidator implements RequestValidatorInterface
{

    public function validate(array $data): array
    {
        $v = new Validator($data);

        $v->rule('required', ['description', 'category', 'amount', 'date']);
        $v->rule('lengthMax', 'description', 150);
        $v->rule('integer', 'category');

        $data['date'] = new \DateTime($data['date']);


        if (! $v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}