<?php

namespace App\Models;

use App\Models\BackPanel\Gallery;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Common extends Model
{
    use HasFactory;


    public static function uploadFile($location, $file)
    {
        try {
            $extension = $file->getClientOriginalExtension();
            if (!in_array($extension, ['png', 'jpg', 'jpeg']))
                throw new Exception('File format is not matched, upload in list (PNG/JPG/JPEG', 1);

            $tempName = Str::random(30) . '-' . time() . '.' . $extension;
            $storeFile = $file->storeAs($location, $tempName, 'public');

            if (empty($storeFile))
                return false;

            return $tempName;
        } catch (Exception $e) {
            throw $e;
        }
    }

    //upload cropped image
    public static function uploadCroppedImage($location, $file)
    {
        try {
            if (!empty($file)) {
                $image_parts = explode(";base64,", $file);
                $image_base64 = base64_decode($image_parts[1]);
                $imageName = Str::random(30) . '-' . time() . '.png';
                $storeFile = Storage::disk('public')->put($location . '/' . $imageName, $image_base64);
            }
            if (empty($storeFile))
                return false;

            return $imageName;
        } catch (Exception $e) {
            throw $e;
        }
    }

    //Upload pdf
    public static function uploadPDF($location, $file)
    {
        try {
            $extension = $file->getClientOriginalExtension();
            if (!in_array($extension, ['pdf', 'doc'])) {
                throw new Exception('File format is not matched, upload in list (PDF/DOC', 1);
            }
            $tempName = Str::random(30) . '-' . time() . '.' . $extension;
            $storeFile = $file->storeAs($location, $tempName, 'public');
            if (empty($storeFile)) {
                return false;
            }
            return $tempName;
        } catch (Exception $e) {
            throw $e;
        }
    }


    // update data
    public static function updateContact($post, $class)
    {
        try {

            if (!$class::where(['id' => $post['id']])->update(['is_contacted' => $post['is_contacted'], 'updated_at' => Carbon::now()])) {
                throw new Exception("Couldn't Update Data. Please try again", 1);
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }




    // permanent Delete data
    public static function deleteRelationData($post, $class, $filepath)
    {
        try {
            $query = $class::query();
            if ($post['type'] === "trashed") {

                if (method_exists($class, 'images')) {
                    $query->with('images');
                }

                if (method_exists($class, 'videos')) {
                    $query->with('videos');
                }
                if (method_exists($class, 'teamCategory')) {
                    $query->with('teamCategory');
                }

                $postInstance = $query->findOrFail($post['id']);


                //delete relation -start
                // Delete related images
                if ($postInstance->images) {
                    foreach ($postInstance->images as $image) {

                        if (!$image->delete()) {
                            throw new Exception("Couldn't Delete Data. Please try again", 1);
                        }

                        if (file_exists($filepath)) {
                            unlink($filepath . '/' . $image->image);
                        } else {
                            throw new Exception("Couldn't Delete Data. Please try again", 1);
                        }
                    }
                }

                // Delete related videos
                if ($postInstance->video) {
                    foreach ($postInstance->videos as $video) {
                        if (!$video->delete()) {
                            throw new Exception("Couldn't Delete Data. Please try again", 1);
                        }
                    }
                }

                // Delete related teamCategory
                if ($postInstance->teamCategory) {
                    if (!$postInstance->delete()) {
                        throw new Exception("Couldn't Delete Data. Please try again", 1);
                    }
                }

                //delete relation -end


                // Delete the main Gallery instance
                if ($postInstance->image) {
                    if (file_exists($filepath . '/' . $postInstance->image)) {
                        unlink($filepath . '/' . $postInstance->image);
                    }
                }

                if (!$postInstance->delete()) {
                    throw new Exception("Couldn't Delete Data. Please try again", 1);
                }
            } else {
                if (!$class::where(['id' => $post['id']])->update(['status' => 'N', 'updated_at' => Carbon::now()])) {
                    throw new Exception("Couldn't Delete Data. Please try again", 1);
                }
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function deleteSingleData($post, $class, $filepath)
    {
        try {
            if ($post['type'] === "trashed") {
                $postInstance =  $class->findOrFail($post['id']);
                if (!$postInstance->delete()) {
                    throw new Exception("Couldn't Delete Data. Please try again", 1);
                }

                if ($postInstance->feature_image) { //delete multiple json data feature image (post/news)

                    $decodedFeatureImages = json_decode($postInstance->feature_image, true);
                    foreach ($decodedFeatureImages as $image) {

                        if (file_exists($filepath . '/' . $image)) {
                            unlink($filepath . '/' . $image);
                        }
                    }
                }

                if (!empty($postInstance->image)) { // no image case
                    if (file_exists($filepath . '/' . $postInstance->image)) {
                        unlink($filepath . '/' . $postInstance->image);
                    } else {
                        throw new Exception("Couldn't Delete Data. Please try again", 1);
                    }
                }

                //apply condition for all delete option like this way
                if (!empty($postInstance->photo)) { // no Photo case 
                    if (file_exists($filepath . '/' . $postInstance->photo)) {
                        unlink($filepath . '/' . $postInstance->photo);
                    } else {
                        throw new Exception("Couldn't Delete Data. Please try again", 1);
                    }
                }

                if (!empty($postInstance->file)) { // no file case 
                    if (file_exists($filepath . '/' . $postInstance->file)) {
                        unlink($filepath . '/' . $postInstance->file);
                    } else {
                        throw new Exception("Couldn't Delete Data. Please try again", 1);
                    }
                }
            } else {
                if (!$class::where(['id' => $post['id']])->update(['status' => 'N', 'updated_at' => Carbon::now()])) {
                    throw new Exception("Couldn't Delete Data. Please try again", 1);
                }
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    public static function deleteSingleDataTwoImage($post, $class, $filepath)
    {
        try {
            if ($post['type'] === "trashed") {
                $postInstance =  $class->findOrFail($post['id']);
                if (!$postInstance->delete()) {
                    throw new Exception("Couldn't Delete Data. Please try again", 1);
                }
                if (!empty($filepath)) { // no image case
                    if (file_exists($filepath . '/' . $postInstance->thumbnail_image)) {
                        unlink($filepath . '/' . $postInstance->thumbnail_image);
                    }
                    if (file_exists($filepath . '/' . $postInstance->feature_image)) {
                        unlink($filepath . '/' . $postInstance->feature_image);
                    } else {
                        throw new Exception("Couldn't Delete Data. Please try again", 1);
                    }
                }
            } else {
                if (!$class::where(['id' => $post['id']])->update(['status' => 'N', 'updated_at' => Carbon::now()])) {
                    throw new Exception("Couldn't Delete Data. Please try again", 1);
                }
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
    public static function deleteDataFileDoesnotExists($post, $class)
    {
        try {
            if ($post['type'] === "trashed") {
                $postInstance =  $class->findOrFail($post['id']);
                if (!$postInstance->delete()) {
                    throw new Exception("Couldn't Delete Data. Please try again", 1);
                }
            } else {
                if (!$class::where(['id' => $post['id']])->update(['status' => 'N', 'updated_at' => Carbon::now()])) {
                    throw new Exception("Couldn't Delete Data. Please try again", 1);
                }
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    // Delete data without image
    public static function deleteData($post, $class)
    {
        try {

            if (!$class::where(['id' => $post['id']])->update(['status' => 'N'])) {
                throw new Exception("Couldn't Delete Data. Please try again", 1);
            }
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
}