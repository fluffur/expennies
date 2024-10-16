<?php

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\RequestValidatorFactoryInterface;
use App\Contracts\UserProviderServiceInterface;
use App\Entity\User;
use App\RequestValidators\ChangeUserOptionsRequestValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class UserOptionsController
{

    public function __construct(
        private readonly Twig $twig,
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->twig->render($response,
            'user_options.twig',
            ['user' => $request->getAttribute('user')]);
    }

    public function update(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(ChangeUserOptionsRequestValidator::class)->validate(
            $request->getParsedBody(),
        );
        /** @var User $user */
        $user = $request->getAttribute('user');
        $user->setHasTwoFactorAuthEnabled($data['2fa']);
        $this->entityManagerService->sync($user);

        return $response->withHeader('Location', '/options')->withStatus(302);
    }
}