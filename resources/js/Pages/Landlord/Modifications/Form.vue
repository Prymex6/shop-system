<template>
  <LandlordLayout :title="modification ? t('common.edit_modification') : t('common.new_modification')">
    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
          <Link :href="route('landlord.modifications.index')" class="text-sm text-blue-600 hover:text-blue-800">
            {{ t('common.back_to_the_list') }}
          </Link>
          <h1 class="text-3xl font-bold text-gray-900 mt-2">
            {{ modification ? t('common.edit_modification') : t('common.new_modification') }}
          </h1>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Basic info -->
          <div class="bg-white shadow rounded-lg p-6 space-y-5">
            <h2 class="text-lg font-semibold text-gray-900">{{ t('landlord.modifications.form.basic_details') }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.name_2') }}</label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  :placeholder="t('landlord.modifications.form.e_g_sort_the_menu_by')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                />
                <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name }}</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('landlord.modifications.form.code_unique')
                }}</label>
                <input
                  v-model="form.code"
                  type="text"
                  required
                  :placeholder="t('landlord.modifications.form.e_g_menu_sort_by_name')"
                  :disabled="!!modification"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100 font-mono text-sm"
                />
                <p class="mt-1 text-xs text-gray-400">
                  {{ t('landlord.modifications.form.letters_digits_and_underscores_only_it') }}
                </p>
                <p v-if="errors.code" class="mt-1 text-xs text-red-600">{{ errors.code }}</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('common.description') }}</label>
              <textarea
                v-model="form.description"
                rows="2"
                :placeholder="t('landlord.modifications.form.what_does_this_modification_do')"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              ></textarea>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('landlord.modifications.form.version')
                }}</label>
                <input
                  v-model="form.version"
                  type="text"
                  placeholder="1.0.0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{
                  t('landlord.modifications.form.author')
                }}</label>
                <input
                  v-model="form.author"
                  type="text"
                  :placeholder="t('landlord.modifications.form.e_g_john_smith')"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div class="flex items-end">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input v-model="form.status" type="checkbox" class="h-4 w-4 text-blue-600 rounded" />
                  <span class="text-sm font-medium text-gray-700">{{ t('landlord.modifications.form.active') }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Tenant assignment -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ t('landlord.modifications.form.applies_to_the_shop') }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                {{ t('landlord.modifications.form.tick_particular_shops_or_leave_everything') }}
              </p>
            </div>

            <div class="flex items-center gap-3 mb-3">
              <button
                type="button"
                @click="form.tenant_ids = null"
                :class="
                  form.tenant_ids === null ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                "
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
              >
                {{ t('common.all_shops') }}
              </button>
              <button
                type="button"
                @click="form.tenant_ids = []"
                :class="
                  Array.isArray(form.tenant_ids)
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                "
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
              >
                {{ t('landlord.modifications.form.selected_shops') }}
              </button>
            </div>

            <div v-if="Array.isArray(form.tenant_ids)" class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <label
                v-for="tenant in tenants"
                :key="tenant.id"
                class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                :class="form.tenant_ids.includes(tenant.id) ? 'border-blue-300 bg-blue-50' : 'border-gray-200'"
              >
                <input
                  v-model="form.tenant_ids"
                  type="checkbox"
                  :value="tenant.id"
                  class="h-4 w-4 text-blue-600 rounded"
                />
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ tenant.name }}</div>
                  <div class="text-xs text-gray-400 font-mono">{{ tenant.id }}</div>
                </div>
                <span
                  :class="tenant.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                  class="ml-auto px-2 py-0.5 rounded-full text-xs"
                  >{{ tenant.status }}</span
                >
              </label>
            </div>
            <div v-else class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-sm text-blue-700">
              {{ t('landlord.modifications.form.the_modification_is_applied_to_every') }}
            </div>
          </div>

          <!-- Rules JSON editor -->
          <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">
                {{ t('landlord.modifications.form.modification_rules_json') }}
              </h2>
              <p class="text-sm text-gray-500 mt-1">
                Format identyczny jak OpenCart OCMod – lista patches z operacjami find/replace na plikach PHP
              </p>
            </div>

            <p v-if="errors.rules_json" class="text-sm text-red-600 bg-red-50 border border-red-200 rounded p-3">
              {{ errors.rules_json }}
            </p>

            <textarea
              v-model="form.rules_json"
              rows="20"
              spellcheck="false"
              :placeholder="t('landlord.modifications.form.rules_example')"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg font-mono text-xs leading-relaxed focus:ring-2 focus:ring-blue-500 bg-gray-50"
            ></textarea>

            <!-- Example template -->
            <div class="bg-gray-900 text-green-400 rounded-lg p-4 text-xs font-mono overflow-x-auto">
              <p class="text-gray-400 mb-2">{{ t('landlord.modifications.form.example_sort_the_menu_by_name') }}</p>
              <pre>{{ exampleJson }}</pre>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
              <div class="bg-gray-50 rounded-lg p-3 border">
                <p class="font-semibold text-gray-700 mb-1">position: "replace"</p>
                <p class="text-gray-500">
                  {{ t('landlord.modifications.form.replace') }} <code>search</code> na <code>add</code>
                </p>
              </div>
              <div class="bg-gray-50 rounded-lg p-3 border">
                <p class="font-semibold text-gray-700 mb-1">position: "before"</p>
                <p class="text-gray-500">{{ t('common.add') }} <code>add</code> PRZED <code>search</code></p>
              </div>
              <div class="bg-gray-50 rounded-lg p-3 border">
                <p class="font-semibold text-gray-700 mb-1">position: "after"</p>
                <p class="text-gray-500">{{ t('common.add') }} <code>add</code> PO <code>search</code></p>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3">
            <Link
              :href="route('landlord.modifications.index')"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
            >
              {{ t('common.cancel') }}
            </Link>
            <button
              type="submit"
              :disabled="submitting"
              class="px-6 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
              {{ submitting ? 'Zapisywanie...' : t('common.save_the_modification') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </LandlordLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import LandlordLayout from '@/Layouts/LandlordLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  modification: Object,
  tenants: Array,
})

const errors = ref({})
const submitting = ref(false)

const form = reactive({
  name: props.modification?.name || '',
  code: props.modification?.code || '',
  description: props.modification?.description || '',
  version: props.modification?.version || '1.0.0',
  author: props.modification?.author || '',
  status: props.modification?.status ?? false,
  tenant_ids: props.modification?.tenant_ids ?? null,
  rules_json: props.modification?.rules_json || '',
})

const exampleJson = `{
  "patches": [
    {
      "file": "app/Http/Controllers/Tenant/Client/MenuController.php",
      "operations": [
        {
          "search": "->orderBy('sort_order', 'asc')",
          "add": "->orderBy('name', 'asc')",
          "position": "replace"
        }
      ]
    },
    {
      "file": "app/Http/Controllers/Tenant/Manager/OrderController.php",
      "operations": [
        {
          "search": "$commission = 0;",
          "add": "$commission = $order->total * 0.05;",
          "position": "replace"
        }
      ]
    }
  ]
}`

const submit = () => {
  submitting.value = true
  errors.value = {}

  const url = props.modification
    ? route('landlord.modifications.update', props.modification.id)
    : route('landlord.modifications.store')

  const method = props.modification ? 'put' : 'post'

  router[method](
    url,
    { ...form },
    {
      onError: (e) => {
        errors.value = e
        submitting.value = false
      },
      onFinish: () => {
        submitting.value = false
      },
    },
  )
}
</script>
