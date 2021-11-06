<?php

namespace Morrislaptop\Journal\Http\Livewire;

use Spatie\EventSourcing\StoredEvents\Models\EloquentStoredEvent;

trait EventModalTrait
{
    // To show/hide the modal
    public bool $viewingModal = false;

    // The information currently being displayed in the modal
    public ?EloquentStoredEvent $currentModal = null;

    public function getTableRowUrl(): string
    {
        return '#';
    }

    public function setTableRowAttributes($row): array
    {
        return ['wire:click.prevent' => 'viewModal('.$row->id.')'];
    }

    public function viewModal(string $uuid): void
    {
        $this->viewingModal = true;
        $this->currentModal = $this->getQuery()->findOrFail($uuid);
    }

    public function resetModal(): void
    {
        $this->reset('viewingModal', 'currentModal');
    }

    public function modalsView(): string
    {
        return 'journal::modal';
    }
}
