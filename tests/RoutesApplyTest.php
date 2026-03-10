<?php

declare(strict_types=1);

namespace Vet\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Slim\App;
use Vet\Vet\Routes;
use Vet\Vet\Handler\UserHandler;
use Vet\Vet\Auth\Auth;

class RoutesApplyTest extends TestCase
{
    private function createUserHandler(): UserHandler
    {
        $secret = base64_encode(random_bytes(32));
        return new UserHandler(new Auth($secret));
    }

    public function testApplyCallsGetForGetRoutes(): void
    {
        $mockApp = $this->createMock(App::class);
        
        $mockApp->expects($this->once())
            ->method('get')
            ->with('/hello/{name}', $this->callback(function ($handler) {
                return is_callable($handler);
            }));

        Routes::initialize()->apply($mockApp);
    }

    public function testApplyCallsPostForPostRoutes(): void
    {
        $mockApp = $this->createMock(App::class);
        
        $mockApp->expects($this->exactly(2))
            ->method('post')
            ->willReturnCallback(function ($path, $handler) {
                $this->assertTrue(in_array($path, ['/users/signup', '/users/signin']), "Unexpected path: $path");
                $this->assertTrue(is_callable($handler) && is_array($handler));
                return $this->createMock(\Slim\Interfaces\RouteInterface::class);
            });

        Routes::initialize()->apply($mockApp);
    }

    public function testApplyUsesCorrectHttpMethodForEachRoute(): void
    {
        $mockGet = $this->createMock(App::class);
        $mockPost = $this->createMock(App::class);
        
        $mockGet->expects($this->once())->method('get');
        $mockPost->expects($this->exactly(2))->method('post');

        Routes::initialize()->apply($mockGet);
        Routes::initialize()->apply($mockPost);
    }

    public function testHandlerWithClassStringIsInstantiated(): void
    {
        $handler = [UserHandler::class, 'signUp'];

        $this->assertFalse(is_callable($handler), 'Class::method array should not be callable');

        [$class, $method] = $handler;
        $processedHandler = [new $class(new Auth('test-secret')), $method];
        
        $this->assertTrue(is_callable($processedHandler), 'Processed handler should be callable');
    }

    public function testHandlerWithObjectInstanceIsCallable(): void
    {
        $handler = [$this->createUserHandler(), 'signUp'];

        $this->assertTrue(is_callable($handler), 'Object::method should be callable');
    }

    public function testHandlerWithCallbackIsCallable(): void
    {
        $handler = function ($request, $response, $args) {
            return $response;
        };

        $this->assertTrue(is_callable($handler), 'Callback should be callable');
    }

    public function testCustomRoutesCanBeProvided(): void
    {
        $mockApp = $this->createMock(App::class);
        
        $mockApp->expects($this->once())
            ->method('put')
            ->with('/custom', $this->anything());

        $routes = new Routes([
            [Routes::PUT_REQ, '/custom', function () {}]
        ]);

        $routes->apply($mockApp);
    }

    public function testApplyThrowsExceptionForInvalidRoute(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid route');

        $mockApp = $this->createMock(App::class);

        $routes = new Routes([
            ['get', '/incomplete']
        ]);

        $routes->apply($mockApp);
    }

    public function testApplyThrowsExceptionForInvalidClass(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid class');

        $mockApp = $this->createMock(App::class);

        $routes = new Routes([
            [Routes::POST_REQ, '/test', ['NonExistentClass', 'method']]
        ]);

        $routes->apply($mockApp);
    }

    public function testApplyThrowsExceptionForInvalidMethod(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid method');

        $mockApp = $this->createMock(App::class);

        $routes = new Routes([
            [Routes::POST_REQ, '/test', [UserHandler::class, 'nonExistentMethod']]
        ]);

        $routes->apply($mockApp);
    }

    public function testApplyThrowsExceptionForInvalidMethodOnObject(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid method');

        $mockApp = $this->createMock(App::class);

        $routes = new Routes([
            [Routes::POST_REQ, '/test', [$this->createUserHandler(), 'nonExistentMethod']]
        ]);

        $routes->apply($mockApp);
    }
}
