<?php

namespace Morrislaptop\Journal\Http\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class EventsTable extends DataTableComponent
{
    use EventModalTrait;

    public array $sorts = [
        'created_at' => 'desc',
        'aggregate_version' => 'desc',
    ];
    public $refresh = 2000;
    public int $perPage = 25;
    public array $perPageAccepted = [10, 25, 50];

    public function columns(): array
    {
        return [
            Column::make('UUID', 'aggregate_uuid'),

            Column::make('Event', 'aggregate_version')
                ->sortable()
                ->format(function ($value, $column, $row) {
                    return $row->aggregate_version . '@' . call_user_func(config('journal.event_class_to_label'), ($row->event_class));
                }),

            Column::make('Created', 'created_at')
                ->sortable()
                ->format(function ($value, $column, $row) {
                    return view('journal::created_at')->withDate(Carbon::parse($value));
                }),
        ];
    }

    public function filters(): array
    {
        $eventClasses = $this->getQuery()
            ->select('event_class')
            ->distinct()
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    $item->event_class => call_user_func(config('journal.event_class_to_label'), ($item->event_class)),
                ];
            });

        return [
            'event_class' => Filter::make('Event Class')
                ->select($eventClasses->prepend('Any', '')->toArray()),
            'created_from' => Filter::make('Created From')
                ->date(),
            'created_to' => Filter::make('Created To')
                ->date(),
            // 'tags' => Filter::make('Tags')
            //     ->multiSelect([
            //         'tag1' => 'Tags 1',
            //         'tag2' => 'Tags 2',
            //         'tag3' => 'Tags 3',
            //         'tag4' => 'Tags 4',
            //     ]),
        ];
    }

    protected function getQuery(): Builder
    {
        $storedEventModel = (string) config('event-sourcing.stored_event_model', EloquentStoredEvent::class);

        return $storedEventModel::query();
    }

    public function query(): Builder
    {
        return $this->getQuery()
            ->when($this->getFilter('event_class'), fn ($query, $type) => $query->where('event_class', $type))
            ->when($this->getFilter('created_from'), fn ($query, $date) => $query->where('created_at', '>=', $date))
            ->when($this->getFilter('created_to'), fn ($query, $date) => $query->where('created_at', '<=', $date))
            ->when($this->getFilter('search'), fn ($query, $search) => $this->addSearch($query, $search));
    }

    /**
     * @todo Do we want to search inside data or metadata? Probably very slow...
     */
    protected function addSearch(Builder $query, string $search)
    {
        // if it's a UUID, just search by aggregate_uuid
        if (Uuid::isValid($search)) {
            return $query->where('aggregate_uuid', $search);
        }

        // otherwise, let's convert to a uuid using our namespaces from config.
        $uuids = collect(config('journal.uuid5_namespaces'))
            ->map(fn ($ns) => Uuid::uuid5($ns, $search)->toString());

        return $query->whereIn('aggregate_uuid', $uuids);
    }

    public function configure(): void
    {
        // TODO: Implement configure() method.
    }

}
