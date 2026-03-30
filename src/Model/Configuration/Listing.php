<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle\Model\Configuration;

use InSquare\OpendxpProcessManagerBundle\Model\Configuration;
use OpenDxp\Model;

/**
 * @method \InSquare\OpendxpProcessManagerBundle\Model\Configuration\Listing\Dao getDao()
 * @method Configuration[] load()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    protected ?\OpenDxp\Model\User $user = null;

    /**
     * @param mixed $key
     *
     * @return bool
     */
    public function isValidOrderKey($key): bool
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
    public function setUser(?\OpenDxp\Model\User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
