<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        modelValue: boolean;
        /** Google Maps API key (e.g. from usePage().props.googleMapsApiKey) */
        apiKey?: string;
        /** Initial center when no coords (e.g. Nairobi) */
        defaultLat?: number;
        defaultLng?: number;
        /** Pre-fill marker position when opening */
        initialLat?: number | null;
        initialLng?: number | null;
        /** If true, request geolocation on open and center map + set marker */
        useGeolocationOnOpen?: boolean;
        /** Optional modal title */
        title?: string;
    }>(),
    {
        apiKey: '',
        defaultLat: -1.286389,
        defaultLng: 36.817223,
        initialLat: null,
        initialLng: null,
        useGeolocationOnOpen: true,
        title: 'Select location',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    confirm: [payload: { lat: number; lng: number }];
}>();

const mapContainer = ref<HTMLElement | null>(null);
let map: google.maps.Map | null = null;
let marker: google.maps.Marker | null = null;

const selectedLat = ref<number>(props.defaultLat);
const selectedLng = ref<number>(props.defaultLng);
const isLocating = ref(false);
const isGeocoding = ref(false);
const errorMessage = ref<string | null>(null);
const geocodeError = ref<string | null>(null);
const mapLoadError = ref<string | null>(null);
const addressSearch = ref('');

function getCenter(): { lat: number; lng: number } {
    if (props.initialLat != null && props.initialLng != null) {
        return { lat: props.initialLat, lng: props.initialLng };
    }
    return { lat: props.defaultLat, lng: props.defaultLng };
}

function loadGoogleMapsScript(): Promise<void> {
    if (window.google?.maps) return Promise.resolve();
    return new Promise((resolve, reject) => {
        const existing = document.getElementById('google-maps-script');
        if (existing) {
            if (window.google?.maps) return resolve();
            existing.addEventListener('load', () => resolve());
            return;
        }
        const script = document.createElement('script');
        script.id = 'google-maps-script';
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(props.apiKey)}`;
        script.async = true;
        script.defer = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Google Maps'));
        document.head.appendChild(script);
    });
}

function getReferrerHelpMessage(): string {
    const origin = typeof window !== 'undefined' ? window.location.origin : '';
    const referrer = origin || 'http://127.0.0.1:8000';
    return `Add this URL to your API key's allowed HTTP referrers in Google Cloud Console: ${referrer}/* (and optionally http://localhost:8000/* for localhost).`;
}

function initMap() {
    if (!mapContainer.value || !props.apiKey) {
        if (!props.apiKey) mapLoadError.value = 'Google Maps API key is not configured.';
        return;
    }
    mapLoadError.value = null;
    const center = getCenter();
    selectedLat.value = center.lat;
    selectedLng.value = center.lng;

    try {
        map = new google.maps.Map(mapContainer.value, {
            center: { lat: center.lat, lng: center.lng },
            zoom: 15,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControl: true,
        });

        marker = new google.maps.Marker({
            position: { lat: center.lat, lng: center.lng },
            map,
            draggable: true,
            title: 'Delivery location',
        });

        marker.addListener('dragend', () => {
            const pos = marker!.getPosition();
            if (pos) {
                selectedLat.value = pos.lat();
                selectedLng.value = pos.lng();
            }
        });

        map.addListener('click', (e: google.maps.MapMouseEvent) => {
            const latLng = e.latLng;
            if (latLng) {
                selectedLat.value = latLng.lat();
                selectedLng.value = latLng.lng();
                marker?.setPosition(latLng);
            }
        });

        // Google sometimes shows RefererNotAllowedMapError via an overlay instead of throwing
        setTimeout(() => {
            if (!mapContainer.value) return;
            const text = mapContainer.value.innerText || '';
            if (/didn't load Google Maps correctly|RefererNotAllowedMapError|Oops! Something went wrong/i.test(text)) {
                mapLoadError.value =
                    'This site is not allowed to use your Google Maps key. ' + getReferrerHelpMessage();
                destroyMap();
            }
        }, 1500);
    } catch (err) {
        const msg = err instanceof Error ? err.message : String(err);
        const isReferrerError =
            /RefererNotAllowedMapError|referrer|not allowed/i.test(msg) || msg.includes('RefererNotAllowedMapError');
        if (isReferrerError) {
            mapLoadError.value =
                'This site is not allowed to use your Google Maps key. ' + getReferrerHelpMessage();
        } else {
            mapLoadError.value = 'Could not load the map. ' + (msg || 'Unknown error.');
        }
        destroyMap();
    }
}

function destroyMap() {
    if (marker) {
        marker.setMap(null);
        marker = null;
    }
    map = null;
}

function tryGeolocation() {
    if (!navigator.geolocation) {
        errorMessage.value = 'Geolocation is not supported.';
        return;
    }
    isLocating.value = true;
    errorMessage.value = null;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            selectedLat.value = lat;
            selectedLng.value = lng;
            const latLng = { lat, lng };
            marker?.setPosition(latLng);
            map?.panTo(latLng);
            map?.setZoom(15);
            isLocating.value = false;
        },
        () => {
            errorMessage.value = 'Unable to get your location. You can still move the pin on the map.';
            isLocating.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
}

/** Parse lat,lng from input (e.g. "-1.286389, 36.817223" or "-1.286389 36.817223") */
function tryParseCoordinates(input: string): { lat: number; lng: number } | null {
    const trimmed = input.trim();
    const parts = trimmed.split(/[\s,]+/).filter(Boolean);
    if (parts.length >= 2) {
        const lat = parseFloat(parts[0]);
        const lng = parseFloat(parts[1]);
        if (!Number.isNaN(lat) && !Number.isNaN(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            return { lat, lng };
        }
    }
    return null;
}

function locateFromInput() {
    const query = addressSearch.value?.trim();
    if (!query) {
        geocodeError.value = 'Please enter an address or coordinates.';
        return;
    }
    geocodeError.value = null;

    // Try parsing as coordinates first (e.g. -1.286389, 36.817223)
    const coords = tryParseCoordinates(query);
    if (coords) {
        selectedLat.value = coords.lat;
        selectedLng.value = coords.lng;
        marker?.setPosition(coords);
        map?.panTo(coords);
        map?.setZoom(15);
        return;
    }

    // Otherwise geocode as address
    if (!window.google?.maps?.Geocoder) {
        geocodeError.value = 'Geocoding is not available yet. Please wait for the map to load.';
        return;
    }
    isGeocoding.value = true;
    const geocoder = new google.maps.Geocoder();
    geocoder.geocode({ address: query }, (results, status) => {
        isGeocoding.value = false;
        if (status === 'OK' && results && results.length > 0) {
            const loc = results[0].geometry.location;
            const lat = loc.lat();
            const lng = loc.lng();
            selectedLat.value = lat;
            selectedLng.value = lng;
            marker?.setPosition({ lat, lng });
            map?.panTo({ lat, lng });
            map?.setZoom(15);
        } else {
            geocodeError.value = status === 'ZERO_RESULTS'
                ? 'No results found. Try a full address or coordinates (e.g. -1.29, 36.82).'
                : 'Could not find this location. Please try again.';
        }
    });
}

function open() {
    errorMessage.value = null;
    geocodeError.value = null;
    mapLoadError.value = null;
    if (props.useGeolocationOnOpen) {
        tryGeolocation();
    }
}

function close() {
    emit('update:modelValue', false);
}

function confirm() {
    emit('confirm', { lat: selectedLat.value, lng: selectedLng.value });
    emit('update:modelValue', false);
}

watch(
    () => props.modelValue,
    (visible) => {
        if (visible) {
            if (!props.apiKey) {
                mapLoadError.value = 'Google Maps API key is not configured.';
                return;
            }
            loadGoogleMapsScript()
                .then(() => {
                    nextTick(() => {
                        initMap();
                        open();
                    });
                })
                .catch(() => {
                    mapLoadError.value = 'Failed to load Google Maps.';
                });
        } else {
            destroyMap();
            mapLoadError.value = null;
        }
    },
);

onBeforeUnmount(() => {
    destroyMap();
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="modelValue"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="map-modal-title"
            @click.self="close"
        >
            <div
                class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-lg bg-white shadow-xl"
                @click.stop
            >
                <div class="flex shrink-0 items-center justify-between border-b px-4 py-3">
                    <h2 id="map-modal-title" class="text-lg font-semibold text-gray-800">{{ title }}</h2>
                    <button
                        type="button"
                        class="rounded p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                        aria-label="Close"
                        @click="close"
                    >
                        ×
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div class="flex flex-col gap-3">
                    <p class="text-sm text-gray-600">
                        Choose how to set your delivery location:
                    </p>

                    <!-- Option 1: Use current location -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="rounded-md border border-primary bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary hover:bg-primary/20 disabled:opacity-50"
                            :disabled="isLocating"
                            @click="tryGeolocation"
                        >
                            {{ isLocating ? 'Getting location…' : 'Use current location' }}
                        </button>
                        <span v-if="errorMessage" class="text-xs text-amber-600">{{ errorMessage }}</span>
                    </div>

                    <!-- Option 2: Type address or coordinates -->
                    <div class="flex flex-col gap-1">
                        <label for="address-input" class="text-xs font-medium text-gray-600">Or type your delivery address or paste coordinates</label>
                        <div class="flex gap-2">
                            <input
                                id="address-input"
                                v-model="addressSearch"
                                type="text"
                                placeholder="e.g. Westlands, Nairobi or -1.286389, 36.817223"
                                class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm placeholder:text-gray-400 focus:border-primary focus:ring focus:ring-primary/30"
                                @keydown.enter.prevent="locateFromInput"
                            />
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50"
                                :disabled="isGeocoding"
                                @click="locateFromInput"
                            >
                                {{ isGeocoding ? 'Locating…' : 'Show on map' }}
                            </button>
                        </div>
                        <span v-if="geocodeError" class="text-xs text-amber-600">{{ geocodeError }}</span>
                    </div>

                    <!-- Option 3: Click on map -->
                    <p class="text-xs text-gray-500">
                        Or click directly on the map to place the pin at your delivery location.
                    </p>
                    <div
                        v-if="mapLoadError"
                        class="flex h-[280px] min-h-[200px] w-full shrink-0 items-center justify-center rounded border border-gray-200 bg-gray-100 text-sm text-gray-600"
                    >
                        {{ mapLoadError }}
                    </div>
                    <div
                        v-else
                        ref="mapContainer"
                        class="h-[280px] min-h-[200px] w-full shrink-0 overflow-hidden rounded border border-gray-200 bg-gray-100"
                    />
                    <div v-if="!mapLoadError" class="flex justify-between text-xs text-gray-500">
                        <span>Coordinates: {{ selectedLat.toFixed(5) }}, {{ selectedLng.toFixed(5) }}</span>
                    </div>
                </div>
                </div>
                <div class="flex shrink-0 justify-end gap-2 border-t bg-white px-4 py-3">
                    <button
                        type="button"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="close"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90"
                        :disabled="!!mapLoadError"
                        @click="confirm"
                    >
                        Confirm location
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
