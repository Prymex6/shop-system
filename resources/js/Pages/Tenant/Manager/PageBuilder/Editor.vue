<template>
  <ManagerLayout :title="page ? t('common.edit_page') : t('manager.pagebuilder.index.new_page')">
    <div class="space-y-4">
      <!-- Header -->
      <div class="flex items-center gap-3">
        <Link :href="route('tenant.manager.page-builder.index')" class="text-gray-400 hover:text-gray-700">
          <i class="fa-solid fa-arrow-left"></i>
        </Link>
        <div class="flex-1">
          <h1 class="text-3xl font-bold text-gray-900">
            {{ page ? t('common.edit_page') : t('manager.pagebuilder.index.new_page') }}
          </h1>
        </div>
        <div class="flex items-center gap-2">
          <select
            v-model="form.status"
            class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
          >
            <option value="draft">{{ t('manager.articles.form.draft') }}</option>
            <option value="published">{{ t('manager.pagebuilder.editor.published') }}</option>
          </select>
          <button
            @click="save"
            :disabled="saving"
            class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 text-sm font-semibold disabled:opacity-50"
          >
            {{ saving ? t('common.saving') : t('common.save') }}
          </button>
        </div>
      </div>

      <!-- Page meta -->
      <div class="bg-white shadow rounded-lg p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{
            t('manager.pagebuilder.editor.page_title')
          }}</label>
          <input
            v-model="form.title"
            type="text"
            @input="autoSlug"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            :placeholder="t('manager.pagebuilder.editor.e_g_about_us')"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{
            t('manager.pagebuilder.editor.url_slug')
          }}</label>
          <input
            v-model="form.slug"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-blue-500"
            placeholder="o-nas"
          />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{
            t('manager.pagebuilder.editor.meta_description')
          }}</label>
          <input
            v-model="form.meta_description"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500"
            :placeholder="t('manager.pagebuilder.editor.seo_description')"
          />
        </div>
      </div>

      <div class="grid grid-cols-12 gap-4">
        <!-- Left: Block palette -->
        <div class="col-span-12 lg:col-span-3 space-y-3">
          <div class="bg-white shadow rounded-lg p-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
              {{ t('manager.pagebuilder.editor.blocks') }}
            </p>
            <div class="space-y-2">
              <div
                v-for="bt in blockTypes"
                :key="bt.type"
                draggable="true"
                @dragstart="onPaletteDragStart(bt.type)"
                class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-grab hover:border-blue-400 hover:bg-blue-50 transition select-none"
              >
                <i :class="bt.icon" class="text-gray-500 w-5 text-center"></i>
                <span class="text-sm font-medium text-gray-700">{{ bt.label }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Center: Canvas -->
        <div class="col-span-12 lg:col-span-6">
          <div class="bg-white shadow rounded-lg min-h-[500px] p-4" @dragover.prevent @drop="onCanvasDrop">
            <p v-if="!form.blocks.length" class="text-center text-gray-400 py-20 text-sm select-none">
              <i class="fa-solid fa-arrow-left text-2xl block mb-3"></i>
              {{ t('manager.pagebuilder.editor.drag_a_block_from_the_left') }}
            </p>

            <div v-else class="space-y-3">
              <div
                v-for="(block, i) in form.blocks"
                :key="block.id"
                draggable="true"
                @dragstart="onBlockDragStart(i)"
                @dragover.prevent="onBlockDragOver(i)"
                @drop.stop="onBlockReorder(i)"
                :class="[
                  'border-2 rounded-xl transition cursor-grab',
                  selectedBlock === i ? 'border-blue-500 shadow-md' : 'border-gray-200 hover:border-blue-300',
                ]"
                @click="selectedBlock = i"
              >
                <div
                  class="flex items-center justify-between px-4 py-2 bg-gray-50 rounded-t-xl border-b border-gray-200"
                >
                  <span class="text-xs font-semibold text-gray-600 flex items-center gap-2">
                    <i :class="getBlockType(block.type)?.icon" class="text-gray-400"></i>
                    {{ getBlockType(block.type)?.label }}
                  </span>
                  <div class="flex items-center gap-1">
                    <button
                      @click.stop="moveBlock(i, -1)"
                      :disabled="i === 0"
                      class="text-gray-400 hover:text-gray-700 disabled:opacity-30 px-1"
                    >
                      ↑
                    </button>
                    <button
                      @click.stop="moveBlock(i, 1)"
                      :disabled="i === form.blocks.length - 1"
                      class="text-gray-400 hover:text-gray-700 disabled:opacity-30 px-1"
                    >
                      ↓
                    </button>
                    <button @click.stop="removeBlock(i)" class="text-red-400 hover:text-red-600 px-1 ml-1">✕</button>
                  </div>
                </div>

                <!-- Block preview -->
                <div class="p-4">
                  <BlockPreview :block="block" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Block settings -->
        <div class="col-span-12 lg:col-span-3">
          <div class="bg-white shadow rounded-lg p-4 sticky top-4">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
              {{ t('manager.pagebuilder.editor.block_properties') }}
            </p>
            <div v-if="selectedBlock !== null && form.blocks[selectedBlock]">
              <BlockEditor :block="form.blocks[selectedBlock]" @update="updateBlock" />
            </div>
            <p v-else class="text-sm text-gray-400">{{ t('manager.pagebuilder.editor.click_a_block_to_edit_it') }}</p>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import BlockPreview from './BlockPreview.vue'
import BlockEditor from './BlockEditor.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({ page: { type: Object, default: null } })

const blockTypes = [
  { type: 'hero', label: t('common.hero_banner'), icon: 'fa-solid fa-image' },
  { type: 'text', label: t('common.text'), icon: 'fa-solid fa-align-left' },
  { type: 'image', label: t('manager.pagebuilder.editor.image'), icon: 'fa-solid fa-photo-film' },
  { type: 'columns', label: t('manager.pagebuilder.editor.columns_2'), icon: 'fa-solid fa-table-columns' },
  { type: 'button', label: t('manager.pagebuilder.editor.call_to_action_button'), icon: 'fa-solid fa-hand-pointer' },
  { type: 'divider', label: 'Separator', icon: 'fa-solid fa-minus' },
  { type: 'html', label: t('common.custom_html'), icon: 'fa-solid fa-code' },
  { type: 'spacer', label: t('common.spacing'), icon: 'fa-solid fa-arrows-up-down' },
]

const getBlockType = (type) => blockTypes.find((b) => b.type === type)

const defaultBlockData = {
  hero: {
    heading: t('common.section_heading'),
    subheading: t('common.subheading'),
    bg_color: '#1e1b4b',
    text_color: '#ffffff',
    button_text: t('manager.pagebuilder.editor.buy_now'),
    button_url: t('manager.homepagebuilder.index.shop'),
    image_url: '',
  },
  text: { content: t('common.write_the_content_here'), align: 'left' },
  image: { url: '', alt: '', width: '100%', link: '' },
  columns: {
    left: t('manager.pagebuilder.blockeditor.left_column'),
    right: t('manager.pagebuilder.blockeditor.right_column'),
  },
  button: { text: t('manager.pagebuilder.editor.click_here'), url: '/', style: 'primary', align: 'center' },
  divider: { margin: '24' },
  html: { code: t('common.p_your_own_html_p') },
  spacer: { height: '40' },
}

const form = reactive({
  title: props.page?.title || '',
  slug: props.page?.slug || '',
  status: props.page?.status || 'draft',
  blocks: (props.page?.blocks || []).map((b) => ({ ...b, id: b.id || crypto.randomUUID() })),
  meta_title: props.page?.meta_title || '',
  meta_description: props.page?.meta_description || '',
})

const saving = ref(false)
const selectedBlock = ref(null)
let dragFromPalette = null
let dragFromIndex = null

const autoSlug = () => {
  if (!props.page) {
    form.slug = form.title
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/(^-|-$)/g, '')
  }
}

const onPaletteDragStart = (type) => {
  dragFromPalette = type
  dragFromIndex = null
}

const onBlockDragStart = (i) => {
  dragFromIndex = i
  dragFromPalette = null
}

const onBlockDragOver = (i) => {
  /* visual feedback if needed */
}

const onCanvasDrop = () => {
  if (dragFromPalette) {
    const newBlock = {
      id: crypto.randomUUID(),
      type: dragFromPalette,
      data: { ...defaultBlockData[dragFromPalette] },
    }
    form.blocks.push(newBlock)
    selectedBlock.value = form.blocks.length - 1
    dragFromPalette = null
  }
}

const onBlockReorder = (toIndex) => {
  if (dragFromIndex === null || dragFromIndex === toIndex) return
  const [moved] = form.blocks.splice(dragFromIndex, 1)
  form.blocks.splice(toIndex, 0, moved)
  selectedBlock.value = toIndex
  dragFromIndex = null
}

const moveBlock = (i, dir) => {
  const j = i + dir
  if (j < 0 || j >= form.blocks.length) return
  const [b] = form.blocks.splice(i, 1)
  form.blocks.splice(j, 0, b)
  selectedBlock.value = j
}

const removeBlock = (i) => {
  form.blocks.splice(i, 1)
  if (selectedBlock.value >= form.blocks.length) selectedBlock.value = null
}

const updateBlock = (data) => {
  if (selectedBlock.value === null) return
  form.blocks[selectedBlock.value].data = { ...data }
}

const save = () => {
  saving.value = true
  const payload = {
    title: form.title,
    slug: form.slug,
    status: form.status,
    blocks: form.blocks,
    meta_title: form.meta_title,
    meta_description: form.meta_description,
  }
  if (props.page) {
    router.put(route('tenant.manager.page-builder.update', props.page.id), payload, {
      onFinish: () => {
        saving.value = false
      },
    })
  } else {
    router.post(route('tenant.manager.page-builder.store'), payload, {
      onFinish: () => {
        saving.value = false
      },
    })
  }
}
</script>
