<?php

namespace MagicLogin\Dependencies\Twilio\Rest;

use MagicLogin\Dependencies\Twilio\Rest\Taskrouter\V1;
class Taskrouter extends TaskrouterBase {

    /**
     * @deprecated Use v1->workspaces instead.
     */
    protected function getWorkspaces(): \MagicLogin\Dependencies\Twilio\Rest\Taskrouter\V1\WorkspaceList {
        echo "workspaces is deprecated. Use v1->workspaces instead.";
        return $this->v1->workspaces;
    }

    /**
     * @deprecated Use v1->workspaces(\$sid) instead.
     * @param string $sid The SID of the resource to fetch
     */
    protected function contextWorkspaces(string $sid): \MagicLogin\Dependencies\Twilio\Rest\Taskrouter\V1\WorkspaceContext {
        echo "workspaces(\$sid) is deprecated. Use v1->workspaces(\$sid) instead.";
        return $this->v1->workspaces($sid);
    }
}