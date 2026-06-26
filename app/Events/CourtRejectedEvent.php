<?php
namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class CourtRejectedEvent implements ShouldDispatchAfterCommit
{
    public $ownerId;
    public $courtId;
    public $courtName;
    public $reason; // 🔥 lý do từ chối

    public function __construct($ownerId, $courtId, $courtName, $reason)
    {
        $this->ownerId = $ownerId;
        $this->courtId = $courtId;
        $this->courtName = $courtName;
        $this->reason = $reason;
    }
}