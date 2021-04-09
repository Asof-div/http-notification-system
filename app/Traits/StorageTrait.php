<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

trait StorageTrait
{
  
    public function upload($bucket, $path, $filename, $file){
        
        if (Storage::disk($bucket)->exists($path.$filename)) {
            Storage::disk($bucket)->delete($path.$filename);
        }

        Storage::disk($bucket)->put($path.$filename, File::get($file));

    }

    public static function uploadFile($bucket, $path, $filename, $file){
        
        if (Storage::disk($bucket)->exists($path.$filename)) {
            Storage::disk($bucket)->delete($path.$filename);
        }

        Storage::disk($bucket)->put($path.$filename, File::get($file));

    }


    public static function copyFile($bucket, $from, $to){
        
        if (Storage::disk($bucket)->exists($from)) {
            if (Storage::disk($bucket)->exists($to)) {
                Storage::disk($bucket)->delete($to);
            }
            Storage::disk($bucket)->copy($from, $to);
            return true;
        }
        return false;
    }


    public static function deleteFile($bucket, $file){
        
        if(Storage::disk($bucket)->exists($file)){
            Storage::disk($bucket)->delete($file);
        }

    }

}