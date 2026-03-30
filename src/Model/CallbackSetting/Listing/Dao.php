<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Model\CallbackSetting\Listing;

use InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle;
use InSquare\OpendxpProcessManagerBundle\Model\CallbackSetting;
use OpenDxp\Model;

class Dao extends Model\Listing\Dao\AbstractDao
{
    protected function getTableName(): string
    {
        return InSquareOpendxpProcessManagerBundle::TABLE_NAME_CALLBACK_SETTING;
    }

    /**
     * @return CallbackSetting[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function load(): array
    {
        $sql = 'SELECT id FROM '.$this->getTableName().$this->getCondition().$this->getOrder().$this->getOffsetLimit();
        $ids = $this->db->fetchFirstColumn($sql, $this->model->getConditionVariables());

        $items = [];
        foreach ($ids as $id) {
            $items[] = CallbackSetting::getById($id);
        }

        $this->model->setData($items);

        return $items;
    }

    public function getTotalCount(): int
    {
        return (int)$this->db->fetchOne(
            'SELECT COUNT(*) as amount FROM '.$this->getTableName().' '.$this->getCondition(),
            $this->model->getConditionVariables()
        );
    }
}
