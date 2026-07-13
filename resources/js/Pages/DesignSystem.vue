<script setup>
import { translate } from '@/i18n';
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
    { value: 'single', label: translate('admin.generated.k_11c118e48bae') },
    { value: 'double', label: translate('admin.generated.k_7859a686e762') },
    { value: 'suite', label: translate('admin.generated.k_41128b1d2ff2') },
    { value: 'family', label: translate('admin.generated.k_d61be3ea13a0') },
];

// Modal state
const showModal = ref(false);

// Toast ref
const toasts = ref(null);

// Table data
const tableColumns = [
    { key: 'id', label: '#', sortable: true, width: '60px' },
    { key: 'name', label: translate('admin.generated.k_585d9c5cdce0'), sortable: true },
    { key: 'room', label: translate('admin.generated.k_ee9afe500547'), sortable: true },
    { key: 'status', label: translate('admin.generated.k_d9b84f98b814'), sortable: true },
    { key: 'checkIn', label: translate('admin.generated.k_f6dcd1baf2c7'), sortable: true },
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
    confirmed: { variant: 'info', label: translate('admin.generated.k_d0c3feb9b4e1') },
    checked_in: { variant: 'success', label: translate('admin.generated.k_4ff1102ed0bb') },
    checked_out: { variant: 'neutral', label: translate('admin.generated.k_2773209ad549') },
    pending: { variant: 'warning', label: translate('admin.generated.k_44c890796e0f') },
    cancelled: { variant: 'error', label: translate('admin.generated.k_7dac79b7b747') },
};

// Active section for sidebar navigation
const activeSection = ref('colors');
const sections = [
    { id: 'colors', label: translate('admin.generated.k_78f7ca61279e') },
    { id: 'typography', label: translate('admin.generated.k_f47f63dd4290') },
    { id: 'buttons', label: translate('admin.generated.k_7dc83779376d') },
    { id: 'forms', label: translate('admin.generated.k_25a84fd79d48') },
    { id: 'table', label: translate('admin.generated.k_7f36f3d6e9b1') },
    { id: 'cards', label: translate('admin.generated.k_4612e72eb2a2') },
    { id: 'alerts', label: translate('admin.generated.k_3f6f80c0e9b1') },
    { id: 'badges', label: translate('admin.generated.k_e7c713fc2e28') },
    { id: 'modal', label: translate('admin.generated.k_5e55557c8655') },
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
            <h2 class="text-label text-neutral-500 uppercase tracking-wider mb-4 px-2">{{ $t('admin.generated.k_9199e0b2c9f6') }}</h2>
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
                <h1 class="text-h1 text-neutral-900">{{ $t('admin.generated.k_6039f72a5ca7') }}</h1>
                <p class="text-body text-neutral-500 mt-2">{{ $t('admin.generated.k_9c9fabe3e4f1') }}</p>
            </div>

            <!-- COLORS -->
            <section id="colors">
                <h2 class="text-h2 text-neutral-900 mb-6">{{ $t('admin.generated.k_4be2e428c439') }}</h2>
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
                <h2 class="text-h2 text-neutral-900 mb-6">{{ $t('admin.generated.k_382fdf8aee3b') }}</h2>
                <Card>
                    <div class="space-y-4">
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">{{ $t('admin.generated.k_a8d421311fe5') }}</span>
                            <p class="text-h1">{{ $t('admin.generated.k_be73f29880f9') }}</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">{{ $t('admin.generated.k_1eb776a653cd') }}</span>
                            <p class="text-h2">{{ $t('admin.generated.k_383b89be0023') }}</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">{{ $t('admin.generated.k_95850f4a8b17') }}</span>
                            <p class="text-h3">{{ $t('admin.generated.k_c3f604ea1cbb') }}</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">{{ $t('admin.generated.k_f4d29ad5e1e5') }}</span>
                            <p class="text-h4">{{ $t('admin.generated.k_ab3ca66b2bf3') }}</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">{{ $t('admin.generated.k_e4f94470bbc0') }}</span>
                            <p class="text-body">{{ $t('admin.generated.k_74858f3b8db5') }}</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">{{ $t('admin.generated.k_1bb708444561') }}</span>
                            <p class="text-body-sm">{{ $t('admin.generated.k_8c2e3c5fce20') }}</p>
                        </div>
                        <div class="flex items-baseline gap-4 border-b border-neutral-100 pb-3">
                            <span class="text-small text-neutral-400 w-20 shrink-0">{{ $t('admin.generated.k_0373bfb909cd') }}</span>
                            <p class="text-label">{{ $t('admin.generated.k_9e1690e7e3aa') }}</p>
                        </div>
                        <div class="flex items-baseline gap-4">
                            <span class="text-small text-neutral-400 w-20 shrink-0">{{ $t('admin.generated.k_e161b7326397') }}</span>
                            <p class="text-small">{{ $t('admin.generated.k_b9f6016643e8') }}</p>
                        </div>
                    </div>
                </Card>
            </section>

            <!-- BUTTONS -->
            <section id="buttons">
                <h2 class="text-h2 text-neutral-900 mb-6">{{ $t('admin.generated.k_3292beea3e19') }}</h2>
                <Card>
                    <div class="space-y-6">
                        <!-- Variants -->
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">{{ $t('admin.generated.k_779c0d1231d4') }}</h4>
                            <div class="flex flex-wrap items-center gap-3">
                                <Button variant="primary">{{ $t('admin.generated.k_cdc5500696a7') }}</Button>
                                <Button variant="secondary">{{ $t('admin.generated.k_4b7f02df2c40') }}</Button>
                                <Button variant="outline">{{ $t('admin.generated.k_21d8d8415b3c') }}</Button>
                                <Button variant="danger">{{ $t('admin.generated.k_feeef1085bfa') }}</Button>
                                <Button variant="ghost">{{ $t('admin.generated.k_227fe5dfda82') }}</Button>
                            </div>
                        </div>
                        <!-- Sizes -->
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">{{ $t('admin.generated.k_c06d8f4c614c') }}</h4>
                            <div class="flex flex-wrap items-center gap-3">
                                <Button size="sm">{{ $t('admin.generated.k_7c27617b18bc') }}</Button>
                                <Button size="md">{{ $t('admin.generated.k_922607608170') }}</Button>
                                <Button size="lg">{{ $t('admin.generated.k_674c7e746a97') }}</Button>
                            </div>
                        </div>
                        <!-- States -->
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">{{ $t('admin.generated.k_cbff25253b54') }}</h4>
                            <div class="flex flex-wrap items-center gap-3">
                                <Button :loading="true">{{ $t('admin.generated.k_d375d59eb4fa') }}</Button>
                                <Button :disabled="true">{{ $t('admin.generated.k_d2710fbc1893') }}</Button>
                                <Button variant="outline" :loading="true">{{ $t('admin.generated.k_1659cf0f5512') }}</Button>
                            </div>
                        </div>
                    </div>
                </Card>
            </section>

            <!-- FORMS -->
            <section id="forms">
                <h2 class="text-h2 text-neutral-900 mb-6">{{ $t('admin.generated.k_f25f25cb764e') }}</h2>
                <Card>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <FormGroup :label="$t('admin.generated.k_7c5b43b3f6ad')" required html-for="name">
                            <TextInput id="name" v-model="textValue" :placeholder="$t('admin.generated.k_47960e48e8e6')" />
                        </FormGroup>

                        <FormGroup :label="$t('admin.generated.k_95e3aeacc5b8')" html-for="email" error="Email nuk eshte i vlefshem">
                            <TextInput id="email" type="email" v-model="textValue" :placeholder="$t('admin.generated.k_ecd3eddce273')" error="invalid" />
                        </FormGroup>

                        <FormGroup :label="$t('admin.generated.k_90d62abbccf6')" html-for="room-type">
                            <Select id="room-type" v-model="selectValue" :options="selectOptions" />
                        </FormGroup>

                        <FormGroup :label="$t('admin.generated.k_003d5be1ad74')" html-for="password">
                            <TextInput id="password" type="password" v-model="textValue" placeholder="********" />
                        </FormGroup>

                        <div class="md:col-span-2">
                            <FormGroup :label="$t('admin.generated.k_7d9aa82fb50e')" html-for="notes">
                                <Textarea id="notes" v-model="textareaValue" :placeholder="$t('admin.generated.k_fe5caef36cc3')" :rows="3" />
                            </FormGroup>
                        </div>

                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">{{ $t('admin.generated.k_74bf65451967') }}</h4>
                            <div class="space-y-2">
                                <Checkbox v-model="checkboxValue" :label="$t('admin.generated.k_65d4bd1f0531')" />
                                <Checkbox :model-value="true" :label="$t('admin.generated.k_235832420774')" />
                                <Checkbox :model-value="false" :disabled="true" :label="$t('admin.generated.k_944ec9213c39')" />
                            </div>
                        </div>

                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">{{ $t('admin.generated.k_9aedb6776062') }}</h4>
                            <div class="space-y-2">
                                <Radio v-model="radioValue" value="option1" name="demo" :label="$t('admin.generated.k_80fe120a8003')" />
                                <Radio v-model="radioValue" value="option2" name="demo" :label="$t('admin.generated.k_27e713accd46')" />
                                <Radio v-model="radioValue" value="option3" name="demo" :label="$t('admin.generated.k_587df0cd79a2')" />
                            </div>
                        </div>

                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">{{ $t('admin.generated.k_d2710fbc1893') }}</h4>
                            <TextInput v-model="textValue" :disabled="true" :placeholder="$t('admin.generated.k_aeda6390de6d')" />
                        </div>
                    </div>
                </Card>
            </section>

            <!-- TABLE -->
            <section id="table">
                <h2 class="text-h2 text-neutral-900 mb-6">{{ $t('admin.generated.k_b1966c8e5aa7') }}</h2>
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
                <h2 class="text-h2 text-neutral-900 mb-6">{{ $t('admin.generated.k_55e426e5fe2b') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card>
                        <template #header>
                            <h4 class="text-h4">{{ $t('admin.generated.k_a52bbfea6cfb') }}</h4>
                        </template>
                        <p class="text-body-sm text-neutral-600">{{ $t('admin.generated.k_c9bca3d73af1') }}</p>
                        <div class="mt-3">
                            <Badge variant="success" dot>{{ $t('admin.generated.k_1dbc67ac5ee2') }}</Badge>
                        </div>
                        <template #footer>
                            <div class="flex justify-between items-center">
                                <span class="text-small text-neutral-500">{{ $t('admin.generated.k_6e853778690a') }}</span>
                                <Button size="sm" variant="outline">{{ $t('admin.generated.k_03ba03bcd1a3') }}</Button>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #header>
                            <h4 class="text-h4">{{ $t('admin.generated.k_d6f7cec284f2') }}</h4>
                        </template>
                        <p class="text-body-sm text-neutral-600">{{ $t('admin.generated.k_f8494d134c3b') }}</p>
                        <div class="mt-3">
                            <Badge variant="error" dot>{{ $t('admin.generated.k_229dc6c9548f') }}</Badge>
                        </div>
                        <template #footer>
                            <div class="flex justify-between items-center">
                                <span class="text-small text-neutral-500">{{ $t('admin.generated.k_fc9e3f1ed9a0') }}</span>
                                <Button size="sm" variant="outline" :disabled="true">{{ $t('admin.generated.k_229dc6c9548f') }}</Button>
                            </div>
                        </template>
                    </Card>

                    <Card>
                        <template #header>
                            <h4 class="text-h4">{{ $t('admin.generated.k_b217bd4be020') }}</h4>
                        </template>
                        <p class="text-body-sm text-neutral-600">{{ $t('admin.generated.k_b9a83743a5a5') }}</p>
                        <div class="mt-3">
                            <Badge variant="warning" dot>{{ $t('admin.generated.k_155527215816') }}</Badge>
                        </div>
                        <template #footer>
                            <div class="flex justify-between items-center">
                                <span class="text-small text-neutral-500">{{ $t('admin.generated.k_783cfca9da1f') }}</span>
                                <Button size="sm" variant="outline" :disabled="true">{{ $t('admin.generated.k_a0ddabed9a80') }}</Button>
                            </div>
                        </template>
                    </Card>
                </div>
            </section>

            <!-- ALERTS -->
            <section id="alerts">
                <h2 class="text-h2 text-neutral-900 mb-6">{{ $t('admin.generated.k_1f1eb5189d1e') }}</h2>
                <div class="space-y-3">
                    <Alert variant="success" :title="$t('admin.generated.k_a6883c5548f6')" :dismissible="true">{{ $t('admin.generated.k_0b2e3ae2f11f') }}</Alert>
                    <Alert variant="warning" :title="$t('admin.generated.k_8febe6c9baeb')">{{ $t('admin.generated.k_a9b97468c0a2') }}</Alert>
                    <Alert variant="error" :title="$t('admin.generated.k_69da03b8976e')">{{ $t('admin.generated.k_22b6733a9f51') }}</Alert>
                    <Alert variant="info" :title="$t('admin.generated.k_29ebc0bba031')">{{ $t('admin.generated.k_2c2a955c1dbd') }}</Alert>
                </div>
                <div class="mt-6 flex flex-wrap gap-3">
                    <Button variant="primary" size="sm" @click="toasts?.success('Veprimi u krye me sukses!')">{{ $t('admin.generated.k_3409a4c5cf15') }}</Button>
                    <Button variant="danger" size="sm" @click="toasts?.error('Dicka shkoi gabim!')">{{ $t('admin.generated.k_558eabc7b4c4') }}</Button>
                    <Button variant="outline" size="sm" @click="toasts?.warning('Kujdes: sesioni skadon per 5 min')">{{ $t('admin.generated.k_deb7b18d709a') }}</Button>
                    <Button variant="ghost" size="sm" @click="toasts?.info('3 rezervime te reja sot')">{{ $t('admin.generated.k_95140c762ac3') }}</Button>
                </div>
            </section>

            <!-- BADGES -->
            <section id="badges">
                <h2 class="text-h2 text-neutral-900 mb-6">{{ $t('admin.generated.k_0dd909175f78') }}</h2>
                <Card>
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">{{ $t('admin.generated.k_779c0d1231d4') }}</h4>
                            <div class="flex flex-wrap gap-2">
                                <Badge variant="success">{{ $t('admin.generated.k_b3e60a8727dd') }}</Badge>
                                <Badge variant="warning">{{ $t('admin.generated.k_f2401e2f900a') }}</Badge>
                                <Badge variant="error">{{ $t('admin.generated.k_0d504f73ac79') }}</Badge>
                                <Badge variant="info">{{ $t('admin.generated.k_4ad998b669e8') }}</Badge>
                                <Badge variant="neutral">{{ $t('admin.generated.k_3c27444a1e80') }}</Badge>
                                <Badge variant="accent">{{ $t('admin.generated.k_17041d0865cf') }}</Badge>
                                <Badge variant="dark">{{ $t('admin.generated.k_9a4d380aa110') }}</Badge>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">{{ $t('admin.generated.k_ad2ac2efe8bc') }}</h4>
                            <div class="flex flex-wrap gap-2">
                                <Badge variant="success" dot>{{ $t('admin.generated.k_a65972cab2da') }}</Badge>
                                <Badge variant="error" dot>{{ $t('admin.generated.k_bbb0691d4aa7') }}</Badge>
                                <Badge variant="warning" dot>{{ $t('admin.generated.k_ea40e27c1c0e') }}</Badge>
                                <Badge variant="neutral" dot>{{ $t('admin.generated.k_bbbb8e75cb1a') }}</Badge>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-label text-neutral-500 mb-3">{{ $t('admin.generated.k_c06d8f4c614c') }}</h4>
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge variant="accent" size="sm">{{ $t('admin.generated.k_7c27617b18bc') }}</Badge>
                                <Badge variant="accent" size="md">{{ $t('admin.generated.k_922607608170') }}</Badge>
                            </div>
                        </div>
                    </div>
                </Card>
            </section>

            <!-- MODAL -->
            <section id="modal">
                <h2 class="text-h2 text-neutral-900 mb-6">{{ $t('admin.generated.k_3c0a8cd5e396') }}</h2>
                <Button @click="showModal = true">{{ $t('admin.generated.k_6779636a7d92') }}</Button>

                <Modal :show="showModal" :title="$t('admin.generated.k_986d176ee0e9')" @close="showModal = false">
                    <div class="space-y-4">
                        <FormGroup :label="$t('admin.generated.k_aa80f00dd73e')" required>
                            <TextInput v-model="textValue" :placeholder="$t('admin.generated.k_620ecbfc872e')" />
                        </FormGroup>
                        <FormGroup :label="$t('admin.generated.k_90d62abbccf6')">
                            <Select v-model="selectValue" :options="selectOptions" />
                        </FormGroup>
                    </div>
                    <template #footer>
                        <Button variant="outline" @click="showModal = false">{{ $t('admin.generated.k_e1ca5b423c03') }}</Button>
                        <Button variant="primary" @click="showModal = false; toasts?.success('Rezervimi u ruajt!')">{{ $t('admin.generated.k_6b0a88b41167') }}</Button>
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
