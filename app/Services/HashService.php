<?php

namespace App\Services;

class HashService
{

    public function hashPassword(#[\SensitiveParameter] $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}