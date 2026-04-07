<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/UI/Modal.vue';
import TextInput from '@/Components/UI/TextInput.vue';
import FormGroup from '@/Components/UI/FormGroup.vue';

const props = defineProps({ categories: Array, toasts: Object });

// Category
const showCatModal = ref(false);
const editingCat = ref(null);
const catForm = useForm({ name: '' });

function openCreateCat() { editingCat.value = null; catForm.reset(); showCatModal.value = true; }
function openEditCat(cat) { editingCat.value = cat; catForm.name = cat.name; showCatModal.value = true; }

function submitCat() {
    if (editingCat.value) {
        catForm.put(route('settings.menu-categories.update', editingCat.value.id), {
            onSuccess: () => { showCatModal.value = false; props.toasts?.success('Kategoria u perditesua.'); },
        });
    } else {
        catForm.post(route('settings.menu-categories.store'), {
            onSuccess: () => { showCatModal.value = false; catForm.reset(); props.toasts?.success('Kategoria u shtua.'); },
        });
    }
}

function deleteCat(cat) {
    if (!confirm(`Fshi kategorine "${cat.name}"?`)) return;
    router.delete(route('settings.menu-categories.destroy', cat.id), {
        preserveScroll: true,
        onSuccess: () => props.toasts?.success('Kategoria u fshi.'),
        onError: () => props.toasts?.error('Ka artikuj brenda — fshij ata fillimisht.'),
    });
}

// Item
const showItemModal = ref(false);
const editingItem = ref(null);
const itemForm = useForm({ menu_category_id: '', name: '', price: '' });

function openCreateItem(catId) {
    editingItem.value = null;
    itemForm.reset();
    itemForm.menu_category_id = catId;
    showItemModal.value = true;
}

function openEditItem(item) {
    editingItem.value = item;
    itemForm.name = item.name;
    itemForm.price = item.price;
    itemForm.menu_category_id = item.menu_category_id;
    showItemModal.value = true;
}

function submitItem() {
    if (editingItem.value) {
        itemForm.put(route('settings.menu-items.update', editingItem.value.id), {
            onSuccess: () => { showItemModal.value = false; props.toasts?.success('Artikulli u perditesua.'); },
        });
    } else {
        itemForm.post(route('settings.menu-items.store'), {
            onSuccess: () => { showItemModal.value = false; itemForm.reset(); props.toasts?.success('Artikulli u shtua.'); },
        });
    }
}

function toggleItem(item) {
    router.patch(route('settings.menu-items.toggle', item.id), {}, {
        preserveScroll: true,
        onSuccess: () => props.toasts?.info(`${item.name}: ${item.is_available ? 'jo disponueshem' : 'disponueshem'}`),
    });
}

function deleteItem(item) {
    if (!confirm(`Fshi "${item.name}"?`)) return;
    router.delete(route('settings.menu-items.destroy', item.id), {
        preserveScroll: true,
        onSuccess: () => props.toasts?.success('Artikulli u fshi.'),
    });
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-h4 text-primary-900">Menu POS</h3>
            <Button size="sm" variant="primary" @click="openCreateCat">+ Kategori e re</Button>
        </div>

        <Card v-for="cat in categories" :key="cat.id">
            <template #header>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h4 class="text-label text-primary-900">{{ cat.name }}</h4>
                        <Badge variant="neutral" size="sm">{{ cat.items?.length || 0 }} artikuj</Badge>
                    </div>
                    <div class="flex gap-1.5">
                        <Button size="sm" variant="ghost" @click="openCreateItem(cat.id)">+ Artikull</Button>
                        <Button size="sm" variant="ghost" @click="openEditCat(cat)">Edito</Button>
                        <Button size="sm" variant="ghost" class="text-error-600" @click="deleteCat(cat)">Fshi</Button>
                    </div>
                </div>
            </template>

            <div v-if="cat.items?.length" class="divide-y divide-neutral-100 -my-1">
                <div v-for="item in cat.items" :key="item.id" class="flex items-center justify-between py-2">
                    <div class="flex items-center gap-3">
                        <span class="text-body-sm text-primary-900">{{ item.name }}</span>
                        <span class="text-body-sm text-accent-600 font-medium">€{{ item.price }}</span>
                        <Badge v-if="!item.is_available" variant="error" size="sm">Jo disponueshem</Badge>
                    </div>
                    <div class="flex gap-1">
                        <Button size="sm" variant="ghost" @click="toggleItem(item)">
                            {{ item.is_available ? 'Caktivizo' : 'Aktivizo' }}
                        </Button>
                        <Button size="sm" variant="ghost" @click="openEditItem(item)">Edito</Button>
                        <Button size="sm" variant="ghost" class="text-error-600" @click="deleteItem(item)">Fshi</Button>
                    </div>
                </div>
            </div>
            <div v-else class="py-4 text-center text-small text-neutral-400">Asnje artikull ne kete kategori.</div>
        </Card>

        <div v-if="!categories?.length" class="py-8 text-center text-body-sm text-neutral-500">Nuk ka kategori.</div>
    </div>

    <!-- Category Modal -->
    <Modal :show="showCatModal" :title="editingCat ? 'Edito kategorine' : 'Kategori e re'" max-width="sm" @close="showCatModal = false">
        <FormGroup label="Emri" :error="catForm.errors.name" required>
            <TextInput v-model="catForm.name" placeholder="psh. Pije te ftohta" :error="catForm.errors.name" />
        </FormGroup>
        <template #footer>
            <Button variant="outline" @click="showCatModal = false">Anulo</Button>
            <Button variant="primary" :loading="catForm.processing" @click="submitCat">{{ editingCat ? 'Ruaj' : 'Shto' }}</Button>
        </template>
    </Modal>

    <!-- Item Modal -->
    <Modal :show="showItemModal" :title="editingItem ? 'Edito artikullin' : 'Artikull i ri'" max-width="sm" @close="showItemModal = false">
        <div class="space-y-4">
            <FormGroup label="Emri" :error="itemForm.errors.name" required>
                <TextInput v-model="itemForm.name" placeholder="psh. Mojito" :error="itemForm.errors.name" />
            </FormGroup>
            <FormGroup label="Cmimi (€)" :error="itemForm.errors.price" required>
                <TextInput type="number" v-model="itemForm.price" min="0.01" step="0.01" :error="itemForm.errors.price" />
            </FormGroup>
        </div>
        <template #footer>
            <Button variant="outline" @click="showItemModal = false">Anulo</Button>
            <Button variant="primary" :loading="itemForm.processing" @click="submitItem">{{ editingItem ? 'Ruaj' : 'Shto' }}</Button>
        </template>
    </Modal>
</template>
