<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { CalendarX2 } from 'lucide-vue-next';
import WebsiteLayout from '@/Layouts/WebsiteLayout.vue';

const props = defineProps({
    roomTypes: Array,
    preselectedType: [String, Number],
    hotel: Object,
});

const step = ref(1);
const availableRooms = ref([]);
const selectedRoom = ref(null);
const loading = ref(false);

const searchForm = ref({
    check_in: '',
    check_out: '',
    room_type_id: props.preselectedType || '',
    adults: 1,
});

const guestForm = useForm({
    room_id: '',
    check_in: '',
    check_out: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    notes: '',
    adults: 1,
    website: '', // honeypot — must stay empty
});

const nights = ref(0);
const flashError = computed(() => usePage().props.flash?.error);

const typeOptions = props.roomTypes.map(t => ({ value: t.id, label: `${t.name} (€${t.base_price}/nate)` }));

async function checkAvailability() {
    loading.value = true;
    try {
        const response = await axios.post('/book/check', {
            check_in: searchForm.value.check_in,
            check_out: searchForm.value.check_out,
            room_type_id: searchForm.value.room_type_id || null,
        });
        availableRooms.value = response.data.rooms;
        nights.value = response.data.nights;
        step.value = 2;
    } catch (e) {
        alert(e.response?.data?.message || 'Gabim gjate kontrollit');
    }
    loading.value = false;
}

function selectRoom(room) {
    selectedRoom.value = room;
    guestForm.room_id = room.id;
    guestForm.check_in = searchForm.value.check_in;
    guestForm.check_out = searchForm.value.check_out;
    guestForm.adults = searchForm.value.adults;
    step.value = 3;
}

function submitBooking() {
    guestForm.post('/book', {
        onError: () => {},
    });
}

function goBack(toStep) {
    step.value = toStep;
}
</script>

<template>
    <Head title="Rezervo — Villa Mucho" />
    <WebsiteLayout>
        <section class="py-16">
            <div class="max-w-3xl mx-auto px-4 sm:px-6">
                <div class="text-center mb-10">
                    <h1 class="text-h1 text-primary-900">Rezervo Dhomen</h1>
                    <p class="text-body text-neutral-500 mt-2">Zgjidhni datat dhe dhomen qe deshironi</p>
                </div>

                <!-- Steps indicator -->
                <div class="flex items-center justify-center gap-2 mb-10">
                    <div v-for="s in 3" :key="s" :class="['flex items-center gap-2', s < 3 && 'flex-1']">
                        <div :class="['h-8 w-8 rounded-full flex items-center justify-center text-small font-medium shrink-0', step >= s ? 'bg-ink text-bone' : 'bg-limestone text-driftwood']">{{ s }}</div>
                        <span :class="['text-body-sm hidden sm:block', step >= s ? 'text-ink font-medium' : 'text-driftwood']">{{ s === 1 ? 'Datat' : s === 2 ? 'Dhoma' : 'Te dhenat' }}</span>
                        <div v-if="s < 3" :class="['flex-1 h-0.5 mx-2', step > s ? 'bg-ink/30' : 'bg-driftwood/20']" />
                    </div>
                </div>

                <!-- Flash error (e.g. room no longer available) -->
                <div v-if="flashError" class="mb-6 p-3 rounded-lg bg-error-50 border border-error-200 text-body-sm text-error-700">
                    {{ flashError }}
                </div>

                <!-- Step 1: Dates -->
                <div v-if="step === 1" class="bg-white rounded-2xl border border-neutral-100 p-6 sm:p-8">
                    <h2 class="text-h3 text-primary-900 mb-6">Zgjidhni datat</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-label text-neutral-700 mb-1.5">Check-in *</label>
                            <input type="date" v-model="searchForm.check_in" :min="new Date().toISOString().split('T')[0]" class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                        </div>
                        <div>
                            <label class="block text-label text-neutral-700 mb-1.5">Check-out *</label>
                            <input type="date" v-model="searchForm.check_out" :min="searchForm.check_in || new Date().toISOString().split('T')[0]" class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                        </div>
                        <div>
                            <label class="block text-label text-neutral-700 mb-1.5">Tipi i dhomes</label>
                            <select v-model="searchForm.room_type_id" class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30">
                                <option value="">Te gjitha tipet</option>
                                <option v-for="t in roomTypes" :key="t.id" :value="t.id">{{ t.name }} (€{{ t.base_price }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-label text-neutral-700 mb-1.5">Mysafire</label>
                            <input type="number" v-model="searchForm.adults" min="1" max="10" class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                        </div>
                    </div>
                    <button
                        :disabled="!searchForm.check_in || !searchForm.check_out || loading"
                        class="btn-reserve w-full mt-6"
                        @click="checkAvailability"
                    >
                        {{ loading ? 'Duke kontrolluar...' : 'Kontrollo Disponueshmerine' }}
                    </button>
                </div>

                <!-- Step 2: Select Room -->
                <div v-if="step === 2">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-h3 text-primary-900">Dhomat e lira</h2>
                        <button class="text-body-sm text-ionian hover:text-ionian-dark" @click="goBack(1)">← Ndrysho datat</button>
                    </div>
                    <p class="text-body-sm text-neutral-500 mb-6">{{ searchForm.check_in }} → {{ searchForm.check_out }} · {{ nights }} net</p>

                    <div v-if="availableRooms.length" class="space-y-3">
                        <button
                            v-for="room in availableRooms"
                            :key="room.id"
                            class="w-full bg-bone border border-driftwood/20 p-5 text-left hover:border-ionian/50 transition-colors"
                            @click="selectRoom(room)"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-h4 text-primary-900">Dhoma {{ room.room_number }}</h3>
                                    <p class="text-body-sm text-neutral-500">{{ room.room_type }} · Kati {{ room.floor }} · Max {{ room.max_occupancy }} persona</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-h4 text-brass">€{{ room.total_price }}</p>
                                    <p class="text-tiny text-neutral-400">{{ nights }} net × €{{ room.price_per_night }}</p>
                                </div>
                            </div>
                        </button>
                    </div>

                    <div v-else class="bg-bone border border-driftwood/20 p-10 text-center">
                        <CalendarX2 class="h-10 w-10 mx-auto mb-3 text-driftwood" :stroke-width="1.1" />
                        <p class="text-body text-ink/70">Nuk ka dhoma te lira per keto data.</p>
                        <button class="mt-3 text-body-sm text-ionian hover:text-ionian-dark" @click="goBack(1)">Provoni data te tjera</button>
                    </div>
                </div>

                <!-- Step 3: Guest Info -->
                <div v-if="step === 3" class="bg-white rounded-2xl border border-neutral-100 p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-h3 text-primary-900">Te dhenat tuaja</h2>
                        <button class="text-body-sm text-ionian hover:text-ionian-dark" @click="goBack(2)">← Ndrysho dhomen</button>
                    </div>

                    <!-- Summary -->
                    <div class="bg-neutral-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-label text-primary-900">Dhoma {{ selectedRoom?.room_number }} — {{ selectedRoom?.room_type }}</p>
                                <p class="text-body-sm text-neutral-500">{{ searchForm.check_in }} → {{ searchForm.check_out }} · {{ nights }} net</p>
                            </div>
                            <p class="text-h3 text-brass">€{{ selectedRoom?.total_price }}</p>
                        </div>
                    </div>

                    <form @submit.prevent="submitBooking" class="space-y-4">
                        <!-- Honeypot: hidden from humans, bots fill it -->
                        <input v-model="guestForm.website" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="absolute -left-[9999px] h-0 w-0 opacity-0" />
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-label text-neutral-700 mb-1.5">Emri *</label>
                                <input v-model="guestForm.first_name" type="text" placeholder="Emri" class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                                <p v-if="guestForm.errors.first_name" class="text-small text-error-600 mt-1">{{ guestForm.errors.first_name }}</p>
                            </div>
                            <div>
                                <label class="block text-label text-neutral-700 mb-1.5">Mbiemri *</label>
                                <input v-model="guestForm.last_name" type="text" placeholder="Mbiemri" class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                                <p v-if="guestForm.errors.last_name" class="text-small text-error-600 mt-1">{{ guestForm.errors.last_name }}</p>
                            </div>
                            <div>
                                <label class="block text-label text-neutral-700 mb-1.5">Email *</label>
                                <input v-model="guestForm.email" type="email" placeholder="email@example.com" class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                                <p v-if="guestForm.errors.email" class="text-small text-error-600 mt-1">{{ guestForm.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-label text-neutral-700 mb-1.5">Telefon *</label>
                                <input v-model="guestForm.phone" type="tel" placeholder="+355 69..." class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                                <p v-if="guestForm.errors.phone" class="text-small text-error-600 mt-1">{{ guestForm.errors.phone }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-label text-neutral-700 mb-1.5">Kerkesa speciale</label>
                            <textarea v-model="guestForm.notes" rows="3" placeholder="Shenime shtese..." class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-body-sm focus:border-ionian focus:ring-2 focus:ring-ionian/30" />
                        </div>
                        <button
                            type="submit"
                            :disabled="guestForm.processing"
                            class="btn-reserve w-full"
                        >
                            {{ guestForm.processing ? 'Duke derguar...' : `Konfirmo Rezervimin — €${selectedRoom?.total_price}` }}
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </WebsiteLayout>
</template>
