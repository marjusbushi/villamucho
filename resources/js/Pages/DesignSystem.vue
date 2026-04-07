<script setup>
import { ref } from 'vue';

// UI Components
import Button from '@/Components/UI/Button.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import Select from '@/Components/UI/Select.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import Radio from '@/Components/UI/Radio.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';
import DataTable from '@/Components/UI/DataTable.vue';
import Card from '@/Components/UI/Card.vue';
import Modal from '@/Components/UI/Modal.vue';
import Alert from '@/Components/UI/Alert.vue';
import Badge from '@/Components/UI/Badge.vue';
import ToastContainer from '@/Components/UI/ToastContainer.vue';

// Form state
const textValue = ref('');
const selectValue = ref('');
const checkboxValue = ref(false);
const radioValue = ref('option1');
const textareaValue = ref('');

const selectOptions = [
    { value: 'single', label: 'Single Room' },
    { value: 'double', label: 'Double Room' },
    { value: 'suite', label: 'Suite' },
    { value: 'family', label: 'Family Room' },
];

// Modal state
const showModal = ref(false);

// Toast ref
const toasts = ref(null);

// Table data
const tableColumns = [
    { key: 'id', label: '#', sortable: true, width: '60px' },
    { key: 'name', label: 'Emri', sortable: true },
    { key: 'room', label: 'Dhoma', sortable: true },
    { key: 'status', label: 'Statusi', sortable: true },
    { key: 'checkIn', label: 'Check-in', sortable: true },
];

const tableData = [
    { id: 1, name: 'Arben Hoxha', room: '101 — Double', status: 'confirmed', checkIn: '2026-04-08' },
    { id: 2, name: 'Elena Koci', room: '205 — Suite', status: 'checked_in', checkIn: '2026-04-07' },
    { id: 3, name: 'Marco Rossi', room: '103 — Single', status: 'pending', checkIn: '2026-04-09' },
    { id: 4, name: 'Anna Mueller', room: '302 — Family', status: 'checked_out', checkIn: '2026-04-05' },
    { id: 5, name: 'Besnik Dervishi', room: '104 — Double', status: 'cancelled', checkIn: '2026-04-10' },
    { id: 6, name: 'Sarah Johnson', room: '201 — Suite', status: 'confirmed', checkIn: '2026-04-11' },
    { id: 7, name: 'Dritan Leka', room: '106 — Single', status: 'checked_in', checkIn: '2026-04-06' },
    { id: 8, name: 'Maria Popescu', room: '301 — Family', status: 'pending', checkIn: '2026-04-12' },
];

const statusBadge = {
    confirmed: { variant: 'info', label: 'Konfirmuar' },
    checked_in: { variant: 'success', label: 'Brenda' },
    checked_out: { variant: 'neutral', label: 'Larguar' },
    pending: { variant: 'warning', label: 'Ne pritje' },
    cancelled: { variant: 'error', label: 'Anulluar' },
};

// Active section for sidebar navigation
const activeSection = ref('colors');
const sections = [
    { id: 'colors', label: 'Ngjyrat' },
    { id: 'typography', label: 'Tipografia' },
    { id: 'buttons', label: 'Butonat' },
    { id: 'forms', label: 'Format' },
    { id: 'table', label: 'Tabela' },
    { id: 'cards', label: 'Kartat' },
    { id: 'alerts', label: 'Alerts' },
    { id: 'badges', label: 'Badges' },
    { id: 'modal', label: 'Modal' },
];

function scrollTo(id) {
    activeSection.value = id;
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Color palette for display
const colorGroups = [
    { name: 'Primary (Charcoal)', key: 'primary', shades: [50,100,200,300,400,500,600,700,800,900,950] },
    { name: 'Accent (Emerald)', key: 'accent', shades: [50,100,200,300,400,500,600,700,800,900,950] },
    { name: 'Neutral', key: 'neutral', shades: [50,100,200,300,400,500,600,700,800,900,950] },
    { name: 'Success', key: 'success', shades: [50,100,200,300,400,500,600,700,800,900,950] },
    { name: 'Warning', key: 'warning', shades: [50,100,200,300,400,500,600,700,800,900,950] },
    { name: 'Error', key: 'error', shades: [50,100,200,300,400,500,600,700,800,900,950] },
    { name: 'Info', key: 'info', shades: [50,100,200,300,400,500,600,700,800,900,950] },
];
</script>

<template>
    <div class="flex min-h-screen bg-neutral-50">
        <!-- Sidebar navigation -->
        <aside class="hidden lg:block w-56 bg-white border-r border-neutral-200 sticky top-0 h-screen overflow-y-auto py-6 px-4">
            <h2 class="text-label text-neutral-500 uppercase tracking-wider mb-4 px-2">Komponentet</h2>
            <nav class="space-y-0.5">
                <button
                    v-for="section in sections"
                    :key="section.id"
                    :class="[
                        'block w-full text-left rounded-md px-3 py-2 text-body-sm transition-colors duration-150',
                        activeSection === section.id
                            ? 'bg-accent-50 text-accent-700 font-medium'
                            : 'text-neutral-600 hover:bg-neutral-100',
                    ]"
                    @click="scrollTo(section.id)"
                >
                    {{ section.label }}
                </button>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="flex-1 max-w-5xl mx-auto px-6 py-10 space-y-16">
            <div>
                <h1 class="text-h1 text-neutral-900">Design System</h1>
                <p class="text-body text-neutral-500 mt-2">Chanel Manager — komponentet baze te UI</p>
            </div>

            <!-- COLORS -->
            <section id="colors">
                <h2 class="text-h2 text-neutral-900 mb-6">Ngjyrat</h2>
                <div class="space-y-6">
                    <div v-for="group in colorGroups" :key="group.key">
                        <h3 class="text-label text-neutral-700 mb-2">{{ group.name }}</h3>
                        <div class="flex gap-1">
                            <div
                                v-for="shade in group.shades"
                                :key="shade"
                                class="flex-1"
                            >
                                <div
                                    :class="`bg-${group.key}-${shade} h-12 rounded-md`"
                                />
                                <p class="text-tiny text-neutral-500 mt-1 text-center">{{ shade }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TYPOGRAPHY -->
            <section id="typography">
                <h2 class="text-h2 text-neutral-900 mb-6">Tipografia</h2>
                <Card>
                    <div class="space-y-4">
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">h1</span>
                            <p class="text-h1">Menaxhimi i Hotelit</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">h2</span>
                            <p class="text-h2">Rezervimet e sotme</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">h3</span>
                            <p class="text-h3">Detajet e dhomes</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">h4</span>
                            <p class="text-h4">Informacioni i mysafirit</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">body</span>
                            <p class="text-body">Hoteli yne ofron eksperience unike per cdo mysafir qe na viziton.</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">body-sm</span>
                            <p class="text-body-sm">Rezervimi #1234 — Check-in: 8 Prill 2026</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">label</span>
                            <p class="text-label">Emri i mysafirit</p>
                        </div>
                        <div class="flex items-baseline gap-4">
                            <span class="text-small text-neutral-400 w-20 shrink-0">small</span>
                            <p class="text-small">Krijuar me 7 Prill 2026, 14:30</p>
                        </div>
                    </div>
                </Card>
            </section>

            <!-- BUTTONS -->
            <section id="buttons">
                <h2 class="text-h2 text-neutral-900 mb-6">Butonat</h2>
                <Card>
                    <div class="space-y-6">
                        <!-- Variants -->
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">Variantet</h4>
                            <div class="flex flex-wrap items-center gap-3">
                                <Button variant="primary">Primary</Button>
                                <Button variant="secondary">Secondary</Button>
                                <Button variant="outline">Outline</Button>
                                <Button variant="danger">Danger</Button>
                                <Button variant="ghost">Ghost</Button>
                            </div>
                        </div>
                        <!-- Sizes -->
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">Madhesit</h4>
                            <div class="flex flex-wrap items-center gap-3">
                                <Button size="sm">Small</Button>
                                <Button size="md">Medium</Button>
                                <Button size="lg">Large</Button>
                            </div>
                        </div>
                        <!-- States -->
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">States</h4>
                            <div class="flex flex-wrap items-center gap-3">
                                <Button :loading="true">Loading...</Button>
                                <Button :disabled="true">Disabled</Button>
                                <Button variant="outline" :loading="true">Saving</Button>
                            </div>
                        </div>
                    </div>
                </Card>
            </section>

            <!-- FORMS -->
            <section id="forms">
                <h2 class="text-h2 text-neutral-900 mb-6">Format</h2>
                <Card>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormGroup label="Emri i plote" required html-for="name">
                            <TextInput id="name" v-model="textValue" placeholder="Shkruaj emrin..." />
                        </FormGroup>

                        <FormGroup label="Email" html-for="email" error="Email nuk eshte i vlefshem">
                            <TextInput id="email" type="email" v-model="textValue" placeholder="email@example.com" error="invalid" />
                        </FormGroup>

                        <FormGroup label="Tipi i dhomes" html-for="room-type">
                            <Select id="room-type" v-model="selectValue" :options="selectOptions" />
                        </FormGroup>

                        <FormGroup label="Password" html-for="password">
                            <TextInput id="password" type="password" v-model="textValue" placeholder="********" />
                        </FormGroup>

                        <div class="md:col-span-2">
                            <FormGroup label="Shenime" html-for="notes">
                                <Textarea id="notes" v-model="textareaValue" placeholder="Shkruaj shenime per rezervimin..." :rows="3" />
                            </FormGroup>
                        </div>

                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">Checkbox</h4>
                            <div class="space-y-2">
                                <Checkbox v-model="checkboxValue" label="Pranon kushtet e sherbimit" />
                                <Checkbox :model-value="true" label="Merr njoftimet me email" />
                                <Checkbox :model-value="false" :disabled="true" label="Opsion i padisponueshem" />
                            </div>
                        </div>

                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">Radio</h4>
                            <div class="space-y-2">
                                <Radio v-model="radioValue" value="option1" name="demo" label="Pagese me karte" />
                                <Radio v-model="radioValue" value="option2" name="demo" label="Pagese cash" />
                                <Radio v-model="radioValue" value="option3" name="demo" label="Room charge" />
                            </div>
                        </div>

                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">Disabled</h4>
                            <TextInput v-model="textValue" :disabled="true" placeholder="Fushe e bllokuar" />
                        </div>
                    </div>
                </Card>
            </section>

            <!-- TABLE -->
            <section id="table">
                <h2 class="text-h2 text-neutral-900 mb-6">Tabela</h2>
                <DataTable
                    :columns="tableColumns"
                    :data="tableData"
                    :per-page="5"
                    :clickable-rows="true"
                    @row-click="(row) => toasts?.info(`Klikove: ${row.name}`)"
                >
                    <template #cell-status="{ value }">
                        <Badge :variant="statusBadge[value]?.variant" dot>
                            {{ statusBadge[value]?.label }}
                        </Badge>
                    </template>
                </DataTable>
            </section>

            <!-- CARDS -->
            <section id="cards">
                <h2 class="text-h2 text-neutral-900 mb-6">Kartat</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card>
                        <template #header>
                            <h4 class="text-h4">Dhoma 101</h4>
                        </template>
                        <p class="text-body-sm text-neutral-600">Double Room — Kati 1</p>
                        <div class="mt-3">
                            <Badge variant="success" dot>E lire</Badge>
                        </div>
                        <template #footer>
                            <div class="flex justify-between items-center">
                                <span class="text-small text-neutral-500">€80/nate</span>
                                <Button size="sm" variant="outline">Rezervo</Button>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #header>
                            <h4 class="text-h4">Dhoma 205</h4>
                        </template>
                        <p class="text-body-sm text-neutral-600">Suite — Kati 2</p>
                        <div class="mt-3">
                            <Badge variant="error" dot>E zene</Badge>
                        </div>
                        <template #footer>
                            <div class="flex justify-between items-center">
                                <span class="text-small text-neutral-500">€150/nate</span>
                                <Button size="sm" variant="outline" :disabled="true">E zene</Button>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #header>
                            <h4 class="text-h4">Dhoma 103</h4>
                        </template>
                        <p class="text-body-sm text-neutral-600">Single Room — Kati 1</p>
                        <div class="mt-3">
                            <Badge variant="warning" dot>Pastrimi</Badge>
                        </div>
                        <template #footer>
                            <div class="flex justify-between items-center">
                                <span class="text-small text-neutral-500">€50/nate</span>
                                <Button size="sm" variant="outline" :disabled="true">Ne pastrim</Button>
                            </div>
                        </template>
                    </Card>
                </div>
            </section>

            <!-- ALERTS -->
            <section id="alerts">
                <h2 class="text-h2 text-neutral-900 mb-6">Alerts & Toasts</h2>
                <div class="space-y-3">
                    <Alert variant="success" title="Sukses!" :dismissible="true">Rezervimi u konfirmua me sukses.</Alert>
                    <Alert variant="warning" title="Kujdes!">Dhoma 205 ka nevoje per pastrim para check-in.</Alert>
                    <Alert variant="error" title="Gabim!">Nuk mund te procesohet pagesa. Provoni perseri.</Alert>
                    <Alert variant="info" title="Info">Check-out per dhomen 301 eshte neser ne oren 11:00.</Alert>
                </div>
                <div class="mt-6 flex flex-wrap gap-3">
                    <Button variant="primary" size="sm" @click="toasts?.success('Veprimi u krye me sukses!')">Toast Sukses</Button>
                    <Button variant="danger" size="sm" @click="toasts?.error('Dicka shkoi gabim!')">Toast Error</Button>
                    <Button variant="outline" size="sm" @click="toasts?.warning('Kujdes: sesioni skadon per 5 min')">Toast Warning</Button>
                    <Button variant="ghost" size="sm" @click="toasts?.info('3 rezervime te reja sot')">Toast Info</Button>
                </div>
            </section>

            <!-- BADGES -->
            <section id="badges">
                <h2 class="text-h2 text-neutral-900 mb-6">Badges</h2>
                <Card>
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">Variantet</h4>
                            <div class="flex flex-wrap gap-2">
                                <Badge variant="success">Konfirmuar</Badge>
                                <Badge variant="warning">Ne pritje</Badge>
                                <Badge variant="error">Anulluar</Badge>
                                <Badge variant="info">Check-in sot</Badge>
                                <Badge variant="neutral">Arkivuar</Badge>
                                <Badge variant="accent">VIP</Badge>
                                <Badge variant="dark">Kthyes</Badge>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">Me dot indicator</h4>
                            <div class="flex flex-wrap gap-2">
                                <Badge variant="success" dot>Online</Badge>
                                <Badge variant="error" dot>Offline</Badge>
                                <Badge variant="warning" dot>Busy</Badge>
                                <Badge variant="neutral" dot>Away</Badge>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">Madhesit</h4>
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="accent" size="sm">Small</Badge>
                                <Badge variant="accent" size="md">Medium</Badge>
                            </div>
                        </div>
                    </div>
                </Card>
            </section>

            <!-- MODAL -->
            <section id="modal">
                <h2 class="text-h2 text-neutral-900 mb-6">Modal</h2>
                <Button @click="showModal = true">Hap Modalin</Button>

                <Modal :show="showModal" title="Konfirmo rezervimin" @close="showModal = false">
                    <div class="space-y-4">
                        <FormGroup label="Emri i mysafirit" required>
                            <TextInput v-model="textValue" placeholder="Emri i plote..." />
                        </FormGroup>
                        <FormGroup label="Tipi i dhomes">
                            <Select v-model="selectValue" :options="selectOptions" />
                        </FormGroup>
                    </div>
                    <template #footer>
                        <Button variant="outline" @click="showModal = false">Anulo</Button>
                        <Button variant="primary" @click="showModal = false; toasts?.success('Rezervimi u ruajt!')">Ruaj</Button>
                    </template>
                </Modal>
            </section>

            <!-- Footer spacing -->
            <div class="h-20" />
        </main>

        <!-- Toast container -->
        <ToastContainer ref="toasts" />
    </div>
</template>
