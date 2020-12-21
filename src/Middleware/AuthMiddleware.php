<?php

namespace Ycbl\AdminAuth\Middleware;

use FastRoute\Dispatcher;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\HttpServer\Router\Dispatched;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ycbl\AdminAuth\Annotation\Auth as AuthAnnotation;
use Ycbl\AdminAuth\Auth;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    const NEED_LOGIN = 1001;

    const NEED_RIGHT = 1002;

    /**
     * @Inject
     * @var Auth
     */
    protected $auth;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        [$no_need_login, $no_need_right] = $this->checkWhiteList($request);
        // 无需登录直接执行
        if ($no_need_login){
            return $handler->handle($request);
        }
        //未登录返回错误信息
        if (!$this->auth->isLogin()){
            return $this->errorResult(self::NEED_LOGIN);
        }
        //无需权限认证直接执行
        if ($no_need_right){
            return $handler->handle($request);
        }

        $uri = $this->request->path();
        if (!$this->auth->check($uri)){
            return $this->errorResult(self::NEED_RIGHT);
        }
        return $handler->handle($request);
    }

    public function errorResult($error_code)
    {
        if ($error_code == self::NEED_LOGIN) {
            return $this->response->json(['code' => $error_code, 'msg' => '请先登录']);
        } else {
            return $this->response->json(['code' => $error_code, 'msg' => '您没有权限']);
        }
    }

    public function checkWhiteList(ServerRequestInterface $request)
    {
        $dispatched = $request->getAttribute(Dispatched::class);
        if ($dispatched->status !== Dispatcher::FOUND) {
            return true;
        }
        list($class, $method) = $dispatched->handler->callback;
        $annotations = AnnotationCollector::getClassMethodAnnotation($class, $method);
        if (isset($annotations[AuthAnnotation::class])) {
            $white_list = $annotations[AuthAnnotation::class];
            $no_need_login = $white_list->noNeedLogin;
            $no_need_right = $white_list->noNeedRight;
        } else {
            $no_need_login = false;
            $no_need_right = false;
        }
        return [$no_need_login, $no_need_right];
    }

}