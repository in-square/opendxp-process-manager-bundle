<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem\Listing;

use InSquare\OpendxpProcessManagerBundle\InSquareOpendxpProcessManagerBundle;
use InSquare\OpendxpProcessManagerBundle\Helper;
use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;
use OpenDxp\Model;

class Dao extends Model\Listing\Dao\AbstractDao
{
    protected $model;

    protected function getTableName(): string
    {
        return InSquareOpendxpProcessManagerBundle::TABLE_NAME_MONITORING_ITEM;
    }

    /**
     * @return string
     */
    protected function getCondition(): string
    {
        $condition = '';
        if (($cond = $this->model->getCondition()) !== '' && ($cond = $this->model->getCondition()) !== '0') {
            $condition .= ' WHERE ' . $cond . ' ';
        }

        /**
         * @var \InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem\Listing $list
         */
        $list = $this->model;
        if (($user = $list->getUser()) && !$user->isAdmin()) {
            if ($ids = Helper::getAllowedConfigIdsByUser($user)) {
                if ($this->model->getCondition() !== '' && $this->model->getCondition() !== '0') {
                    $condition .= ' AND ';
                } else {
                    $condition .= ' WHERE ';
                }
                $condition .= ' configurationId IN(' . implode(', ', wrapArrayElements($ids, "'")).')';
            }
        }

        return $condition;
    }

    /**
     * @return array<mixed>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function load(): array
    {
        $sql = 'SELECT id FROM ' . $this->getTableName() . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit();
        $ids = $this->db->fetchFirstColumn($sql, $this->model->getConditionVariables());

        $items = [];
        foreach ($ids as $id) {
            $item = MonitoringItem::getById($id);
            if ($item) {//hack because somehow it can happen that we dont get a monitoring id if we are using multiprocessing and the element would be empty
                $items[] = $item;
            }
        }

        $this->model->setData($items);

        return $items;
    }

    public function getTotalCount(): int
    {
        return (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM ' . $this->getTableName() . ' '. $this->getCondition(), $this->model->getConditionVariables());
    }
}
