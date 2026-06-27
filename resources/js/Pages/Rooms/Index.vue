<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/UI/Modal.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import Select from '@/Components/UI/Select.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';

const props = defineProps({
    rooms: Object,
    roomTypes: Array,
    filters: Object,
    stats: Object,
});

const toasts = ref(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedRoom = ref(null);
const viewMode = ref('grid');

const page = usePage();
const userPerms = computed(() => page.props.auth.user?.permissions || []);
const canCreate = computed(() => userPerms.value.includes('create_rooms'));
const canUpdate = computed(() => userPerms.value.includes('update_rooms'));
const canDelete = computed(() => userPerms.value.includes('delete_rooms'));

const roomTypeOptions = props.roomTypes.map((t) => ({ value: t.id, label: `${t.name} (€${t.base_price})` }));
const statusOptions = [
    { value: 'available', label: 'E lire' },
    { value: 'occupied', label: 'E zene' },
    { value: 'cleaning', label: 'Pastrim' },
    { value: 'maintenance', label: 'Mirembajtje' },
];
const floorOptions = [1, 2, 3, 4, 5].map((f) => ({ value: f, label: `Kati ${f}` }));

const statusBadge = {
    available: { variant: 'success', label: 'E lire' },
    occupied: { variant: 'info', label: 'E zene' },
    cleaning: { variant: 'warning', label: 'Pastrim' },
    maintenance: { variant: 'error', label: 'Mirembajtje' },
};

// Hotel-standard colour language for the room grid.
const statusStyle = {
    available:   { bar: 'border-l-success-500', tint: 'bg-success-50', text: 'text-success-700', dot: 'bg-success-500', num: 'text-success-900' },
    occupied:    { bar: 'border-l-info-500',    tint: 'bg-info-50',    text: 'text-info-700',    dot: 'bg-info-500',    num: 'text-info-900' },
    cleaning:    { bar: 'border-l-warning-500', tint: 'bg-warning-50', text: 'text-warning-700', dot: 'bg-warning-500', num: 'text-warning-900' },
    maintenance: { bar: 'border-l-error-500',   tint: 'bg-error-50',   text: 'text-error-700',   dot: 'bg-error-500',   num: 'text-error-900' },
};
const statColor = {
    total: 'text-primary-900',
    available: 'text-success-600',
    occupied: 'text-info-600',
    cleaning: 'text-warning-600',
    maintenance: 'text-error-600',
};

const roomsByFloor = computed(() => {
    const groups = {};
    for (const room of props.rooms.data || []) {
        (groups[room.floor] ??= []).push(room);
    }
    return Object.keys(groups)
        .sort((a, b) => Number(a) - Number(b))
        .map((floor) => ({ floor, rooms: groups[floor] }));
});

const createForm = useForm({ room_type_id: '', room_number: '', floor: '', status: 'available', notes: '' });
const editForm = useForm({ room_type_id: '', room_number: '', floor: '', status: '', notes: '' });

const filterStatus = ref(props.filters?.status || '');
const filterFloor = ref(props.filters?.floor || '');
const filterType = ref(props.filters?.room_type_id || '');

function applyFilters() {
    const params = {};
    if (filterStatus.value) params.status = filterStatus.value;
    if (filterFloor.value) params.floor = filterFloor.value;
    if (filterType.value) params.room_type_id = filterType.value;
    router.get(route('rooms.index'), params, { preserveState: true });
}
function clearFilters() {
    filterStatus.value = '';
    filterFloor.value = '';
    filterType.value = '';
    router.get(route('rooms.index'), {}, { preserveState: true });
}
function openEdit(room) {
    selectedRoom.value = room;
    editForm.room_type_id = room.room_type_id;
    editForm.room_number = room.room_number;
    editForm.floor = room.floor;
    editForm.status = room.status;
    editForm.notes = room.notes || '';
    showEditModal.value = true;
}
function openDelete(room) {
    selectedRoom.value = room;
    showDeleteModal.value = true;
}
function submitCreate() {
    createForm.post(route('rooms.store'), {
        onSuccess: () => { showCreateModal.value = false; createForm.reset(); toasts.value?.success('Dhoma u shtua.'); },
    });
}
function submitEdit() {
    editForm.put(route('rooms.update', selectedRoom.value.id), {
        onSuccess: () => { showEditModal.value = false; toasts.value?.success('Dhoma u perditesua.'); },
    });
}
function submitDelete() {
    router.delete(route('rooms.destroy', selectedRoom.value.id), {
        onSuccess: () => { showDeleteModal.value = false; toasts.value?.success('Dhoma u fshi.'); },
    });
}
function quickStatus(room, status) {
    router.patch(route('rooms.status', room.id), { status }, {
        preserveScroll: true,
        onSuccess: () => toasts.value?.success(`Dhoma ${room.room_number}: ${statusBadge[status].label}`),
    });
}
</script>

<template>
    <AppLayout>
        <PageHeader
            title="Dhomat"
            :breadcrumbs="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Dhomat' }]"
        >
            <template #actions>
                <!-- View toggle -->
                <div class="inline-flex rounded-lg border border-neutral-200 bg-white p-0.5">
                    <button
                        :class="['px-3 py-1.5 rounded-md text-body-sm font-medium transition-colors', viewMode === 'grid' ? 'bg-primary-900 text-white' : 'text-neutral-500 hover:text-neutral-800']"
                        @click="viewMode = 'grid'"
                    >Rrjete</button>
                    <button
                        :class="['px-3 py-1.5 rounded-md text-body-sm font-medium transition-colors', viewMode === 'table' ? 'bg-primary-900 text-white' : 'text-neutral-500 hover:text-neutral-800']"
                        @click="viewMode = 'table'"
                    >Tabele</button>
                </div>
                <Button v-if="canCreate" variant="primary" @click="showCreateModal = true">+ Shto dhome</Button>
            </template>
        </PageHeader>

        <!-- Stats cards -->
        <div class="mt-6 grid grid-cols-2 sm:grid-cols-5 gap-3">
            <Card v-for="(count, key) in stats" :key="key">
                <div class="text-center">
                    <p :class="['text-h3', statColor[key] || 'text-primary-900']">{{ count }}</p>
                    <p class="text-tiny text-neutral-500 uppercase tracking-wider mt-1">
                        {{ key === 'total' ? 'Gjithsej' : statusBadge[key]?.label || key }}
                    </p>
                </div>
            </Card>
        </div>

        <!-- Filters -->
        <div class="mt-6 flex flex-wrap items-end gap-3">
            <div class="w-40">
                <Select v-model="filterStatus" :options="statusOptions" placeholder="Statusi..." @change="applyFilters" />
            </div>
            <div class="w-36">
                <Select v-model="filterFloor" :options="floorOptions" placeholder="Kati..." @change="applyFilters" />
            </div>
            <div class="w-48">
                <Select v-model="filterType" :options="roomTypeOptions" placeholder="Tipi..." @change="applyFilters" />
            </div>
            <Button v-if="filterStatus || filterFloor || filterType" variant="ghost" size="sm" @click="clearFilters">
                Pastro filtrat
            </Button>
        </div>

        <!-- GRID view: color-coded cards grouped by floor -->
        <div v-if="viewMode === 'grid'" class="mt-6 space-y-8">
            <section v-for="group in roomsByFloor" :key="group.floor">
                <div class="flex items-center gap-3 mb-3">
                    <h3 class="text-label text-neutral-600 uppercase tracking-wider">Kati {{ group.floor }}</h3>
                    <span class="h-px flex-1 bg-neutral-200"></span>
                    <span class="text-tiny text-neutral-400">{{ group.rooms.length }} dhoma</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
                    <div
                        v-for="room in group.rooms"
                        :key="room.id"
                        :class="['group relative rounded-lg border border-neutral-200 border-l-4 shadow-card hover:shadow-md transition-shadow duration-150 p-3.5', statusStyle[room.status].bar, statusStyle[room.status].tint]"
                    >
                        <div class="flex items-start justify-between">
                            <p :class="['text-h3 leading-none font-semibold', statusStyle[room.status].num]">{{ room.room_number }}</p>
                            <span class="h-2.5 w-2.5 rounded-full mt-1" :class="statusStyle[room.status].dot"></span>
                        </div>
                        <p class="text-small text-neutral-500 mt-1.5 truncate">{{ room.room_type?.name }}</p>
                        <p class="text-tiny font-medium mt-0.5" :class="statusStyle[room.status].text">{{ statusBadge[room.status]?.label }}</p>
                        <p v-if="room.room_type" class="text-small text-accent-600 mt-1.5">€{{ room.room_type.base_price }}/nate</p>

                        <!-- Quick status pills -->
                        <div v-if="canUpdate" class="mt-3 flex flex-wrap gap-1">
                            <button
                                v-for="opt in statusOptions"
                                :key="opt.value"
                                :disabled="room.status === opt.value"
                                :title="opt.label"
                                :class="[
                                    'h-5 w-5 rounded-full border transition-transform hover:scale-110 disabled:opacity-100 disabled:cursor-default',
                                    statusStyle[opt.value].dot,
                                    room.status === opt.value ? 'ring-2 ring-offset-1 ring-neutral-300 border-white' : 'border-white/70 opacity-50 hover:opacity-100',
                                ]"
                                @click="quickStatus(room, opt.value)"
                            />
                        </div>

                        <!-- Hover actions -->
                        <div v-if="canUpdate || canDelete" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                            <button v-if="canUpdate" class="h-6 w-6 grid place-items-center rounded-md bg-white/80 text-neutral-500 hover:text-neutral-900 shadow-sm" title="Edito" @click="openEdit(room)">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path d="M2.695 14.762l-1.262 3.155a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.886L17.5 5.501a2.121 2.121 0 00-3-3L3.58 13.419a4 4 0 00-.885 1.343z"/></svg>
                            </button>
                            <button v-if="canDelete" class="h-6 w-6 grid place-items-center rounded-md bg-white/80 text-error-500 hover:text-error-700 shadow-sm" title="Fshi" @click="openDelete(room)">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- TABLE view: dense list -->
        <div v-else class="mt-6">
            <Card :padding="false">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-neutral-200">
                        <thead class="bg-neutral-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Dhoma</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Tipi</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Kati</th>
                                <th class="px-5 py-3 text-left text-label text-neutral-600">Statusi</th>
                                <th class="px-5 py-3 text-right text-label text-neutral-600">Cmimi</th>
                                <th class="px-5 py-3 text-right text-label text-neutral-600">Veprime</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr v-for="room in rooms.data" :key="room.id" class="hover:bg-neutral-50">
                                <td class="px-5 py-3 text-body-sm text-primary-900 font-medium">{{ room.room_number }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-600">{{ room.room_type?.name }}</td>
                                <td class="px-5 py-3 text-body-sm text-neutral-600">Kati {{ room.floor }}</td>
                                <td class="px-5 py-3"><Badge :variant="statusBadge[room.status]?.variant" dot>{{ statusBadge[room.status]?.label }}</Badge></td>
                                <td class="px-5 py-3 text-right text-body-sm text-accent-600">€{{ room.room_type?.base_price }}</td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Button v-if="canUpdate" size="sm" variant="ghost" @click="openEdit(room)">Edito</Button>
                                        <Button v-if="canDelete" size="sm" variant="ghost" class="text-error-600" @click="openDelete(room)">Fshi</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

        <!-- Empty state -->
        <div v-if="!rooms.data?.length" class="mt-6">
            <Card>
                <div class="text-center py-12">
                    <div class="h-12 w-12 rounded-full bg-neutral-100 grid place-items-center mx-auto mb-3">
                        <svg class="h-6 w-6 text-neutral-400" viewBox="0 0 20 20" fill="currentColor"><path d="M1 11.27V2.757C1 1.786 1.786 1 2.757 1h14.486C18.214 1 19 1.786 19 2.757v8.513a2.27 2.27 0 01-.632 1.573L12.932 18.5a1.27 1.27 0 01-1.864 0L5.632 12.843A2.27 2.27 0 011 11.27z"/></svg>
                    </div>
                    <p class="text-body-sm text-neutral-500">Nuk ka dhoma qe perputhen me filtrat.</p>
                    <Button v-if="canCreate" variant="outline" size="sm" class="mt-3" @click="showCreateModal = true">+ Shto dhome</Button>
                </div>
            </Card>
        </div>

        <!-- Create Modal -->
        <Modal :show="showCreateModal" title="Shto dhome te re" @close="showCreateModal = false">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <FormGroup label="Numri i dhomes" :error="createForm.errors.room_number" required>
                    <TextInput v-model="createForm.room_number" placeholder="psh. 106" :error="createForm.errors.room_number" />
                </FormGroup>
                <FormGroup label="Tipi" :error="createForm.errors.room_type_id" required>
                    <Select v-model="createForm.room_type_id" :options="roomTypeOptions" :error="createForm.errors.room_type_id" />
                </FormGroup>
                <FormGroup label="Kati" :error="createForm.errors.floor" required>
                    <Select v-model="createForm.floor" :options="floorOptions" :error="createForm.errors.floor" />
                </FormGroup>
                <FormGroup label="Shenime">
                    <Textarea v-model="createForm.notes" placeholder="Shenime opsionale..." :rows="2" />
                </FormGroup>
            </form>
            <template #footer>
                <Button variant="outline" @click="showCreateModal = false">Anulo</Button>
                <Button variant="primary" :loading="createForm.processing" @click="submitCreate">Shto</Button>
            </template>
        </Modal>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" title="Edito dhomen" @close="showEditModal = false">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <FormGroup label="Numri i dhomes" :error="editForm.errors.room_number" required>
                    <TextInput v-model="editForm.room_number" :error="editForm.errors.room_number" />
                </FormGroup>
                <FormGroup label="Tipi" :error="editForm.errors.room_type_id" required>
                    <Select v-model="editForm.room_type_id" :options="roomTypeOptions" :error="editForm.errors.room_type_id" />
                </FormGroup>
                <div class="grid grid-cols-2 gap-4">
                    <FormGroup label="Kati" :error="editForm.errors.floor" required>
                        <Select v-model="editForm.floor" :options="floorOptions" :error="editForm.errors.floor" />
                    </FormGroup>
                    <FormGroup label="Statusi" :error="editForm.errors.status" required>
                        <Select v-model="editForm.status" :options="statusOptions" :error="editForm.errors.status" />
                    </FormGroup>
                </div>
                <FormGroup label="Shenime">
                    <Textarea v-model="editForm.notes" :rows="2" />
                </FormGroup>
            </form>
            <template #footer>
                <Button variant="outline" @click="showEditModal = false">Anulo</Button>
                <Button variant="primary" :loading="editForm.processing" @click="submitEdit">Ruaj</Button>
            </template>
        </Modal>

        <!-- Delete Confirmation -->
        <Modal :show="showDeleteModal" title="Fshi dhomen" max-width="sm" @close="showDeleteModal = false">
            <p class="text-body-sm text-neutral-600">
                Je i sigurt qe deshiron te fshish dhomen <strong>{{ selectedRoom?.room_number }}</strong>?
            </p>
            <template #footer>
                <Button variant="outline" @click="showDeleteModal = false">Anulo</Button>
                <Button variant="danger" @click="submitDelete">Fshi</Button>
            </template>
        </Modal>

        <ToastContainer ref="toasts" />
    </AppLayout>
</template>
