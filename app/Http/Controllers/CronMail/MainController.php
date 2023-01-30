<?php

namespace App\Http\Controllers\CronMail;

use App\Http\Controllers\Controller;
use App\Jobs\CronEmailJob;
use App\Models\CronMail;
use App\Models\Extension;
use App\Models\Server;
use App\User;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;

/**
 * Cron Mail Controller
 *
 * @extends Controller
 */
class MainController extends Controller
{
    private $tagTexts = [];

    /**
     * Get mail tags from extension
     *
     * @return JsonResponse|Response
     */
    public function getMailTags()
    {
        $path = '/liman/extensions/' . strtolower((string) extension()->name) . '/db.json';
        if (! is_file($path)) {
            return respond('Bu eklentinin bir veritabanı yok!', 201);
        }

        $file = file_get_contents($path);

        $json = json_decode($file, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            return respond('Eklenti veritabanı okunamıyor.', 201);
        }

        if (array_key_exists('mail_tags', $json)) {
            return respond($json['mail_tags']);
        } else {
            return respond([]);
        }
    }

    /**
     * Create cron mail
     *
     * @return Application|JsonResponse|RedirectResponse|Response|Redirector
     */
    public function addCronMail()
    {
        validate([
            'user_id' => 'required|array',
            'user_id.*' => 'exists:users,id',
            'server_id' => 'required|exists:servers,id',
            'extension_id' => 'required|exists:extensions,id',
            'target' => 'required',
            'cron_type' => 'required|in:hourly,daily,weekly,monthly',
            'to' => 'required|array',
            'to.*' => 'email',
        ]);

        $request = request()->all();
        $request['user_id'] = json_encode($request['user_id']);
        $request['to'] = json_encode($request['to']);
        $request['target'] = json_encode($request['target']);
        $request['last'] = Carbon::now()->subDecade();

        $cron_mail = CronMail::create($request);
        if ($cron_mail) {
            return redirect('/ayarlar#mailSettings');
        } else {
            return respond('Mail ayarı eklenemedi!', 201);
        }
    }

    /**
     * Delete cron mail
     *
     * @return JsonResponse|Response
     */
    public function deleteCronMail()
    {
        $obj = CronMail::find(request('cron_id'));

        if ($obj == null) {
            return respond('Bu mail ayarı bulunamadı!');
        }

        if ($obj->delete()) {
            return respond('Mail ayarı başarıyla silindi');
        } else {
            return respond('Mail ayarı silinemedi!', 201);
        }
    }

    /**
     * Get cron mail object
     *
     * @return Application|Factory|View
     */
    public function getCronMail()
    {
        $mails = CronMail::all()->map(function ($obj) {
            $ext = Extension::find($obj->extension_id);
            if ($ext) {
                $obj->extension_name = $ext->display_name;
                $target_list = json_decode((string) $obj->target);
                foreach ($target_list as &$target) {
                    $target = $this->getTagText($target, $ext->name);
                }
                $obj->tag_string = implode(', ', $target_list);
            } else {
                $obj->extension_name = 'Bu eklenti silinmiş!';
                $obj->tag_string = 'Bu eklenti silinmiş!';
            }

            $srv = Server::find($obj->server_id);
            if ($srv) {
                $obj->server_name = $srv->name;
            } else {
                $obj->server_name = 'Bu sunucu silinmiş!';
            }

            $user_ids = json_decode((string) $obj->user_id);
            $users = [];
            foreach ($user_ids as $usr) {
                try {
                    $user = User::find($usr);
                } catch (\Throwable) {
                    continue;
                }
                $users[] = $user->name;
            }

            $obj->username = implode(', ', $users);

            $obj->to = implode(', ', json_decode((string) $obj->to));

            return $obj;
        });

        return view('settings.mail', [
            'cronMails' => $mails,
        ]);
    }

    /**
     * Get tag text as human readable format
     *
     * @param $key
     * @param $extension_name
     * @return mixed
     */
    private function getTagText($key, $extension_name)
    {
        if (! array_key_exists($extension_name, $this->tagTexts)) {
            $file = file_get_contents('/liman/extensions/' . strtolower((string) $extension_name) . '/db.json');
            $json = json_decode($file, true);
            if (json_last_error() != JSON_ERROR_NONE) {
                return $key;
            }
            $this->tagTexts[$extension_name] = $json;
        }

        if (! array_key_exists('mail_tags', $this->tagTexts[$extension_name])) {
            return $key;
        }
        foreach ($this->tagTexts[$extension_name]['mail_tags'] as $obj) {
            if ($obj['tag'] == $key) {
                return $obj['description'];
            }
        }

        return $key;
    }

    /**
     * Run cron mail job instantly
     *
     * @return JsonResponse|Response
     */
    public function sendNow()
    {
        $obj = CronMail::find(request('cron_id'));

        if ($obj == null) {
            return respond('Bu mail ayarı bulunamadı!', 201);
        }

        $obj->update([
            'last' => Carbon::now()->subDecade(),
        ]);

        $job = (new CronEmailJob(
            $obj
        ))->onQueue('cron_mail');
        app(Dispatcher::class)->dispatch($job);

        return respond('İşlem başlatıldı, tamamlandığında size mail ulaşacaktır.');
    }

    /**
     * Create cron mail view
     *
     * @return Application|Factory|View
     */
    public function getView()
    {
        return view('settings.add_mail');
    }

    /**
     * Edit cron mail view
     *
     * @return Application|Factory|View
     */
    public function editView()
    {
        $id = request()->id;

        $cron_mail = CronMail::findOrFail($id);

        $users = json_decode((string) $cron_mail->user_id);
        $users = User::find($users);

        $to = json_decode((string) $cron_mail->to);
        $target = json_decode((string) $cron_mail->target);

        return view('settings.edit_mail', compact(['cron_mail', 'users', 'to', 'target']));
    }

    /**
     * Edit cron mail settings
     *
     * @return JsonResponse|Response
     */
    public function edit()
    {
        validate([
            'user_id' => 'required|array',
            'user_id.*' => 'exists:users,id',
            'server_id' => 'required|exists:servers,id',
            'extension_id' => 'required|exists:extensions,id',
            'target' => 'required',
            'cron_type' => 'required|in:hourly,daily,weekly,monthly',
            'to' => 'required|array',
            'to.*' => 'email',
        ]);

        $id = request()->id;

        $cron_mail = CronMail::findOrFail($id);

        $request = request()->all();
        $request['user_id'] = json_encode($request['user_id']);
        $request['to'] = json_encode($request['to']);

        $cron_mail->update($request);

        return respond('Mail ayarı başarıyla düzenlendi.');
    }
}
