@props([
    'cancelHref',
    'endsOnMin' => null,
    'posterUrl' => null,
    'submitLabel',
])

<form wire:submit="submit">
    <div class="activityForm">
        <div class="activityForm__poster">
            <x-field
                :description="__('agenda/form.poster.description', ['width' => App\Models\Activity::POSTER_WIDTH, 'height' => App\Models\Activity::POSTER_HEIGHT])"
                :label="__('agenda/form.poster.label')"
                model="poster"
            >
                <div @class(['activityPoster', 'activityPoster--placeholder' => ! $posterUrl])>
                    @if ($posterUrl)
                        <img alt="" class="activityPoster__img" src="{{ $posterUrl }}">
                    @else
                        <x-icon icon="image" />
                    @endif
                </div>
                <x-input.upload accept="image/jpeg,image/png,image/webp" model="poster" />
            </x-field>
        </div>
        <div class="activityForm__fields flex flex-col flex-gap-xl">
            <div class="flex flex-col flex-gap-m">
                <x-field :label="__('agenda/form.title.label')" model="form.title">
                    <x-input.text large maxlength="120" model="form.title" required />
                </x-field>
                <x-field :label="__('agenda/form.category.label')" model="form.category">
                    <x-input.select
                        :empty="__('agenda/form.category.placeholder')"
                        model="form.category"
                        :options="App\Enums\ActivityCategory::options()"
                        required
                    />
                </x-field>
                <div class="activityForm__row">
                    <x-field :label="__('agenda/form.starts_on.label')" model="form.starts_on">
                        <x-input.text required type="date" wire:model.live="form.starts_on" />
                    </x-field>
                    <x-field :label="__('agenda/form.starts_at.label')" model="form.starts_at">
                        <x-input.text model="form.starts_at" required type="time" />
                    </x-field>
                </div>
                <div class="activityForm__row">
                    <x-field :label="__('agenda/form.ends_on.label')" model="form.ends_on">
                        <x-input.text :min="$endsOnMin" model="form.ends_on" required type="date" />
                    </x-field>
                    <x-field :label="__('agenda/form.ends_at.label')" model="form.ends_at">
                        <x-input.text model="form.ends_at" type="time" />
                    </x-field>
                </div>
                <x-field :label="__('agenda/form.description.label')" model="form.description">
                    <x-editor model="form.description" required />
                </x-field>
                <x-field
                    :description="config('services.google.api_key') ? __('agenda/form.location.description') : null"
                    :label="__('agenda/form.location.label')"
                    model="form.location_name"
                >
                    @if (config('services.google.api_key'))
                        <x-input.place-autocomplete
                            :no-results="__('agenda/form.location.no_results')"
                            :placeholder="__('agenda/form.location.search_placeholder')"
                        />
                    @endif
                    <div class="activityForm__location">
                        <x-input.text
                            model="form.location_name"
                            :placeholder="__('agenda/form.location_name.placeholder')"
                            required
                        />
                        <x-input.text
                            model="form.location_address"
                            :placeholder="__('agenda/form.location_address.placeholder')"
                        />
                    </div>
                </x-field>
                <x-field
                    :description="__('agenda/form.price.description')"
                    :label="__('agenda/form.price.label')"
                    model="form.price"
                >
                    <x-input.text inputmode="decimal" min="0" model="form.price" required step="0.01" type="number">
                        <span class="input__unit">€</span>
                    </x-input.text>
                </x-field>
                <x-field :label="__('agenda/form.website_url.label')" model="form.website_url">
                    <x-input.text
                        maxlength="255"
                        model="form.website_url"
                        :placeholder="__('agenda/form.website_url.placeholder')"
                        type="url"
                    />
                </x-field>
            </div>
            <div class="flex flex-align-center flex-justify-spaceBetween flex-gap-m">
                <div class="flex flex-align-center flex-gap-m">
                    <x-btn primary submit>{{ $submitLabel }}</x-btn>
                    <x-btn text href="{{ $cancelHref }}">@lang('ui.cancel')</x-btn>
                </div>
                {{ $secondaryActions ?? '' }}
            </div>
        </div>
    </div>
</form>
