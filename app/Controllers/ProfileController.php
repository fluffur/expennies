<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\DataObjects\UserProfileData;
use App\Exception\ValidationException;
use App\RequestValidators\UpdatePasswordRequestValidator;
use App\RequestValidators\UpdateProfileRequestValidator;
use App\Services\UserProfileService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ProfileController
{
    public function __construct(
        private readonly Twig $twig,
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly UserProfileService $userProfileService,
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->twig->render(
            $response,
            'profile/index.twig',
            ['profileData' => $this->userProfileService->get($request->getAttribute('user')->getId())]
        );
    }

    public function update(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $this->requestValidatorFactory->make(UpdateProfileRequestValidator::class)->validate(
            $request->getParsedBody()
        );


        $this->userProfileService->update(
            $user,
            new UserProfileData($user->getEmail(), $data['name'], (bool) ($data['twoFactor'] ?? false))
        );

        return $response;
    }

    public function changePassword(Request $request, Response $response): Response
    {
        $data = $this->requestValidatorFactory->make(UpdatePasswordRequestValidator::class)->validate(
            $request->getParsedBody(),
        );

        $currentPassword = $data['currentPassword'];
        $newPassword = $data['newPassword'];
        $user = $request->getAttribute('user');
        if (!password_verify($currentPassword, $user->getPassword())) {
            throw new ValidationException(['currentPassword' => 'Wrong current password']);
        }

        $this->userProfileService->changePassword($user, $newPassword);

        return $response;
    }
}
