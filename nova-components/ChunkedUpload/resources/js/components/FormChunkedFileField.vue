<template>
  <div class="chunked-upload-field py-4">
    <div class="flex flex-col md:flex-row md:items-start">
      <!-- Label Section -->
      <div class="w-full md:w-1/5 md:py-2">
        <label :for="field.attribute" class="inline-block text-gray-700 dark:text-gray-300 font-bold mb-1">
          {{ field.name }}
          <span v-if="field.required" class="text-red-500 font-bold">*</span>
        </label>
      </div>

      <!-- Field Content Section -->
      <div class="w-full md:w-4/5">
        <!-- Existing File Display (if not currently replacing) -->
        <div v-if="hasExistingFile && !file && !isCompleted" class="mb-3 p-3 bg-gray-50 dark:bg-gray-800 border rounded flex items-center justify-between">
          <div class="flex items-center space-x-2">
            <span class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ field.value }}</span>
          </div>
          <a v-if="field.downloadable && field.downloadUrl" :href="field.downloadUrl" target="_blank" class="text-sm text-primary-500 font-bold hover:underline">
            {{ __('Download') }}
          </a>
        </div>

        <!-- Dropzone / File Picker (when not uploading or completed) -->
        <div
          v-if="!isUploading && !isCompleted"
          class="chunked-dropzone border-2 border-dashed rounded-lg p-6 text-center cursor-pointer transition-colors"
          :class="{ 'border-primary-500 bg-primary-50 dark:bg-gray-800': isDragging, 'border-gray-300 dark:border-gray-600 hover:border-gray-400': !isDragging }"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
          @click="triggerFileInput"
        >
          <input
            ref="fileInput"
            type="file"
            class="hidden"
            :accept="field.acceptedTypes || '*'"
            @change="handleFileChange"
          />
          <div class="flex flex-col items-center justify-center space-y-2">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <div class="text-sm font-medium text-gray-700 dark:text-gray-200">
              <span class="text-primary-500 font-bold">{{ __('Choose a file') }}</span> {{ __('or drag & drop here') }}
            </div>
            <div class="text-xs text-gray-500">
              {{ __('Chunked upload enabled') }} &bull; {{ __('Chunk size:') }} {{ formatBytes(chunkSize) }}
              <span v-if="field.maxFileSize"> &bull; {{ __('Max:') }} {{ formatBytes(field.maxFileSize) }}</span>
            </div>
          </div>
        </div>

        <!-- Upload In Progress / Completed State -->
        <div v-if="isUploading || isCompleted" class="p-4 border rounded-lg bg-gray-50 dark:bg-gray-800 space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 truncate">
              <span class="font-bold text-sm text-gray-800 dark:text-gray-100 truncate">{{ file ? file.name : '' }}</span>
              <span class="text-xs text-gray-500">({{ formatBytes(file ? file.size : 0) }})</span>
            </div>
            <button
              v-if="!isCompleted"
              type="button"
              @click="cancelUpload"
              class="text-xs text-red-600 hover:text-red-800 font-bold ml-2"
            >
              {{ __('Cancel') }}
            </button>
            <button
              v-else
              type="button"
              @click="resetField"
              class="text-xs text-gray-600 hover:text-gray-800 dark:text-gray-300 font-bold ml-2"
            >
              {{ __('Change File') }}
            </button>
          </div>

          <!-- Progress Bar -->
          <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
            <div
              class="h-full transition-all duration-200"
              :class="isCompleted ? 'bg-green-500' : 'bg-primary-500'"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>

          <!-- Status Message -->
          <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
            <span>{{ statusText }}</span>
            <span class="font-bold">{{ uploadProgress }}%</span>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="mt-2 text-xs text-red-600 font-bold flex items-center justify-between">
          <span>{{ errorMessage }}</span>
          <button type="button" @click="retryUpload" class="underline ml-2">
            {{ __('Retry') }}
          </button>
        </div>

        <!-- Help Text -->
        <p v-if="field.helpText && showHelpText" class="help-text mt-2 text-xs text-gray-500">
          {{ field.helpText }}
        </p>

        <!-- Validation Errors from Form -->
        <p v-if="errors && errors.has(field.attribute)" class="my-2 text-xs text-red-600 font-bold">
          {{ errors.first(field.attribute) }}
        </p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FormChunkedFileField',
  props: {
    resourceName: { type: String, default: '' },
    resourceId: { type: [Number, String], default: null },
    field: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
    showHelpText: { type: Boolean, default: true },
    formUniqueId: { type: String, default: '' },
  },
  data() {
    return {
      file: null,
      value: '',
      uploadId: null,
      isDragging: false,
      isUploading: false,
      isCompleted: false,
      uploadProgress: 0,
      currentChunk: 0,
      totalChunks: 0,
      statusText: '',
      errorMessage: null,
      aborted: false,
    }
  },
  computed: {
    chunkSize() {
      return this.field.chunkSize || 5 * 1024 * 1024
    },
    hasExistingFile() {
      return Boolean(this.field.value)
    },
  },
  created() {
    this.setInitialValue()
  },
  mounted() {
    this.field.fill = this.fill
  },
  beforeUnmount() {
    if (this.isUploading && !this.isCompleted && this.uploadId) {
      this.cancelUpload()
    }
  },
  methods: {
    setInitialValue() {
      this.value = !(this.field.value === undefined || this.field.value === null)
        ? this.field.value
        : ''
    },
    triggerFileInput() {
      if (this.$refs.fileInput) {
        this.$refs.fileInput.click()
      }
    },
    handleFileChange(event) {
      const files = event.target.files
      if (files && files.length > 0) {
        this.processFile(files[0])
      }
    },
    handleDrop(event) {
      this.isDragging = false
      const files = event.dataTransfer.files
      if (files && files.length > 0) {
        this.processFile(files[0])
      }
    },
    processFile(file) {
      if (!file) return

      if (this.field.maxFileSize && file.size > this.field.maxFileSize) {
        this.errorMessage = `File size (${this.formatBytes(file.size)}) exceeds maximum allowed size of ${this.formatBytes(this.field.maxFileSize)}.`
        return
      }

      this.file = file
      this.errorMessage = null
      this.startUpload()
    },
    generateUploadId() {
      return 'upl_' + Date.now() + '_' + Math.random().toString(36).substring(2, 12)
    },
    async startUpload() {
      if (!this.file) return

      this.uploadId = this.generateUploadId()
      this.totalChunks = Math.max(1, Math.ceil(this.file.size / this.chunkSize))
      this.currentChunk = 0
      this.isUploading = true
      this.isCompleted = false
      this.aborted = false
      this.uploadProgress = 0
      this.statusText = `Preparing upload (1 of ${this.totalChunks} chunks)...`

      if (window.Nova && typeof window.Nova.$emit === 'function') {
        window.Nova.$emit('file-upload-started')
      }

      await this.uploadNextChunk()
    },
    getRequester() {
      if (window.Nova && typeof window.Nova.request === 'function') {
        return window.Nova.request()
      }
      if (window.axios) {
        return window.axios
      }
      return null
    },
    async uploadNextChunk() {
      if (this.aborted || !this.isUploading) return

      const start = this.currentChunk * this.chunkSize
      const end = Math.min(this.file.size, start + this.chunkSize)
      const chunkBlob = this.file.slice(start, end)

      const formData = new FormData()
      formData.append('upload_id', this.uploadId)
      formData.append('chunk_index', this.currentChunk)
      formData.append('total_chunks', this.totalChunks)
      formData.append('file_name', this.file.name)
      formData.append('total_size', this.file.size)
      formData.append('chunk', chunkBlob, this.file.name)

      const uploadUrl = this.field.uploadUrl || '/nova-api/chunked-upload/upload'
      const requester = this.getRequester()

      try {
        let response
        if (requester) {
          response = await requester.post(uploadUrl, formData, {
            onUploadProgress: (progressEvent) => {
              if (progressEvent.total && this.totalChunks > 0) {
                const chunkFraction = progressEvent.loaded / progressEvent.total
                const overallProgress = Math.min(
                  99,
                  Math.round(((this.currentChunk + chunkFraction) / this.totalChunks) * 100)
                )
                this.uploadProgress = overallProgress
              }
            },
          })
        } else {
          // Fallback to fetch if axios/Nova is not available
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          const fetchHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
          }
          if (csrfToken) {
            fetchHeaders['X-CSRF-TOKEN'] = csrfToken
          }
          const rawResponse = await fetch(uploadUrl, {
            method: 'POST',
            body: formData,
            headers: fetchHeaders,
          })
          if (!rawResponse.ok) {
            const errJson = await rawResponse.json().catch(() => ({}))
            throw new Error(errJson.message || `Upload failed with status ${rawResponse.status}`)
          }
          response = { data: await rawResponse.json() }
        }

        const data = response.data

        if (this.aborted) return

        this.currentChunk++
        this.uploadProgress = Math.round((this.currentChunk / this.totalChunks) * 100)

        if (data.status === 'completed' || this.currentChunk >= this.totalChunks) {
          this.isUploading = false
          this.isCompleted = true
          this.uploadProgress = 100
          this.statusText = 'File uploaded and assembled successfully!'
          this.value = JSON.stringify({
            upload_id: this.uploadId,
            original_name: this.file.name,
            file_name: this.file.name,
            size: this.file.size,
            mime_type: this.file.type,
          })
          if (this.field) {
            this.field.value = this.value
          }
          this.$emit('field-changed')
          if (window.Nova && typeof window.Nova.$emit === 'function') {
            window.Nova.$emit('file-upload-finished')
          }
          if (window.Nova && typeof window.Nova.success === 'function') {
            window.Nova.success(`File "${this.file.name}" uploaded successfully.`)
          }
        } else {
          this.statusText = `Uploading chunk ${this.currentChunk + 1} of ${this.totalChunks} (${this.uploadProgress}%)...`
          await this.uploadNextChunk()
        }
      } catch (error) {
        if (this.aborted) return
        this.isUploading = false
        const serverMessage = error.response && error.response.data && (error.response.data.message || error.response.data.error)
        this.errorMessage = serverMessage
          ? serverMessage
          : (error.message || `Failed to upload chunk ${this.currentChunk + 1}. Please try again.`)
        if (window.Nova && typeof window.Nova.error === 'function') {
          window.Nova.error(this.errorMessage)
        }
      }
    },
    async retryUpload() {
      this.errorMessage = null
      this.isUploading = true
      await this.uploadNextChunk()
    },
    async cancelUpload() {
      this.aborted = true
      this.isUploading = false
      if (this.uploadId) {
        const cancelUrl = this.field.cancelUrl || '/nova-api/chunked-upload/cancel'
        const requester = this.getRequester()
        try {
          if (requester) {
            await requester.post(cancelUrl, { upload_id: this.uploadId })
          } else {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            const fetchHeaders = {
              'Content-Type': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            }
            if (csrfToken) {
              fetchHeaders['X-CSRF-TOKEN'] = csrfToken
            }
            await fetch(cancelUrl, {
              method: 'POST',
              headers: fetchHeaders,
              body: JSON.stringify({ upload_id: this.uploadId }),
            })
          }
        } catch (e) {
          // ignore cleanup failures
        }
      }
      this.resetField()
    },
    resetField() {
      this.file = null
      this.value = ''
      this.uploadId = null
      this.isUploading = false
      this.isCompleted = false
      this.uploadProgress = 0
      this.currentChunk = 0
      this.totalChunks = 0
      this.statusText = ''
      this.errorMessage = null
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
      if (this.field) {
        this.field.value = ''
      }
      this.$emit('field-changed')
    },
    fill(formData) {
      if (this.value) {
        formData.append(this.field.attribute, this.value)
      }
    },
    formatBytes(bytes, decimals = 2) {
      if (!bytes || bytes === 0) return '0 Bytes'
      const k = 1024
      const dm = decimals < 0 ? 0 : decimals
      const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
    },
  },
}
</script>

<style scoped>
.chunked-dropzone {
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>
