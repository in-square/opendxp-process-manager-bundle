<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Model\Configuration;

use InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle;
use InSquare\OpendxpProcessManagerBundle\Model\Configuration;
use InSquare\OpendxpProcessManagerBundle\Model\Dao\AbstractDao;
use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;

/**
 * @property Configuration $model
 */
class Dao extends AbstractDao
{
    protected function getTableName(): string
    {
        return InSquareOpendxpProcessManagerBundle::TABLE_NAME_CONFIGURATION;
    }

    public function delete(): void
    {
        $id = $this->model->getId();

        if (!is_null($id)) {
            $items = (new MonitoringItem\Listing())
                ->setCondition('configurationId = ?', [$id])
                ->load();

            foreach ($items as $item) {
                $item->delete();
            }

            $this->db
                ->prepare('DELETE FROM ' . $this->getTableName() . ' WHERE `id` = ?')
                ->executeQuery([$id]);
        }
    }

    /**
     * @param array<mixed> $params
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \JsonException
     */
    public function save(array $params = []): ?Configuration
    {
        $data = $this->getValidStorageValues();

        if ($data['keepVersions'] === '') {
            $data['keepVersions'] = null;
        }
        if (!$data['id']) {
            throw new \Exception('A valid Command has to have an id associated with it!');
        }

        $quoteKeyData= [];
        array_walk($data, function ($value, $key) use (&$quoteKeyData): void { $quoteKeyData['`'.$key.'`'] = $value; });

        if (isset($params['oldId'])) {
            if ($params['oldId'] !== '') {
                $this->db->update($this->getTableName(), $quoteKeyData, ['id' => $params['oldId']]);
            } else {
                $this->db->insert($this->getTableName(), $quoteKeyData);
            }
        } elseif ($id = $this->getById($id = $this->model->getId())) {
            $this->db->update($this->getTableName(), $quoteKeyData, ['id' => $this->model->getId()]);
        } else {
            $this->db->insert($this->getTableName(), $quoteKeyData);
        }

        return $this->getById($this->model->getId());
    }

    public function getById(mixed $id): ?Configuration
    {
        /**
         * @var Configuration|null $model
         */
        $model = parent::getById($id);
        if ($model) {
            $className = $model->getExecutorClass();
            if ($className) {
                $class = new $className;
                $class->setDataFromResource($model);
            }

            return $model;
        }

        return null;
    }
}
