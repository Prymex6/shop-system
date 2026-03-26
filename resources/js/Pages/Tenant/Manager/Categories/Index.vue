<template>
  <ManagerLayout :title="t('common.categories')">
    <div class="space-y-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-900">{{ t('common.categories') }}</h1>
        <button
          @click="showForm = true"
          class="bg-blue-600 text-white px-4 py-2 rounded-md font-semibold hover:bg-blue-700 transition text-sm"
        >
          {{ t('manager.categories.index.new_category') }}
        </button>
      </div>

      <!-- Categories tree -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div v-for="category in categories" :key="category.id" class="border-b border-gray-50 last:border-0">
          <!-- Root category -->
          <div class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition">
            <div class="w-8 h-8 rounded-lg bg-gray-100 overflow-hidden flex-shrink-0">
              <img v-if="category.image" :src="'/storage/' + category.image" class="w-full h-full object-cover" />
              <span v-else class="w-full h-full flex items-center justify-center text-sm">📁</span>
            </div>
            <span class="flex-1 font-semibold text-gray-900">{{ category.name }}</span>
            <span class="text-xs text-gray-400">/{{ category.slug }}</span>
            <span
              class="text-xs px-2 py-0.5 rounded-full"
              :class="category.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
            >
              {{ category.is_active ? t('landlord.modifications.form.active') : 'Nieaktywna' }}
            </span>
            <button
              @click="editCategory(category)"
              class="text-xs text-blue-600 hover:text-blue-900 px-2 py-1 font-medium"
            >
              {{ t('common.edit') }}
            </button>
            <button
              @click="deleteCategory(category)"
              class="text-xs text-red-500 hover:text-red-700 px-2 py-1 font-medium"
            >
              {{ t('common.delete') }}
            </button>
          </div>
          <!-- Children -->
          <div
            v-for="child in category.children"
            :key="child.id"
            class="flex items-center gap-3 px-5 py-2 bg-gray-50/50 border-t border-gray-50 hover:bg-gray-50 transition"
          >
            <div class="w-1 h-4 bg-gray-200 ml-4 flex-shrink-0 rounded-full"></div>
            <span class="flex-1 text-gray-700 text-sm">{{ child.name }}</span>
            <span class="text-xs text-gray-400">/{{ child.slug }}</span>
            <span
              class="text-xs px-2 py-0.5 rounded-full"
              :class="child.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
            >
              {{ child.is_active ? t('landlord.modifications.form.active') : 'Nieaktywna' }}
            </span>
            <button
              @click="editCategory(child)"
              class="text-xs text-blue-600 hover:text-blue-900 px-2 py-1 font-medium"
            >
              {{ t('common.edit') }}
            </button>
            <button
              @click="deleteCategory(child)"
              class="text-xs text-red-500 hover:text-red-700 px-2 py-1 font-medium"
            >
              {{ t('common.delete') }}
            </button>
          </div>
        </div>
        <div v-if="!categories.length" class="text-center py-12 text-gray-400 text-sm">
          {{ t('manager.categories.index.no_categories_yet_add_the_first') }}
        </div>
      </div>

      <!-- Modal -->
      <div
        v-if="showForm"
        class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
        @click.self="closeForm"
      >
        <div class="bg-white shadow rounded-lg w-full max-w-lg p-6">
          <h2 class="text-lg font-bold text-gray-900 mb-5">
            {{ editing ? t('common.edit_category') : t('common.new_category') }}
          </h2>
          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.categories.index.parent_category')
              }}</label>
              <select v-model="form.parent_id" class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm">
                <option :value="null">{{ t('manager.categories.index.none_top_level_category') }}</option>
                <option v-for="cat in flatCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name_2') }}</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{
                t('manager.articles.form.slug_url')
              }}</label>
              <input
                v-model="form.slug"
                type="text"
                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm font-mono"
                placeholder="auto-generowany"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.description') }}</label>
              <textarea
                v-model="form.description"
                rows="2"
                class="w-full border border-gray-200 rounded-md px-3 py-2 text-sm resize-none"
              />
            </div>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="form.is_active" class="accent-indigo-600" />
              {{ t('landlord.modifications.form.active') }}
            </label>
            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="closeForm"
                class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 font-medium"
              >
                {{ t('common.cancel') }}
              </button>
              <button
                type="submit"
                class="bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition"
              >
                {{ editing ? t('common.save_changes') : t('common.add_category') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  categories: { type: Array, default: () => [] },
})

const showForm = ref(false)
const editing = ref(null)

const form = reactive({
  parent_id: null,
  name: '',
  slug: '',
  description: '',
  is_active: true,
})

const flatCategories = computed(() => props.categories.flatMap((c) => [c, ...(c.children ?? [])]))

function editCategory(category) {
  editing.value = category
  Object.assign(form, {
    parent_id: category.parent_id ?? null,
    name: category.name,
    slug: category.slug,
    description: category.description ?? '',
    is_active: category.is_active,
  })
  showForm.value = true
}

function closeForm() {
  showForm.value = false
  editing.value = null
  Object.assign(form, { parent_id: null, name: '', slug: '', description: '', is_active: true })
}

function submitForm() {
  if (editing.value) {
    router.put(route('tenant.manager.categories.update', editing.value.id), form, {
      onSuccess: closeForm,
    })
  } else {
    router.post(route('tenant.manager.categories.store'), form, {
      onSuccess: closeForm,
    })
  }
}

function deleteCategory(category) {
  if (!confirm(t('manager.categories.index.delete_the_category_a', { a: category.name }))) return
  router.delete(route('tenant.manager.categories.destroy', category.id))
}
</script>
