<?php

namespace App\Filament\Resources\ModuleDocuments\Pages;

use App\Filament\Resources\ModuleDocuments\ModuleDocumentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewModuleDocument extends ViewRecord
{
    protected static string $resource = ModuleDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function deleteFileAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('deleteFile')
            ->requiresConfirmation()
            ->modalHeading('Delete File')
            ->modalDescription('Are you sure you want to delete this file or document record?')
            ->modalSubmitActionLabel('Yes, delete it')
            ->color('danger')
            ->action(function (array $arguments) {
                $documentId = $arguments['id'] ?? null;
                $fileIndex = $arguments['index'] ?? null;

                $document = \App\Models\ModuleDocument::find($documentId);
                
                if (!$document) {
                    \Filament\Notifications\Notification::make()
                        ->title('Document not found')
                        ->danger()
                        ->send();
                    return;
                }

                $files = $document->files ?? [];

                if ($fileIndex !== null && isset($files[$fileIndex])) {
                    // Update: For soft delete, we might NOT want to delete from storage immediately
                    // But usually, in this app's context, if they "remove" it, they want it gone.
                    // However, user said "as soft delete", so we'll just remove from array and save.
                    
                    unset($files[$fileIndex]);
                    $files = array_values($files);

                    if (empty($files)) {
                        $document->delete(); // Soft delete
                        \Filament\Notifications\Notification::make()
                            ->title('Document removed (soft deleted)')
                            ->warning()
                            ->send();
                        
                        if ($this->record->id == $documentId) {
                            return redirect($this->getResource()::getUrl('index'));
                        }
                    } else {
                        $document->update(['files' => $files]);
                        \Filament\Notifications\Notification::make()
                            ->title('File removed successfully')
                            ->success()
                            ->send();
                    }
                } else {
                    // Delete entire document (Soft delete)
                    $document->delete();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Record removed (soft deleted)')
                        ->success()
                        ->send();

                    if ($this->record->id == $documentId) {
                        return redirect($this->getResource()::getUrl('index'));
                    }
                }

                $this->refreshFormData(['document_details']);
            });
    }

    // Keep the old method but make it trigger the action if needed, 
    // or just let the blade call mountAction.
    public function deleteFile($documentId, $fileIndex = null)
    {
        $this->mountAction('deleteFile', ['id' => $documentId, 'index' => $fileIndex]);
    }
}
