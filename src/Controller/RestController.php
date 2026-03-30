<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Controller;

use InSquare\OpendxpProcessManagerBundle\Enums;
use InSquare\OpendxpProcessManagerBundle\Helper;
use InSquare\OpendxpProcessManagerBundle\Model\Configuration;
use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;
use OpenDxp\Controller\FrontendController;
use OpenDxp\Model\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/webservice/insquare-opendxp-process-manager/rest')]
class RestController extends FrontendController
{
    protected function getApiUser(Request $request): JsonResponse|User
    {
        $user = User::getByName($request->get('username'));
        if (!$user instanceof User) {
            return $this->json(['success' => false, 'message' => 'User not found']);
        }

        $config = \InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle::getConfiguration();
        $validApiUser = false;

        foreach ($config->getRestApiUsers() as $entry) {
            if ($entry['username'] == $user->getName()) {
                if ($request->get('apiKey') == $entry['apiKey']) {
                    $validApiUser = true;
                } else {
                    return $this->json(['success' => false, 'message' => 'No valid api key for user']);
                }
            }
        }
        if (!$validApiUser) {
            return $this->json(['success' => false, 'message' => 'The user is not a valid api user']);
        }
        if (!$user->getPermission(Enums\Permissions::EXECUTE) || !$user->getPermission(Enums\Permissions::VIEW)) {
            return $this->json(['success' => false, 'message' => 'Missing permissions for user']);
        }

        return $user;
    }

    #[Route(path: '/execute')]
    public function executeAction(Request $request): JsonResponse
    {
        $user = $this->getApiUser($request);
        if (!$user instanceof User) {
            return $user;
        }

        if (!$request->get('id') && !$request->get('name')) {
            return $this->json(['success' => false, 'message' => 'Please provide a "name" or "id" parameter/value.']);
        }

        $list = new Configuration\Listing();
        $list->setUser($user);
        if ($id = $request->get('id')) {
            $list->setCondition('id = ?', [$id]);
        } elseif ($name = $request->get('name')) {
            $list->setCondition('name = ?', [$name]);
        }
        $config = $list->current();
        if (!$config) {
            return $this->json(['success' => false, 'message' => "Couldn't find a process to execute."]);
        }

        $callbackSettings = [];

        if ($val = $request->get('callbackSettings')) {
            $callbackSettings = json_decode((string) $val, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($callbackSettings)) {
                $xml = @simplexml_load_string((string) $val);
                if ($xml !== false) {
                    $callbackSettings = json_decode(json_encode($xml, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
                }
            }

            if (!$callbackSettings) {
                return $this->json(['success' => false, 'message' => "Couldn't decode the callbackSettigs. Please make sure that you passed a valid JSON or XML."]);
            }
        }

        $result = Helper::executeJob($config->getId(), $callbackSettings, $user->getId());
        unset($result['executedCommand']);

        return $this->json($result);
    }

    #[Route(path: '/monitoring-item-state')]
    public function monitoringItemStateAction(Request $request): JsonResponse
    {
        $user = $this->getApiUser($request);
        if ($user instanceof User == false) {
            return $user;
        }

        $list = new MonitoringItem\Listing();
        $list->setUser($user);

        $list->setCondition(' id = ?', [$request->get('id')]);

        $monitoringItem = $list->current();
        if (!$monitoringItem) {
            return $this->json(['success' => false, 'message' => 'The monitoring Item was not found.']);
        }
        $monitoringItem->getLogger()->notice('Checked by rest webservice User ID: ' . $user->getId());

        return $this->json(['success' => true, 'data' => $monitoringItem->getForWebserviceExport()]);
    }
}
