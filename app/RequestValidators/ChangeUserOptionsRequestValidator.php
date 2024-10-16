<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class ChangeUserOptionsRequestValidator implements RequestValidatorInterface
{

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('optional', ['2fa']);
        $v->rule('accepted', ['2fa']);

        if (!$v->validate()) {
            throw new ValidationException($v->errors());
        }

        if (!isset($data['2fa'])) {
            $data['2fa'] = false;
        } else {
            $data['2fa'] = true;
        }

        return $data;
    }
}