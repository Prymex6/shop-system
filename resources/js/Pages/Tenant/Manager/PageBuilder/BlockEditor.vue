<template>
  <div class="space-y-3 text-sm">
    <!-- Hero -->
    <template v-if="block.type === 'hero'">
      <Field :label="t('common.heading')"
        ><input
          v-model="d.heading"
          @input="emit"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      /></Field>
      <Field :label="t('common.subheading')"
        ><input
          v-model="d.subheading"
          @input="emit"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      /></Field>
      <Field :label="t('common.background_colour')"
        ><input
          v-model="d.bg_color"
          @input="emit"
          type="color"
          class="h-9 w-full rounded border border-gray-300 cursor-pointer"
      /></Field>
      <Field :label="t('manager.pagebuilder.blockeditor.text_colour')"
        ><input
          v-model="d.text_color"
          @input="emit"
          type="color"
          class="h-9 w-full rounded border border-gray-300 cursor-pointer"
      /></Field>
      <Field :label="t('common.button_text')"
        ><input
          v-model="d.button_text"
          @input="emit"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      /></Field>
      <Field :label="t('common.button_url')"
        ><input
          v-model="d.button_url"
          @input="emit"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      /></Field>
      <Field :label="t('common.background_image_url')"
        ><input
          v-model="d.image_url"
          @input="emit"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="https://..."
      /></Field>
    </template>

    <!-- Text -->
    <template v-else-if="block.type === 'text'">
      <Field :label="t('common.content_2')">
        <textarea
          v-model="d.content"
          @input="emit"
          rows="6"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
        ></textarea>
      </Field>
      <Field :label="t('common.alignment')">
        <select
          v-model="d.align"
          @change="emit"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option value="left">{{ t('manager.pagebuilder.blockeditor.left') }}</option>
          <option value="center">{{ t('manager.pagebuilder.blockeditor.centre') }}</option>
          <option value="right">{{ t('manager.pagebuilder.blockeditor.right') }}</option>
        </select>
      </Field>
    </template>

    <!-- Image -->
    <template v-else-if="block.type === 'image'">
      <Field :label="t('manager.collections.index.image_url')"
        ><input
          v-model="d.url"
          @input="emit"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="https://..."
      /></Field>
      <Field :label="t('common.alt_text')"
        ><input
          v-model="d.alt"
          @input="emit"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      /></Field>
      <Field :label="t('manager.pagebuilder.blockeditor.link_optional')"
        ><input
          v-model="d.link"
          @input="emit"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="https://..."
      /></Field>
    </template>

    <!-- Columns -->
    <template v-else-if="block.type === 'columns'">
      <Field :label="t('manager.pagebuilder.blockeditor.left_column')">
        <textarea
          v-model="d.left"
          @input="emit"
          rows="3"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
        ></textarea>
      </Field>
      <Field :label="t('manager.pagebuilder.blockeditor.right_column')">
        <textarea
          v-model="d.right"
          @input="emit"
          rows="3"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
        ></textarea>
      </Field>
    </template>

    <!-- Button -->
    <template v-else-if="block.type === 'button'">
      <Field :label="t('common.text')"
        ><input
          v-model="d.text"
          @input="emit"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      /></Field>
      <Field label="URL"
        ><input
          v-model="d.url"
          @input="emit"
          type="text"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      /></Field>
      <Field :label="t('manager.homepagebuilder.index.style')">
        <select
          v-model="d.style"
          @change="emit"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option value="primary">{{ t('manager.pagebuilder.blockeditor.primary') }}</option>
          <option value="outline">{{ t('manager.pagebuilder.blockeditor.outline') }}</option>
        </select>
      </Field>
      <Field :label="t('common.alignment')">
        <select
          v-model="d.align"
          @change="emit"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option value="left">{{ t('manager.pagebuilder.blockeditor.left') }}</option>
          <option value="center">{{ t('manager.pagebuilder.blockeditor.centre') }}</option>
          <option value="right">{{ t('manager.pagebuilder.blockeditor.right') }}</option>
        </select>
      </Field>
    </template>

    <!-- Divider -->
    <template v-else-if="block.type === 'divider'">
      <Field :label="t('manager.pagebuilder.blockeditor.margin_px')"
        ><input
          v-model="d.margin"
          @input="emit"
          type="number"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      /></Field>
    </template>

    <!-- HTML -->
    <template v-else-if="block.type === 'html'">
      <Field :label="t('manager.pagebuilder.blockeditor.html_code')">
        <textarea
          v-model="d.code"
          @input="emit"
          rows="8"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-xs resize-none"
        ></textarea>
      </Field>
      <p class="text-xs text-amber-600">
        {{ t('manager.pagebuilder.blockeditor.this_code_is_rendered_without_sanitising') }}
      </p>
    </template>

    <!-- Spacer -->
    <template v-else-if="block.type === 'spacer'">
      <Field :label="t('common.height_px')"
        ><input
          v-model="d.height"
          @input="emit"
          type="number"
          min="4"
          max="200"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      /></Field>
    </template>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({ block: { type: Object, required: true } })
const emits = defineEmits(['update'])

const d = reactive({ ...props.block.data })

watch(
  () => props.block,
  (newBlock) => {
    Object.assign(d, newBlock.data)
  },
  { deep: true },
)

const emit = () => emits('update', { ...d })

// Field component inline
const Field = {
  props: ['label'],
  template: `<div><label class="block text-xs font-medium text-gray-600 mb-1">{{ label }}</label><slot /></div>`,
}
</script>
