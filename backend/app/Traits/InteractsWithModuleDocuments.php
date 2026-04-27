<?php

namespace App\Traits;

use App\Models\ModuleDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait InteractsWithModuleDocuments
{
    protected $pendingModuleDocuments = [];
    public static function bootInteractsWithModuleDocuments()
    {
        static::saved(function ($model) {
            $model->savePendingModuleDocuments();
        });
    }

    public function __call($method, $parameters)
    {
        if (property_exists($this, 'moduleDocumentKeys')) {
            if (Str::startsWith($method, 'get') && Str::endsWith($method, 'Attribute')) {
                $key = Str::snake(substr($method, 3, -9));
                if (in_array($key, $this->moduleDocumentKeys)) {
                    return $this->getModuleDocument($key);
                }
            }

            if (Str::startsWith($method, 'set') && Str::endsWith($method, 'Attribute')) {
                $key = Str::snake(substr($method, 3, -9));
                if (in_array($key, $this->moduleDocumentKeys)) {
                    $this->setAttribute($key, $parameters[0] ?? null);
                    return $this;
                }
            }
        }

        return parent::__call($method, $parameters);
    }

    public function moduleDocuments()
    {
        return $this->morphMany(ModuleDocument::class, 'model');
    }

    public function getAttribute($key)
    {
        if (property_exists($this, 'moduleDocumentKeys') && in_array($key, $this->moduleDocumentKeys)) {
            return $this->getModuleDocument($key);
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value = null)
    {
        if (property_exists($this, 'moduleDocumentKeys') && in_array($key, $this->moduleDocumentKeys)) {
            $this->pendingModuleDocuments[$key] = $value;
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    protected function getModuleDocument($key)
    {
        if (array_key_exists($key, $this->pendingModuleDocuments)) {
            return $this->pendingModuleDocuments[$key];
        }

        $doc = $this->moduleDocuments()->where('name', $key)->first();

        if ($doc && !empty($doc->files) && is_array($doc->files)) {
            return $doc->files[0];
        }

        // Fallback check
        $fallbackMethod = 'get' . Str::studly($key) . 'Fallback';
        if (method_exists($this, $fallbackMethod)) {
            return $this->$fallbackMethod();
        }

        return null;
    }

    public function savePendingModuleDocuments()
    {
        foreach ($this->pendingModuleDocuments as $key => $file) {
            // Find existing document (including soft-deleted ones)
            $existingDoc = $this->moduleDocuments()->withTrashed()->where('name', $key)->first();

            if ($file) {
                if ($existingDoc) {
                    // Update existing document
                    if ($existingDoc->trashed()) {
                        // Restore if it was soft-deleted
                        $existingDoc->restore();
                    }
                    // Update the files
                    $existingDoc->update([
                        'files' => [$file],
                        'updated_by' => \Illuminate\Support\Facades\Auth::id(),
                    ]);
                } else {
                    // Create new document if it doesn't exist
                    $this->moduleDocuments()->create([
                        'name' => $key,
                        'files' => [$file],
                    ]);
                }
            } else {
                // If file is null/empty, delete the existing document (if any)
                if ($existingDoc) {
                    $existingDoc->delete();
                }
            }
        }

        $this->pendingModuleDocuments = [];
    }

    /**
     * Handle avatar upload from request (multipart file or base64).
     * Stores the file and sets the avatar attribute which will be saved to module_documents on save().
     *
     * @param Request $request
     * @param string $field Field name (default: 'avatar')
     * @param string $base64Field Base64 field name (default: 'avatar_base64')
     * @param string $directory Storage directory (default: 'user_avatar')
     * @return void
     */
    public function handleAvatarUpload(
        Request $request,
        string $field = 'avatar',
        string $base64Field = 'avatar_base64',
        string $directory = 'user_avatar'
    ): void {
        // Handle multipart file upload
        if ($request->hasFile($field)) {
            $file = $request->file($field);
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($directory, $filename, 'public');
            $this->{$field} = $path;
            return;
        }

        // Handle base64 upload
        if ($request->filled($base64Field)) {
            $base64 = $request->input($base64Field);

            // Remove data URI prefix if present
            if (str_contains($base64, ',')) {
                $base64 = explode(',', $base64, 2)[1];
            }

            $binary = base64_decode($base64, true);
            if ($binary === false) {
                return;
            }

            // Detect MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_buffer($finfo, $binary);
            finfo_close($finfo);

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ];

            $ext = $extensions[$mime] ?? 'jpg';
            $filename = Str::uuid() . '.' . $ext;
            $path = $directory . '/' . $filename;

            Storage::disk('public')->put($path, $binary);
            $this->{$field} = $path;
        }
    }
}
