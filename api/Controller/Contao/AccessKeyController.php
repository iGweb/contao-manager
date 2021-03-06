<?php

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller\Contao;

use Contao\ManagerApi\Process\ContaoApi;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessKeyController extends Controller
{
    /**
     * @var ContaoApi
     */
    private $api;

    /**
     * Constructor.
     *
     * @param ContaoApi $api
     */
    public function __construct(ContaoApi $api)
    {
        $this->api = $api;
    }

    /**
     * Handles the controller action.
     *
     * @param Request $request
     *
     * @return Response
     * @throws \Seld\JsonLint\ParsingException
     */
    public function __invoke(Request $request)
    {
        switch ($request->getMethod()) {
            case 'GET':
                return $this->getAccessKey();

            case 'PUT':
                return $this->setAccessKey($request);

            case 'DELETE':
                return $this->removeAccessKey();
        }

        return new Response(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @return Response
     * @throws \Seld\JsonLint\ParsingException
     */
    private function getAccessKey()
    {
        if (!$this->isSupported('get')) {
            return new Response(null, Response::HTTP_NOT_IMPLEMENTED);
        }

        return new JsonResponse(['access-key' => $this->api->runCommand($this->getAccessKeyArguments('get'))]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     * @throws \Seld\JsonLint\ParsingException
     */
    private function setAccessKey(Request $request)
    {
        if (!$this->isSupported('set')) {
            return new Response(null, Response::HTTP_NOT_IMPLEMENTED);
        }

        if (!$request->request->has('user') || !$request->request->has('password')) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }

        $user = $request->request->get('user');
        $password = $request->request->get('password');

        $accessKey = password_hash(
            $user.':'.$password,
            PASSWORD_DEFAULT
        );

        $this->api->runCommand(array_merge($this->getAccessKeyArguments('set'), [$accessKey]));

        return new JsonResponse(['access-key' => $accessKey]);
    }

    /**
     * @return Response
     * @throws \Seld\JsonLint\ParsingException
     */
    private function removeAccessKey()
    {
        if (!$this->isSupported('remove')) {
            return new Response(null, Response::HTTP_NOT_IMPLEMENTED);
        }

        $this->api->runCommand($this->getAccessKeyArguments('remove'));

        return new JsonResponse(['access-key' => '']);
    }

    /**
     * @param string $action
     *
     * @return array
     */
    private function getAccessKeyArguments($action)
    {
        if ($this->api->getVersion() === 1) {
            return ['access-key:'.$action];
        }

        return ['dot-env:'.$action, 'APP_DEV_ACCESSKEY'];
    }

    /**
     * Returns whether access key command is supported.
     *
     * @param string $action
     *
     * @return bool
     */
    private function isSupported($action)
    {
        return $this->api->getVersion() === 1
            || ($this->api->hasCommand('dot-env:'.$action)
                && in_array('APP_DEV_ACCESSKEY', $this->api->getFeatures()['contao/manager-bundle']['dot-env'], true)
            );
    }
}
