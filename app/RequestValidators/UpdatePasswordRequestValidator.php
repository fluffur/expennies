<?php

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use Valitron\Validator;

class UpdatePasswordRequestValidator implements RequestValidatorInterface
{

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['currentPassword', 'newPassword']);
        $v->rule('different', 'currentPassword', 'newPassword');
        $v->rule('lengthMin', 'newPassword', 5);
        if (!$v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}