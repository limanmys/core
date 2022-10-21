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

    protected $users;

    protected $server;

    protected $extension;

    protected $to;

    protected $target;

    public function __construct(protected CronMail $obj)
    {
        $this->users = User::find(json_decode((string) $obj->user_id));
        $this->server = Server::find($obj->server_id);
        $this->extension = Extension::find($obj->extension_id);
        $this->to = json_decode((string) $this->obj->to);
        $this->target = json_decode((string) $this->obj->target);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (!$this->doubleCheckTime()) {
            return;
        }

        if (!is_file("/liman/logs/liman_new.log")) {
            return;
        }

        $now = Carbon::now()->unix();
        switch ($this->obj->cron_type) {
            case 'hourly':
                $before = Carbon::now()->subHour()->unix();
                break;
            case 'daily':
                $before = Carbon::now()->subDay()->unix();
                break;
            case 'weekly':
                $before = Carbon::now()->subWeek()->unix();
                break;
            case 'monthly':
                $before = Carbon::now()->subMonth()->unix();
                break;
        }

        foreach ($this->target as $target) {
            $encoded = base64_encode($this->obj->extension_id . '-' . $this->obj->server_id . '-' . $target);
            $logs = Command::runLiman('cat /liman/logs/liman_new.log | grep @{:encoded}', [
                'encoded' => $encoded
            ]);

            $logs = explode("\n", $logs);
            foreach ($logs as $key => &$item) {
                $tmp = json_decode($item);

                if (!isset($tmp->ts)) {
                    continue;
                }

                if ($tmp->ts < $before) {
                    unset($logs[$key]);
                }
            }
            $logs = implode("\n", $logs);

            foreach ($this->users as $user) {
                $output = Command::runLiman("echo ':logs:' | grep @{:user_id}", [
                    'logs' => $logs,
                    'user_id' => $user->id,
                ]);

                $data = [];
                if (!empty($output)) {
                    foreach (explode("\n", $output) as $row) {
                        $message = json_decode($row);

                        if (isset($message->request_details->data)) {
                            $decoded = json_decode((string) $message->request_details->data);
                            $data[] = $decoded;
                        }
                    }
                } else {
                    continue;
                }

                $count = Command::runLiman('echo @{:logs} | grep @{:user_id} | wc -l', [
                    'logs' => $logs,
                    'user_id' => $user->id,
                ]);

                foreach ($this->to as $to) {
                    if ((int) $count == 0) {
                        continue;
                    }

                    $view = view('email.cron_mail', [
                        'user_name' => $user->name,
                        'subject' => 'Liman MYS Bilgilendirme',
                        'result' => $count,
                        'data' => $data,
                        'before' => Carbon::parse($before)->isoFormat("LLL"),
                        'now' => Carbon::parse($now)->isoFormat("LLL"),
                        'server' => $this->server,
                        'extension' => $this->extension,
                        'target' => $this->getTagText($target, $this->extension->name),
                        'from' => trim((string) env('APP_NOTIFICATION_EMAIL')),
                        'to' => $to,
                    ])->render();

                    Mail::send([], [], function ($message) use ($view, $to) {
                        $message
                            ->to($to)
                            ->subject("Liman MYS Bilgilendirme")
                            ->from(trim((string) env('APP_NOTIFICATION_EMAIL')))
                            ->html($view)
                            ->text($view);
                    });

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

    private $tagTexts = [];

    private function getTagText($key, $extension_name)
    {
        if (!array_key_exists($extension_name, $this->tagTexts)) {
            $file = file_get_contents('/liman/extensions/' . strtolower((string) $extension_name) . '/db.json');
            $json = json_decode($file, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                return $key;
            }
            $this->tagTexts[$extension_name] = $json;
        }

        if (!array_key_exists('mail_tags', $this->tagTexts[$extension_name])) {
            return $key;
        }
        foreach ($this->tagTexts[$extension_name]['mail_tags'] as $obj) {
            if ($obj['tag'] == $key) {
                return $obj['description'];
            }
        }

        return $key;
    }

    public function doubleCheckTime()
    {
        $now = Carbon::now();
        $flag = false;
        switch ($this->obj->cron_type) {
            case 'hourly':
                $before = $now->subHour();
                if ($before->greaterThan($this->obj->last)) {
                    $flag = true;
                }
                break;
            case 'daily':
                $before = $now->subDay();
                if ($before->greaterThan($this->obj->last)) {
                    $flag = true;
                }
                break;
            case 'weekly':
                $before = $now->subWeek();
                if ($before->greaterThan($this->obj->last)) {
                    $flag = true;
                }
                break;
            case 'monthly':
                $before = $now->subMonth();
                if ($before->greaterThan($this->obj->last)) {
                    $flag = true;
                }
                break;
        }

        return $flag;
    }
}
