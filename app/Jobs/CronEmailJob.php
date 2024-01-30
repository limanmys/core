<?php

namespace App\Jobs;

use App\Models\CronMail;
use App\Models\Extension;
use App\Models\Server;
use App\System\Command;
use App\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class CronEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $users;
    protected Server $server;
    protected Extension $extension;
    protected array $to;
    protected array $target;
    private array $tagTexts = [];

    public function __construct(protected CronMail $obj)
    {
        $this->users = User::find(json_decode((string) $obj->user_id));
        $this->server = Server::find($obj->server_id);
        $this->extension = Extension::find($obj->extension_id);
        $this->to = json_decode((string) $this->obj->to);
        $this->target = json_decode((string) $this->obj->target);
    }

    public function handle()
    {
        if (!$this->doubleCheckTime()) {
            return;
        }

        if (!is_file("/liman/logs/liman_new.log")) {
            return;
        }

        $now = Carbon::now()->unix();
        $before = $this->calculateBeforeTime();

        foreach ($this->target as $target) {
            $encoded = base64_encode("{$this->obj->extension_id}-{$this->obj->server_id}-{$target}");
            $logs = $this->getFilteredLogs($encoded, $before);

            foreach ($this->users as $user) {
                $output = $this->getUserLogs($user->id, $logs);

                $data = [];
                if (!empty($output)) {
                    $data = $this->extractUserData($output);
                } else {
                    continue;
                }

                $count = $this->getUserLogsCount($user->id, $logs);

                foreach ($this->to as $to) {
                    if ((int) $count == 0) {
                        continue;
                    }

                    $view = $this->renderEmailView($user, $count, $data, $before, $now, $to);

                    $this->sendEmail($to, $view);

                    if (env('MAIL_DEBUG')) {
                        echo "---BEGIN---\n$output\n---END---\n";
                    }
                }
            }
        }

        $this->obj->update([
            'last' => Carbon::now(),
        ]);
    }

    // Diğer yardımcı metotları buraya ekleyebilirsiniz...

    private function calculateBeforeTime(): int
    {
        switch ($this->obj->cron_type) {
            case 'hourly':
                return Carbon::now()->subHour()->unix();
            case 'daily':
                return Carbon::now()->subDay()->unix();
            case 'weekly':
                return Carbon::now()->subWeek()->unix();
            case 'monthly':
                return Carbon::now()->subMonth()->unix();
            default:
                return 0;
        }
    }

    // Diğer yardımcı metotları buraya ekleyebilirsiniz...
}
