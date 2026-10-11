<template>
  <div class="flex border-b border-40 py-4">
    <div class="w-1/4">
      <h4 class="font-normal text-80">{{ field.name }}</h4>
    </div>
    <div class="w-3/4">
      <div v-if="field.value" class="flex items-center space-x-2">
        <span class="font-mono text-sm">{{ field.value }}</span>
        <a
          v-if="field.downloadable && field.downloadUrl"
          :href="field.downloadUrl"
          target="_blank"
          class="text-primary-500 font-bold hover:underline text-sm ml-2"
        >
          {{ __('Download') }}
        </a>
      </div>
      <p v-else>&mdash;</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DetailChunkedFileField',
  props: {
    resourceName: { type: String, default: '' },
    resourceId: { type: [Number, String], default: null },
    field: { type: Object, required: true },
  },
}
</script>
