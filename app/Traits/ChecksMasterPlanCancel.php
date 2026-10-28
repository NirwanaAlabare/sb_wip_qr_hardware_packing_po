<?php

namespace App\Traits;

use DB;

trait ChecksMasterPlanCancel
{
    /**
     * Check the current master plan's cancel flag straight from the database,
     * since $this->orderInfo is only a snapshot taken when the plan was loaded.
     * Emits an alert when the plan has been cancelled.
     */
    protected function masterPlanCancelled(): bool
    {
        if (!$this->orderInfo) {
            return false;
        }

        $cancel = DB::connection('mysql_sb')->table('master_plan')->where('id', $this->orderInfo->id)->value('cancel');

        if ($cancel == 'Y') {
            $this->emit('alert', 'error', "Master Plan sudah dibatalkan. Harap refresh browser anda.");

            return true;
        }

        return false;
    }
}
