<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Model\CallbackSetting;

use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;
use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem\Listing\Dao;
use OpenDxp\Model\Listing\AbstractListing;

/**
 * @method Dao getDao()
 * @method MonitoringItem[] load()
 * @method int getTotalCount()
 */
class Listing extends AbstractListing
{
    /**
     * Tests if the given key is a valid order key to sort the results
     *
     * @param mixed $key
     *
     * @return bool
     *
     * @todo remove the dummy-always-true rule
     *
     */
    public function isValidOrderKey(mixed $key): bool
    {
        return true;
    }
}
