<?php

use App\Models\ModuleDocument;
use Illuminate\Database\Eloquent\Model;

if (!function_exists('get_module_document_image_url')) {
    /**
     * Get image URL from module_documents table for a given model and document name
     *
     * @param Model $model The model instance (must use InteractsWithModuleDocuments trait)
     * @param string $docName The document name (e.g., 'avatar', 'certification_0_image', 'award_1_image')
     * @param string|null $fallbackPath Optional fallback file path if module document not found
     * @return string|null The full image URL or null if not found
     */
    function get_module_document_image_url(Model $model, string $docName, ?string $fallbackPath = null): ?string
    {
        if (!method_exists($model, 'moduleDocuments')) {
            // If model doesn't have moduleDocuments relationship, return fallback
            return $fallbackPath ? storage_url($fallbackPath) : null;
        }

        $moduleDoc = $model->moduleDocuments()->where('name', $docName)->first();

        if ($moduleDoc && !empty($moduleDoc->files) && is_array($moduleDoc->files)) {
            return storage_url($moduleDoc->files[0]);
        }

        // Fallback to provided path if module document not found
        if ($fallbackPath) {
            return storage_url($fallbackPath);
        }

        return null;
    }
}

if (!function_exists('format_repeater_with_images')) {
    /**
     * Format a repeater array (like certifications_info, awards_info) with image URLs from module_documents
     *
     * @param Model $model The model instance (must use InteractsWithModuleDocuments trait)
     * @param array|null $repeaterData The repeater array data (e.g., certifications_info, awards_info)
     * @param string $prefix The prefix for document names (e.g., 'certification', 'award')
     * @param string $imageField The field name in the repeater data that contains the image path (e.g., 'certification_image', 'award_image')
     * @param string $urlField The field name to add for the image URL (e.g., 'certification_image_url', 'award_image_url')
     * @return array|null The formatted array with image URLs or null if input is null/empty
     */
    function format_repeater_with_images(
        Model $model,
        ?array $repeaterData,
        string $prefix,
        string $imageField,
        string $urlField
    ): ?array {
        if (!$repeaterData || !is_array($repeaterData) || empty($repeaterData)) {
            return $repeaterData;
        }

        return array_map(function ($item, $index) use ($model, $prefix, $imageField, $urlField) {
            $docName = "{$prefix}_{$index}_{$imageField}";
            $fallbackPath = $item[$imageField] ?? null;

            $item[$urlField] = get_module_document_image_url($model, $docName, $fallbackPath);

            return $item;
        }, $repeaterData, array_keys($repeaterData));
    }
}
