<?php

namespace App\Models;

use App\Enums\MeetingResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingDecision extends Model
{
    protected $table = 'meeting_decisions';
    protected $primaryKey = 'meeting_decision_id';

    protected $fillable = [
        'application_id',
        'meeting_title',
        'meeting_date',
        'department',
        'checked_by',
        'approver_name',
        'approver_position',
        'result',
        'reason',
        'amendments',
        'minutes_summary',
        'minutes_file_name',
        'minutes_file_path',
        'recorded_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
            'result' => MeetingResult::class,
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class, 'application_id', 'application_id');
    }

    /** Officer who recorded the decision in the system */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'recorded_by_admin_id', 'admin_id');
    }

    /** "Dato' Hj. Kamal (Pengerusi JK OSC)" */
    public function approver(): string
    {
        return $this->approver_name . ' (' . $this->approver_position . ')';
    }

    /** Short text used in the status history and the applicant's notification */
    public function summaryLine(): string
    {
        $line = $this->meeting_title . ' (' . $this->meeting_date->format('d/m/Y') . ', ' . $this->department . '): '
            . $this->result->label() . '. Diputuskan oleh ' . $this->approver() . '.';

        if ($this->result === MeetingResult::LulusDenganPindaan) {
            $line .= ' Pindaan diperlukan: ' . $this->amendments;
        } elseif ($this->result === MeetingResult::Ditolak) {
            $line .= ' Sebab: ' . $this->reason;
        }

        return $line;
    }
}
