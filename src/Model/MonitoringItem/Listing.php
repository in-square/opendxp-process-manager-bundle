<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;

use InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem;

/**
 * Class Listing
 *
 * @method \InSquare\OpendxpProcessManagerBundle\Model\MonitoringItem\Listing\Dao getDao()
 * @method MonitoringItem[] load()
 * @method int getTotalCount()
 */
class Listing extends \OpenDxp\Model\Listing\AbstractListing
{
    protected ?\OpenDxp\Model\User $user = null;

    /**
     * Tests if the given key is a valid order key to sort the results
     *
     * @todo remove the dummy-always-true rule
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function isValidOrderKey(mixed $key): bool
    {
        return true;
    }

    public function getUser(): ?\OpenDxp\Model\User
    {
        return $this->user;
    }

    /**
     * @return $this
     */
    public function setUser(?\OpenDxp\Model\User $user)
    {
        $this->user = $user;

        return $this;
    }
}
