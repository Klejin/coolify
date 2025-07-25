<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class ActivityMonitor extends Component
{
    public ?string $header = null;

    public $activityId;

    public $eventToDispatch = 'activityFinished';

    public $eventData = null;

    public $isPollingActive = false;

    public bool $fullHeight = false;

    public $activity;

    public bool $showWaiting = true;

    public static $eventDispatched = false;

    protected $listeners = ['activityMonitor' => 'newMonitorActivity'];

    public function newMonitorActivity($activityId, $eventToDispatch = 'activityFinished', $eventData = null)
    {
        $this->activityId = $activityId;
        $this->eventToDispatch = $eventToDispatch;
        $this->eventData = $eventData;

        $this->hydrateActivity();

        $this->isPollingActive = true;
    }

    public function hydrateActivity()
    {
        $this->activity = Activity::find($this->activityId);
    }

    public function polling()
    {
        $this->hydrateActivity();
        $exit_code = data_get($this->activity, 'properties.exitCode');
        if ($exit_code !== null) {
            $this->isPollingActive = false;
            if ($exit_code === 0) {
                if ($this->eventToDispatch !== null) {
                    if (str($this->eventToDispatch)->startsWith('App\\Events\\')) {
                        $causer_id = data_get($this->activity, 'causer_id');
                        $user = User::find($causer_id);
                        if ($user) {
                            $teamId = $user->currentTeam()->id;
                            if (! self::$eventDispatched) {
                                if (filled($this->eventData)) {
                                    $this->eventToDispatch::dispatch($teamId, $this->eventData);
                                } else {
                                    $this->eventToDispatch::dispatch($teamId);
                                }
                                self::$eventDispatched = true;
                            }
                        }

                        return;
                    }
                    if (! self::$eventDispatched) {
                        if (filled($this->eventData)) {
                            $this->dispatch($this->eventToDispatch, $this->eventData);
                        } else {
                            $this->dispatch($this->eventToDispatch);
                        }
                        self::$eventDispatched = true;
                    }
                }
            }
        }
    }
}
