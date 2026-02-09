<?php

namespace MagicLogin\Dependencies\Twilio\Rest;

use MagicLogin\Dependencies\Twilio\Rest\Oauth\V1;

class Oauth extends OauthBase {
    /**
     * @deprecated Use v1->oauth instead.
     */
    protected function getOauth(): \MagicLogin\Dependencies\Twilio\Rest\Oauth\V1\OauthList {
        echo "oauth is deprecated. Use v1->oauth instead.";
        return $this->v1->oauth;
    }

    /**
     * @deprecated Use v1->oauth() instead.
     */
    protected function contextOauth(): \MagicLogin\Dependencies\Twilio\Rest\Oauth\V1\OauthContext {
        echo "oauth() is deprecated. Use v1->oauth() instead.";
        return $this->v1->oauth();
    }

    /**
     * @deprecated Use v1->deviceCode instead.
     */
    protected function getDeviceCode(): \MagicLogin\Dependencies\Twilio\Rest\Oauth\V1\DeviceCodeList {
        echo "deviceCode is deprecated. Use v1->deviceCode instead.";
        return $this->v1->deviceCode;
    }

    /**
     * @deprecated Use v1->openidDiscovery instead.
     */
    protected function getOpenidDiscovery(): \MagicLogin\Dependencies\Twilio\Rest\Oauth\V1\OpenidDiscoveryList {
        echo "openidDiscovery is deprecated. Use v1->openidDiscovery instead.";
        return $this->v1->openidDiscovery;
    }

    /**
     * @deprecated Use v1->openidDiscovery() instead.
     */
    protected function contextOpenidDiscovery(): \MagicLogin\Dependencies\Twilio\Rest\Oauth\V1\OpenidDiscoveryContext {
        echo "openidDiscovery() is deprecated. Use v1->openidDiscovery() instead.";
        return $this->v1->openidDiscovery();
    }

    /**
     * @deprecated Use v1->token instead.
     */
    protected function getToken(): \MagicLogin\Dependencies\Twilio\Rest\Oauth\V1\TokenList {
        echo "token is deprecated. Use v1->token instead.";
        return $this->v1->token;
    }

    /**
     * @deprecated Use v1->userInfo instead.
     */
    protected function getUserInfo(): \MagicLogin\Dependencies\Twilio\Rest\Oauth\V1\UserInfoList {
        echo "userInfo is deprecated. Use v1->userInfo instead.";
        return $this->v1->userInfo;
    }

    /**
     * @deprecated Use v1->userInfo() instead.
     */
    protected function contextUserInfo(): \MagicLogin\Dependencies\Twilio\Rest\Oauth\V1\UserInfoContext {
        echo "userInfo() is deprecated. Use v1->userInfo() instead.";
        return $this->v1->userInfo();
    }
}