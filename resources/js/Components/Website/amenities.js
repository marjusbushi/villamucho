// Shared amenity → Lucide line-icon map for the public website (Ionian Calm).
// One source of truth used by Home.vue, Rooms.vue and the booking flow so the
// iconography never drifts between pages. Falls back to a generic check.
import {
    Wifi, Tv, Snowflake, ShowerHead, Bath, Sunrise, Wine,
    Waves, Coffee, BedDouble, Check,
} from 'lucide-vue-next';

const MAP = {
    'WiFi': Wifi,
    'TV': Tv,
    'TV 55"': Tv,
    'Aire kondicionuar': Snowflake,
    'Banjo private': ShowerHead,
    'Banjo luksoze': Bath,
    'Ballkon': Sunrise,
    'Minibar': Wine,
    'Pamje nga deti': Waves,
    'Makineri kafeje': Coffee,
    'Shtrat shtese': BedDouble,
};

export function amenityIcon(name) {
    return MAP[name] || Check;
}
