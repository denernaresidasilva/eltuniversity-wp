<?php

namespace MagicLogin\Dependencies\Twilio\Rest;

use MagicLogin\Dependencies\Twilio\Rest\PreviewMessaging\V1;

class PreviewMessaging extends PreviewMessagingBase {
    /**
     * @deprecated Use v1->oauth instead.
     */
    protected function getMessages(): \MagicLogin\Dependencies\Twilio\Rest\PreviewMessaging\V1\MessageList {
        return $this->v1->messages;
    }

    /**
     * @deprecated Use v1->oauth() instead.
     */
    protected function getBroadcasts(): \MagicLogin\Dependencies\Twilio\Rest\PreviewMessaging\V1\BroadcastList {
        return $this->v1->broadcasts;
    }
}
