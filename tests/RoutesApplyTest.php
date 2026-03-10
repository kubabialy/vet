<?php

declare(strict_types=1);

namespace Vet\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Slim\App;
use Vet\Vet\Routes;
use Vet\Vet\Handler\UserHandler;

class RoutesApplyTest extends TestCase
{
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
        
        $mockApp->expects($this->once())
            ->method('post')
            ->with('/users', $this->callback(function ($handler) {
                return is_callable($handler) && is_array($handler);
            }));

        Routes::initialize()->apply($mockApp);
    }

    public function testApplyUsesCorrectHttpMethodForEachRoute(): void
    {
        $mockGet = $this->createMock(App::class);
        $mockPost = $this->createMock(App::class);
        
        $mockGet->expects($this->once())->method('get');
        $mockPost->expects($this->once())->method('post');

        Routes::initialize()->apply($mockGet);
        Routes::initialize()->apply($mockPost);
    }

    public function testHandlerWithClassStringIsInstantiated(): void
    {
        $handler = [UserHandler::class, 'createUser'];

        $this->assertFalse(is_callable($handler), 'Class::method array should not be callable');

        [$class, $method] = $handler;
        $processedHandler = [new $class, $method];
        
        $this->assertTrue(is_callable($processedHandler), 'Processed handler should be callable');
    }

    public function testHandlerWithObjectInstanceIsCallable(): void
    {
        $handler = [new UserHandler(), 'createUser'];

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
            [Routes::POST_REQ, '/test', [new UserHandler(), 'nonExistentMethod']]
        ]);

        $routes->apply($mockApp);
    }
}
