<?php

namespace MagicLogin\Dependencies\Twilio\Rest;

use MagicLogin\Dependencies\Twilio\Rest\Voice\V1;

class Voice extends VoiceBase {
    /**
     * @deprecated Use v1->archivedCalls instead.
     */
    protected function getArchivedCalls(): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\ArchivedCallList {
        echo "archivedCalls is deprecated. Use v1->archivedCalls instead.";
        return $this->v1->archivedCalls;
    }

    /**
     * @deprecated Use v1->archivedCalls(\$date, \$sid) instead.
     * @param \DateTime $date The date of the Call in UTC.
     * @param string $sid The unique string that identifies this resource
     */
    protected function contextArchivedCalls(\DateTime $date, string $sid): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\ArchivedCallContext {
        echo "archivedCalls(\$date, \$sid) is deprecated. Use v1->archivedCalls(\$date, \$sid) instead.";
        return $this->v1->archivedCalls($date, $sid);
    }

    /**
     * @deprecated Use v1->byocTrunks instead.
     */
    protected function getByocTrunks(): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\ByocTrunkList {
        echo "byocTrunks is deprecated. Use v1->byocTrunks instead.";
        return $this->v1->byocTrunks;
    }

    /**
     * @deprecated Use v1->byocTrunks(\$sid) instead.
     * @param string $sid The unique string that identifies the resource
     */
    protected function contextByocTrunks(string $sid): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\ByocTrunkContext {
        echo "byocTrunks(\$sid) is deprecated. Use v1->byocTrunks(\$sid) instead.";
        return $this->v1->byocTrunks($sid);
    }

    /**
     * @deprecated Use v1->connectionPolicies instead.
     */
    protected function getConnectionPolicies(): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\ConnectionPolicyList {
        echo "connectionPolicies is deprecated. Use v1->connectionPolicies instead.";
        return $this->v1->connectionPolicies;
    }

    /**
     * @deprecated Use v1->connectionPolicies(\$sid) instead.
     * @param string $sid The unique string that identifies the resource
     */
    protected function contextConnectionPolicies(string $sid): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\ConnectionPolicyContext {
        echo "connectionPolicies(\$sid) is deprecated. Use v1->connectionPolicies(\$sid) instead.";
        return $this->v1->connectionPolicies($sid);
    }

    /**
     * @deprecated Use v1->dialingPermissions instead.
     */
    protected function getDialingPermissions(): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\DialingPermissionsList {
        echo "dialingPermissions is deprecated. Use v1->dialingPermissions instead.";
        return $this->v1->dialingPermissions;
    }

    /**
     * @deprecated Use v1->ipRecords instead.
     */
    protected function getIpRecords(): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\IpRecordList {
        echo "ipRecords is deprecated. Use v1->ipRecords instead.";
        return $this->v1->ipRecords;
    }

    /**
     * @deprecated Use v1->ipRecords(\$sid) instead.
     * @param string $sid The unique string that identifies the resource
     */
    protected function contextIpRecords(string $sid): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\IpRecordContext {
        echo "ipRecords(\$sid) is deprecated. Use v1->ipRecords(\$sid) instead.";
        return $this->v1->ipRecords($sid);
    }

    /**
     * @deprecated Use v1->sourceIpMappings instead.
     */
    protected function getSourceIpMappings(): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\SourceIpMappingList {
        echo "sourceIpMappings is deprecated. Use v1->sourceIpMappings instead.";
        return $this->v1->sourceIpMappings;
    }

    /**
     * @deprecated Use v1->sourceIpMappings(\$sid) instead.
     * @param string $sid The unique string that identifies the resource
     */
    protected function contextSourceIpMappings(string $sid): \MagicLogin\Dependencies\Twilio\Rest\Voice\V1\SourceIpMappingContext {
        echo "sourceIpMappings(\$sid) is deprecated. Use v1->sourceIpMappings(\$sid) instead.";
        return $this->v1->sourceIpMappings($sid);
    }
}