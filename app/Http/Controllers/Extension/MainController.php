<?php

namespace App\Http\Controllers\Extension;

use App\Extension;
use App\Http\Controllers\Controller;
use App\Server;
use App\Script;

class MainController extends Controller
{
    public function all()
    {
        if (!Extension::where('_id', request('extension_id'))->exists()) {
            return redirect(route('home'));
        }

        // Get all Servers which have this extension.
        $servers = Server::all()->filter(function($value,$key){
            return array_key_exists(request('extension_id'),$value->extensions);
        });
        $servers = Server::filterPermissions($servers);

        // Go through servers and create a city list, it will be used in javascript to highlight cities in map.
        $cities = [];
        foreach ($servers as $server) {
            if(!in_array($server->city,$cities)){
                array_push($cities,$server->city);
            }
        }
        
        // If user have only servers in one city, redirect to it.
        if(count($cities) == 1){
            return redirect(route('extension_city',[
                "extension_id" => request('extension_id'),
                "city" => $cities[0]
            ]));
        }
        if($cities == null){
            return respond('Bu özelliği kullanabileceğiniz hiçbir sunucunuz yok.');
        }
        return view('feature.index', [
            "cities" => implode(',',$cities)
        ]);
    }

    public function city()
    {
        // Get all Servers which have this extension.
        $servers = Server::all()->filter(function($value,$key){
            return array_key_exists(request('extension_id'),$value->extensions);
        });
        $servers = Server::filterPermissions($servers);

        // Get Extension Name
        $extension = Extension::where('_id',request('extension_id'))->first();

        return view('feature.city', [
            "servers" => $servers,
            "name" => $extension->name
        ]);
    }

    public function download(){
        $extension = \App\Extension::where('_id',request('extension_id'))->first();
        $path = resource_path('views' . DIRECTORY_SEPARATOR .'extensions' . DIRECTORY_SEPARATOR . strtolower($extension->name));
        $zip = new \ZipArchive;
        $zip->open('/liman/export/' . $extension->name . '.lmne',\ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        $zip->addEmptyDir('views');
        foreach ($files as $name => $file){
            // Skip directories (they would be added automatically)
            if (!$file->isDir())
            {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($path) + 1);

                // Add current file to archive
                $zip->addFile($filePath, 'views/' . $relativePath);
            }
        }
        $scripts = Script::all()->where('extensions','like',strtolower($extension->name));

        $zip->addEmptyDir('scripts');

        foreach($scripts as $script){
            $zip->addFile(storage_path('app/scripts/') . $script->_id, 'scripts/' . $script->unique_code . '.lmns');
        }
        # DB Extraction
        $random = '/tmp/' . str_random(6);
        file_put_contents($random,$extension->toJson());
        $zip->addFile($random,'db.json');
        $zip->close();
        return response()->download('/liman/export/' . $extension->name . '.lmne')->deleteFileAfterSend();
    }

    public function upload(){
        $zip = new \ZipArchive;
        if(!$zip->open(request()->file('extension'))){
            return respond("Eklenti dosyasi acilamiyor.",201);
        }
        
        $random = str_random(10);
        $zip->extractTo('/tmp/' .$random);
        
        $path = '/tmp/' . $random;

        // Control to bypass GitHub, GitLab direct zip download.
        if(strpos(request()->file('extension')->getClientOriginalName(), '-master')){
            $path = '/tmp/' . $random . '/' . substr(request()->file('extension')->getClientOriginalName(), 0, -4);          
        }
        
        //Now that we have everything, let's extract file.
        $file = file_get_contents($path . '/db.json');
        $json = json_decode($file,true);
    
        $new = new \App\Extension();
        $new->fill($json);

        $extension_folder = resource_path('views/extensions/' . strtolower($new->name));

        // Delete existing folder.
        if(is_dir($extension_folder)){
            $this->rmdir_recursive($extension_folder);
        }

        mkdir($extension_folder);
        
        // Copy Views into the liman.
        shell_exec('cp -r ' . $path . '/views/* ' .  $extension_folder);
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path . '/scripts/'),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $name => $file){
            // Skip directories (they would be added automatically)
            if (!$file->isDir())
            {
                if(substr($file->getFilename(),0,1) == "."){
                    continue;
                }
                
                // Get real and relative path for current file
                $filePath = $file->getRealPath();

                $script = Script::readFromFile($filePath);
                shell_exec('cp ' . $filePath . ' ' .storage_path('app' . DIRECTORY_SEPARATOR . 'scripts' ) . DIRECTORY_SEPARATOR . $script->_id);
            }
        }

        $new->save();
        return respond("Eklenti basariyla eklendi",200);
    }

    private function rmdir_recursive($dir) {
        foreach(scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) continue;
            if (is_dir("$dir/$file")) $this->rmdir_recursive("$dir/$file");
            else unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
