// async function reverseGeocode(lat: number, lng: number): Promise<Partial<Address>> {
//     try {
//         // Use Google Geocoding API or shipping.calculate-home-delivery endpoint
//         const res = await fetch(endpoint, {
//             headers: {
//                 Accept: 'application/json',
//                 // User-Agent for geocoding API requests
//                 'User-Agent': 'buyalot-checkout/1.0 (reverse-geocode)',
//             },
//         });
//         if (!res.ok) throw new Error('Reverse geocoding failed');
//         const data = await res.json();
//         const a = data?.address || {};
//         const road = a.road || a.pedestrian || a.path || a.footway || a.cycleway || a.residential || a.neighbourhood || a.suburb || a.hamlet;
//         const house = a.house_number || '';
//         const line1 = [house, road].filter(Boolean).join(' ').trim() || 'Pinned location';
//         const city = a.city || a.town || a.village || a.suburb || a.county || '';
//         const state = a.state || a.state_district || a.region || a.province || '';
//         const postal = a.postcode || '';
//         const countryCode = (a.country_code || 'KE').toUpperCase();
//         return {
//             address_line_1: line1,
//             city,
//             state,
//             postal_code: postal,
//             country: countryCode,
//         };
//     } catch {
//         return {};
//     }
// }

// function useCurrentLocation() {
//     if (!navigator.geolocation) {
//         alert('Geolocation is not supported by your browser.');
//         return;
//     }
//     navigator.geolocation.getCurrentPosition(
//         async (pos) => {
//             const { latitude, longitude, accuracy } = pos.coords as any;
//             // attach coordinates
//             newAddress.value.coordinates = { lat: latitude, lng: longitude, accuracy };

//             // fetch and autofill address fields
//             const details = await reverseGeocode(latitude, longitude);
//             if (details) {
//                 if (details.address_line_1) newAddress.value.address_line_1 = details.address_line_1 as string;
//                 if (details.city !== undefined) newAddress.value.city = (details.city as string) || newAddress.value.city || '';
//                 if (details.state !== undefined) newAddress.value.state = (details.state as string) || newAddress.value.state || '';
//                 if (details.postal_code !== undefined)
//                     newAddress.value.postal_code = (details.postal_code as string) || newAddress.value.postal_code || '';
//                 if (details.country !== undefined) newAddress.value.country = (details.country as string) || newAddress.value.country || 'KE';
//             }
//             if (!newAddress.value.label) newAddress.value.label = 'Current location';
//             if (!newAddress.value.address_line_1) newAddress.value.address_line_1 = 'Pinned location';
//         },
//         () => {
//             alert('Unable to retrieve your location.');
//         },
//         { enableHighAccuracy: true, timeout: 10000 },
//     );
// }
