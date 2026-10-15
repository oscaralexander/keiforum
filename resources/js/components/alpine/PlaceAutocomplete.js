// Amersfoort city centre, used to prefer nearby places in suggestions
const LOCATION_BIAS = {
    center: { lat: 52.1561, lng: 5.3878 },
    radius: 15000,
};

const waitForGoogleMaps = (timeout = 10000) => new Promise((resolve, reject) => {
    const startedAt = Date.now();

    const check = () => {
        if (window.google?.maps?.importLibrary) {
            resolve(window.google.maps);
        } else if (Date.now() - startedAt > timeout) {
            reject(new Error('Google Maps did not load'));
        } else {
            setTimeout(check, 100);
        }
    };

    check();
});

export default (minCharacters = 3) => {
    // Google's prediction objects are kept outside Alpine's reactive state,
    // since proxying them breaks their methods
    let predictions = [];
    let places = null;
    let sessionToken = null;

    const loadPlaces = async () => {
        places ??= await (await waitForGoogleMaps()).importLibrary('places');

        return places;
    };

    return {
        hasSearched: false,
        isLoading: false,
        minCharacters,
        results: [],
        selectedIndex: -1,

        onDown() {
            if (this.selectedIndex < this.results.length - 1) {
                this.selectedIndex++;
                this.scrollToSelected();
            }
        },

        onEscape() {
            this.$refs.input.blur();
        },

        onSelect() {
            if (this.selectedIndex > -1) {
                this.select(this.selectedIndex);
            }
        },

        onUp() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
                this.scrollToSelected();
            }
        },

        reset() {
            predictions = [];
            this.hasSearched = false;
            this.results = [];
            this.selectedIndex = -1;
        },

        scrollToSelected() {
            this.$el.querySelectorAll('.autocomplete__result').item(this.selectedIndex)?.scrollIntoView({ block: 'nearest' });
        },

        async search() {
            const query = this.$refs.input.value.trim();

            if (query.length < this.minCharacters) {
                this.reset();
                return;
            }

            this.isLoading = true;

            try {
                const { AutocompleteSessionToken, AutocompleteSuggestion } = await loadPlaces();

                // Suggestions and the details of the chosen place are billed as one session
                sessionToken ??= new AutocompleteSessionToken();

                const { suggestions } = await AutocompleteSuggestion.fetchAutocompleteSuggestions({
                    includedRegionCodes: ['nl'],
                    input: query,
                    language: 'nl',
                    locationBias: LOCATION_BIAS,
                    region: 'nl',
                    sessionToken,
                });

                // Ignore responses for a query the user has typed past
                if (query !== this.$refs.input.value.trim()) {
                    return;
                }

                predictions = suggestions.map(({ placePrediction }) => placePrediction).filter(Boolean);
                this.hasSearched = true;
                this.selectedIndex = predictions.length ? 0 : -1;
                this.results = predictions.map((prediction) => ({
                    description: prediction.secondaryText?.text ?? '',
                    label: prediction.mainText?.text ?? prediction.text.text,
                    placeId: prediction.placeId,
                }));
            } catch (error) {
                // The plain location inputs still work without autocomplete
                console.warn(error);
                this.reset();
            } finally {
                this.isLoading = false;
            }
        },

        async select(index) {
            const prediction = predictions[index];

            if (!prediction) {
                return;
            }

            this.isLoading = true;

            try {
                const place = prediction.toPlace();

                await place.fetchFields({ fields: ['displayName', 'formattedAddress', 'id', 'location'] });

                this.$wire.form.location_name = place.displayName ?? '';
                this.$wire.form.location_address = place.formattedAddress ?? '';
                this.$wire.form.location_place_id = place.id ?? '';
                this.$wire.form.location_latitude = place.location?.lat() ?? null;
                this.$wire.form.location_longitude = place.location?.lng() ?? null;
            } catch (error) {
                console.warn(error);
            } finally {
                sessionToken = null;
                this.isLoading = false;
                this.$refs.input.value = '';
                this.$refs.input.blur();
                this.reset();
            }
        },
    };
};
