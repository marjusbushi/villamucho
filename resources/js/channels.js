// Booking channels (source of a reservation). Single source of truth for the
// "Burimi" badges + the staff reservation dropdown. IDs MUST match the backend
// Reservation::CHANNELS list and the channel-manager vocabulary (lowercase/dotted).
export const DIRECT_CHANNEL = { id: 'direct', label: 'Direkt', color: '#2E6E72' };

export const OTA_CHANNELS = [
    { id: 'booking.com', label: 'Booking.com', color: '#003B95' },
    { id: 'expedia', label: 'Expedia', color: '#00355F' },
    { id: 'airbnb', label: 'Airbnb', color: '#FF5A5F' },
    { id: 'agoda', label: 'Agoda', color: '#6D28D9' },
    { id: 'hotels.com', label: 'Hotels.com', color: '#D32F2F' },
    { id: 'vrbo', label: 'Vrbo', color: '#1E40AF' },
    { id: 'trip.com', label: 'Trip.com', color: '#287DFA' },
    { id: 'hostelworld', label: 'Hostelworld', color: '#F97316' },
    { id: 'google', label: 'Google', color: '#4285F4' },
    { id: 'tripadvisor', label: 'Tripadvisor', color: '#00AA6C' },
];

// Staff can choose a direct booking or the actual OTA source. The old
// `manual` value is display-only legacy data and is intentionally not selectable.
export const CHANNELS = [DIRECT_CHANNEL, ...OTA_CHANNELS];

const BY_ID = Object.fromEntries(CHANNELS.map((c) => [c.id, c]));

// Null/empty/legacy manual reservations are first-party bookings, so present
// them consistently as Direct without losing the individual OTA identities.
export function channelMeta(id) {
    if (!id || id === 'manual') return BY_ID.direct;
    return BY_ID[id] || { id, label: id, color: '#6B7280' };
}

// For <Select :options="channelOptions" />
export const channelOptions = CHANNELS.map((c) => ({ value: c.id, label: c.label }));
