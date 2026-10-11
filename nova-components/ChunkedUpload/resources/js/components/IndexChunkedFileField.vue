<template>
  <div class="py-2">
    <span v-if="field.value" class="font-mono text-xs">{{ field.value }}</span>
    <span v-else>&mdash;</span>
  </div>
</template>

<script>
export default {
  name: 'IndexChunkedFileField',
  props: {
    resourceName: { type: String, default: '' },
    field: { type: Object, required: true },
  },
}
</script>
