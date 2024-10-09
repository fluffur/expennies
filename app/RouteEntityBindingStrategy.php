<?php

namespace App;

use App\Services\EntityManagerService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Slim\Interfaces\InvocationStrategyInterface;

class RouteEntityBindingStrategy implements InvocationStrategyInterface
{
    public function __construct(
        private readonly EntityManagerService $entityManagerService,
        private readonly ResponseFactoryInterface $responseFactory
    )
    {
    }

    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        $callableReflection = $this->createReflectionForCallable($callable);
        $resolvedArguments = [];
        foreach ($callableReflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            if (! $type) {
                continue;
            }

            $paramName = $parameter->getName();
            $typeName = $type->getName();
            if ($type->isBuiltin()) {
                if ($typeName === 'array' && $paramName === 'args') {
                    $resolvedArguments[] = $routeArguments;
                }
            } elseif ($typeName === ServerRequestInterface::class && $paramName === 'request') {
                $resolvedArguments[] = $request;
            } elseif ($typeName === ResponseInterface::class && $paramName === 'response') {
                $resolvedArguments[] = $response;
            } else {
                $entityId = $routeArguments[$paramName] ?? null;
                if (! $entityId || $parameter->allowsNull()) {
                    throw new InvalidArgumentException('Unable to resolve argument "' . $paramName . '" in the callable');
                }

                $entity = $this->entityManagerService->find($typeName, (int) $entityId);
                if (! $entity) {
                    return $this->responseFactory->createResponse(404);
                }
                $resolvedArguments[] = $entity;

            }

        }

        return $callable(...$resolvedArguments);
    }

    /**
     * @throws ReflectionException
     */
    public function createReflectionForCallable(callable $callable): ReflectionFunctionAbstract
    {
        return is_array($callable) ?
            new ReflectionMethod($callable[0], $callable[1]) :
            new ReflectionFunction($callable);
    }
}