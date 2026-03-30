<?php

/**
 * Created by valantic CX Austria GmbH
 *
 */

namespace InSquare\OpendxpProcessManagerBundle;

use OpenDxp\Cache\Runtime;

/**
 * @Deprecated Will be removed in the next major version
 */
trait ExecutionTraitClass
{
    use ExecutionTrait;

    /**
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        if (Runtime::isRegistered('process_manager_logger')) {
            return Runtime::get('process_manager_logger');
        }
    }
}
