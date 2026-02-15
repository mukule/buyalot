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
const errorMessage = ref<string | null>(null);
const mapLoadError = ref<string | null>(null);

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

function open() {
    errorMessage.value = null;
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
                class="relative flex max-h-[90vh] w-full max-w-2xl flex-col rounded-lg bg-white shadow-xl"
                @click.stop
            >
                <div class="flex items-center justify-between border-b px-4 py-3">
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
                <div class="flex flex-col gap-3 p-4">
                    <p class="text-sm text-gray-600">
                        Move the pin or click on the map to set your delivery location. You can also use your current
                        location.
                    </p>
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
                    <div
                        v-if="mapLoadError"
                        class="flex h-[400px] w-full items-center justify-center rounded border border-gray-200 bg-gray-100 text-sm text-gray-600"
                    >
                        {{ mapLoadError }}
                    </div>
                    <div
                        v-else
                        ref="mapContainer"
                        class="h-[400px] w-full overflow-hidden rounded border border-gray-200 bg-gray-100"
                        style="min-height: 400px"
                    />
                    <div v-if="!mapLoadError" class="flex justify-between text-xs text-gray-500">
                        <span>Coordinates: {{ selectedLat.toFixed(5) }}, {{ selectedLng.toFixed(5) }}</span>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t px-4 py-3">
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
