<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Controller;

use InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle;
use InSquare\OpendxpProcessManagerBundle\Enums;
use InSquare\OpendxpProcessManagerBundle\Executor\Action\AbstractAction;
use InSquare\OpendxpProcessManagerBundle\Model\Configuration;
use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;
use InSquare\OpendxpProcessManagerBundle\Service\CommandsValidator;
use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use OpenDxp\Controller\UserAwareController;
use OpenDxp\Model\User;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Translation\Translator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/admin/insquare-opendxp-process-manager/index')]
class IndexController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route(path: '/get-plugin-config')]
    public function getPluginConfigAction(CommandsValidator $commandsValidator, Translator $translator): JsonResponse
    {
        try {
            $this->checkPermission(Enums\Permissions::VIEW);
        } catch (AccessDeniedHttpException $e) {
            return $this->jsonResponse([]);
        }

        $bundleConfig = InSquareOpendxpProcessManagerBundle::getConfiguration();
        $data = $bundleConfig->getClassTypes();

        $commands = (array)$commandsValidator->getValidCommands();
        foreach ($commands as $key => $command) {
            $tmp = ['description' => $command->getDescription(), 'options' => $command->getDefinition()->getOptions()];
            $commands[$key] = $tmp;
        }

        $data['pimcoreCommands'] = $commands;

        $data['roles'] = [];

        $list = new \OpenDxp\Model\User\Role\Listing();
        $list->setOrder('ASC')->setOrderKey('name');
        foreach ($list->load() as $role) {
            $data['roles'][] = [
                'id' => $role->getId(),
                'name' => $role->getName(),
            ];
        }

        $data['permissions'] = [];
        $list = new \OpenDxp\Model\User\Permission\Definition\Listing();
        $list->setOrder('ASC')->setOrderKey('key');
        foreach ($list->load() as $permission) {
            $data['permissions'][] = [
                'key' => $permission->getKey(),
                'name' => $translator->trans($permission->getKey(), [], 'admin'). ' (' . $permission->getKey().')',
                'category' => $permission->getCategory(),
            ];
        }

        usort($data['permissions'], fn ($a, $b): int => strnatcasecmp($a['name'], $b['name']));

        $shortCutMenu = [];

        if ($bundleConfig->getDisableShortcutMenu() == false) {
            $list = new Configuration\Listing();
            $list->setUser($this->getCurrentUser());
            $list->setOrderKey('name');
            foreach ($list->load() as $config) {
                $group = $config->getGroup() ?: 'default';
                $shortCutMenu[$group][] = [
                    'id' => $config->getId(),
                    'name' => $config->getName(),
                    'group' => $config->getGroup(),
                ];
            }
            $data['shortCutMenu'] = $shortCutMenu ?: false;
        }

        $data['refreshIntervalSeconds'] = $bundleConfig->getRefreshIntervalSeconds();

        if ($data['shortCutMenu'] ?? null) {
            ksort($data['shortCutMenu'], SORT_LOCALE_STRING);
        }

        return  $this->jsonResponse($data);
    }

    /**
     * @throws \JsonException
     */
    #[Route(path: '/download')]
    public function downloadAction(Request $request): ?Response
    {
        $monitoringItem = MonitoringItem::getById($request->get('id'));
        if (!$monitoringItem) {
            throw $this->createNotFoundException('MonitoringItem Not Found');
        }
        $actions = $monitoringItem->getActions();
        foreach ($actions as $action) {
            if ($action['accessKey'] == $request->get('accessKey')) {
                $className = $action['class'];
                /**
                 * @var AbstractAction $class
                 */
                $class = new $className();

                return $class->execute($monitoringItem, $action);
            }
        }

        return null;
    }

    #[Route(path: '/property-list')]
    public function propertyListAction(Request $request): JsonResponse
    {
        $result = [];
        $fieldName = $request->get('fieldName');

        if ($fieldName == 'myProperties') {
            $result = [];
            for ($i = 1; $i < 50; $i++) {
                $result[] = ['id' => $i, 'name' => 'Display text - '.$fieldName.' - '.$i];
            }
        }

        return $this->jsonResponse(['success' => true, 'data' => $result]);
    }

    /**
     *
     * @return JsonResponse
     *
     */
    #[Route(path: '/get-classes')]
    public function getClassesAction(): JsonResponse
    {
        $result = [];
        $list = new ClassDefinition\Listing();
        $list->setOrderKey('name')->setOrder('ASC');
        foreach ($list as $c) {
            $result[] = ['id' => $c->getId(), 'name' => $c->getName()];
        }

        return new JsonResponse(['data' => $result]);
    }

    /**
     *
     * @return JsonResponse
     *
     */
    #[Route(path: '/get-grid-configs')]
    public function getGridConfigsAction(): JsonResponse
    {
        $result = [];
        $list = new GridConfig\Listing();
        $list->setOrderKey('name');
        $list->setCondition('ownerId = ? OR shareGlobally =1', [$this->getCurrentUser()->getId()]);
        $config = $list->load();
        foreach ($list as $c) {
            $result[] = ['id' => $c->getId(), 'name' => $c->getName()];
        }

        return new JsonResponse(['data' => $result]);
    }

    private function getCurrentUser(): User
    {
        $user = $this->getOpenDxpUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedHttpException();
        }

        return $user;
    }

}
